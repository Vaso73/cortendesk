<div>
    <div class="card">

            {{-- Toolbar --}}
            <div class="rd-toolbar">
                <div>
                    <h4 class="header-title">{{ __('settings.strategies.title') }}</h4>
                    <p class="rd-card-sub mb-0">{{ __('settings.strategies.subtitle') }}</p>
                </div>
                <div class="rd-toolbar-actions">
                    <button type="button" class="btn btn-primary" wire:click="create">
                        <i class="ri-add-line"></i>{{ __('settings.strategies.add') }}
                    </button>
                </div>
            </div>

            @if ($strategies->isNotEmpty() && $strategies->firstWhere('is_default', true) === null)
                <div class="rd-toolbar">
                    <div class="alert alert-secondary py-2 mb-0 w-100">
                        <i class="ri-information-line me-1"></i>{{ __('settings.strategies.no_default') }}
                    </div>
                </div>
            @endif

            {{-- Desktop table (md and up) --}}
            <div class="table-responsive d-none d-md-block">
                <table class="table table-hover table-centered mb-0">
                    <thead>
                    <tr>
                        <th>{{ __('settings.common.name') }}</th>
                        <th>{{ __('settings.strategies.options') }}</th>
                        <th>{{ __('settings.strategies.assigned_to') }}</th>
                        <th>{{ __('settings.strategies.in_force') }}</th>
                        <th>{{ __('settings.common.enabled') }}</th>
                        <th class="text-end">{{ __('settings.common.action') }}</th>
                    </tr>
                    </thead>
                    <tbody>
                    @forelse ($strategies as $strategy)
                        <tr wire:key="s{{ $strategy->id }}">
                            <td>
                                <span class="rd-cell-title d-inline">{{ $strategy->name }}</span>
                                @if ($strategy->is_default)
                                    <span class="badge bg-primary-subtle text-primary ms-1">{{ __('settings.strategies.default') }}</span>
                                @endif
                                @if ($strategy->enforce)
                                    <span class="badge bg-warning-subtle text-warning ms-1" title="{{ __('settings.strategies.enforced_title') }}">{{ __('settings.strategies.enforced') }}</span>
                                @endif
                                @if ($strategy->note)
                                    <small class="text-muted d-block">{{ $strategy->note }}</small>
                                @endif
                            </td>
                            <td>
                                <span class="badge bg-secondary-subtle text-secondary">{{ count($strategy->optionMap()) }}</span>
                            </td>
                            <td>
                                <span class="text-nowrap" title="{{ __('settings.common.devices') }}">
                                    <i class="ri-computer-line me-1 text-muted"></i>{{ $strategy->devices_count }}
                                </span>
                                <span class="text-nowrap ms-2" title="{{ __('settings.common.users') }}">
                                    <i class="ri-user-line me-1 text-muted"></i>{{ $strategy->users_count }}
                                </span>
                                <span class="text-nowrap ms-2" title="{{ __('settings.common.device_groups') }}">
                                    <i class="ri-folder-line me-1 text-muted"></i>{{ $strategy->device_groups_count }}
                                </span>
                            </td>
                            <td>
                                <span class="badge bg-info-subtle text-info">{{ trans_choice('settings.strategies.device_count', $strategy->resolved_devices_count, ['count' => $strategy->resolved_devices_count]) }}</span>
                            </td>
                            <td>
                                <div class="form-check form-switch mb-0">
                                    <input class="form-check-input" type="checkbox" role="switch"
                                           id="strategy-enabled-{{ $strategy->id }}"
                                           @checked($strategy->enabled)
                                           wire:click="toggleEnabled({{ $strategy->id }})">
                                    <label class="form-check-label visually-hidden" for="strategy-enabled-{{ $strategy->id }}">{{ __('settings.common.enabled') }}</label>
                                </div>
                            </td>
                            <td class="text-end rd-rowact">
                                <a href="javascript:void(0);" class="rd-act me-2" wire:click="showHistory({{ $strategy->id }})">{{ __('settings.strategies.history') }}</a>
                                @if ($isAdmin)
                                    <a href="javascript:void(0);" class="rd-act me-2" wire:click="showCompliance({{ $strategy->id }})">{{ __('settings.strategies.compliance') }}</a>
                                @endif
                                <a href="javascript:void(0);" class="rd-act me-2" wire:click="openAssign({{ $strategy->id }})">{{ __('settings.strategies.assign') }}</a>
                                <a href="javascript:void(0);" class="rd-act me-2" wire:click="edit({{ $strategy->id }})">{{ __('settings.common.edit') }}</a>
                                <a href="javascript:void(0);" class="text-danger"
                                   wire:click="deleteStrategy({{ $strategy->id }})"
                                   wire:confirm="{{ __('settings.strategies.delete_confirm', ['name' => $strategy->name]) }}">{{ __('settings.common.delete') }}</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="rd-empty-cell">
                                <div class="rd-empty">
                                    <div class="rd-empty-icon"><i class="ri-settings-3-line"></i></div>
                                    <p class="rd-empty-title">{{ __('settings.strategies.empty') }}</p>
                                    <p class="rd-empty-text">{{ __('settings.strategies.empty_help') }}</p>
                                    <button type="button" class="btn btn-sm btn-outline-light" wire:click="create">{{ __('settings.strategies.add') }}</button>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>

            {{-- Mobile card list (below md) --}}
            <div class="d-md-none rd-cardlist">
                @forelse ($strategies as $strategy)
                    <div class="rd-mini" wire:key="ms{{ $strategy->id }}">
                            <div class="rd-mini-head">
                                <div class="min-width-0">
                                    <span class="rd-mini-title text-truncate">{{ $strategy->name }}</span>
                                    <span class="rd-mini-sub text-truncate">{{ $strategy->note ?: trans_choice('settings.strategies.option_count', count($strategy->optionMap()), ['count' => count($strategy->optionMap())]) }}</span>
                                </div>
                                <div class="form-check form-switch mb-0 flex-shrink-0">
                                    <input class="form-check-input" type="checkbox" role="switch"
                                           id="m-strategy-enabled-{{ $strategy->id }}"
                                           @checked($strategy->enabled)
                                           wire:click="toggleEnabled({{ $strategy->id }})">
                                    <label class="form-check-label visually-hidden" for="m-strategy-enabled-{{ $strategy->id }}">{{ __('settings.common.enabled') }}</label>
                                </div>
                            </div>
                            <div class="mt-2">
                                @if ($strategy->is_default)
                                    <span class="badge bg-primary-subtle text-primary">{{ __('settings.strategies.default') }}</span>
                                @endif
                                @if ($strategy->enforce)
                                    <span class="badge bg-warning-subtle text-warning">{{ __('settings.strategies.enforced') }}</span>
                                @endif
                                <span class="badge bg-info-subtle text-info">{{ __('settings.strategies.in_force') }}: {{ $strategy->resolved_devices_count }}</span>
                            </div>
                            <div class="rd-mini-foot">
                                <span class="rd-mini-sub text-nowrap">
                                    <i class="ri-computer-line me-1"></i>{{ $strategy->devices_count }}
                                    <i class="ri-user-line ms-2 me-1"></i>{{ $strategy->users_count }}
                                    <i class="ri-folder-line ms-2 me-1"></i>{{ $strategy->device_groups_count }}
                                </span>
                                <div class="rd-mini-acts">
                                    <a href="javascript:void(0);" class="rd-iconbtn" title="{{ __('settings.strategies.history') }}"
                                       wire:click="showHistory({{ $strategy->id }})"><i class="ri-history-line"></i></a>
                                    @if ($isAdmin)
                                        <a href="javascript:void(0);" class="rd-iconbtn" title="{{ __('settings.strategies.compliance') }}"
                                           wire:click="showCompliance({{ $strategy->id }})"><i class="ri-pulse-line"></i></a>
                                    @endif
                                    <a href="javascript:void(0);" class="rd-iconbtn" title="{{ __('settings.strategies.assign') }}"
                                       wire:click="openAssign({{ $strategy->id }})"><i class="ri-links-line"></i></a>
                                    <a href="javascript:void(0);" class="rd-iconbtn" title="{{ __('settings.common.edit') }}"
                                       wire:click="edit({{ $strategy->id }})"><i class="ri-pencil-line"></i></a>
                                    <a href="javascript:void(0);" class="rd-iconbtn text-danger" title="{{ __('settings.common.delete') }}"
                                       wire:click="deleteStrategy({{ $strategy->id }})"
                                       wire:confirm="{{ __('settings.strategies.delete_confirm_short', ['name' => $strategy->name]) }}"><i class="ri-delete-bin-line"></i></a>
                                </div>
                            </div>
                    </div>
                @empty
                    <div class="rd-empty">
                        <div class="rd-empty-icon"><i class="ri-settings-3-line"></i></div>
                        <p class="rd-empty-title">{{ __('settings.strategies.empty') }}</p>
                        <button type="button" class="btn btn-sm btn-outline-light" wire:click="create">{{ __('settings.strategies.add') }}</button>
                    </div>
                @endforelse
            </div>
    </div>

    {{-- Create / edit modal --}}
    @if ($editingId !== null)
        <div class="modal fade show d-block" tabindex="-1" role="dialog" aria-modal="true"
             style="background: rgba(0,0,0,.6);" wire:key="strategy-editor">
            <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
                <div class="modal-content">
                    <form wire:submit="save">
                        <div class="modal-header">
                            <h5 class="modal-title">{{ $editingId === 0 ? __('settings.strategies.add_title') : __('settings.strategies.edit_title') }}</h5>
                            <button type="button" class="btn-close" wire:click="closeModal" aria-label="{{ __('settings.common.close') }}"></button>
                        </div>
                        <div class="modal-body">
                            <div class="row">
                                <div class="col-12 col-md-6 mb-3">
                                    <label class="form-label" for="sl-name">{{ __('settings.common.name') }} <span class="text-danger">*</span></label>
                                    <input type="text" id="sl-name" class="form-control @error('formName') is-invalid @enderror"
                                           wire:model="formName" autocomplete="off">
                                    @error('formName') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                </div>
                                <div class="col-12 col-md-6 mb-3">
                                    <label class="form-label" for="sl-note">{{ __('settings.common.note') }}</label>
                                    <input type="text" id="sl-note" class="form-control @error('formNote') is-invalid @enderror"
                                           wire:model="formNote" maxlength="500">
                                    @error('formNote') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                </div>
                                <div class="col-12 mb-3">
                                    <label class="form-label" for="sl-revnote">{{ __('settings.strategies.change_note') }} <span class="text-muted fw-normal">{{ __('settings.strategies.change_note_help') }}</span></label>
                                    <input type="text" id="sl-revnote" class="form-control @error('revisionNote') is-invalid @enderror"
                                           wire:model="revisionNote" maxlength="500" placeholder="{{ __('settings.strategies.change_placeholder') }}">
                                    @error('revisionNote') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                </div>
                            </div>

                            <div class="row g-2 mb-3">
                                <div class="col-12 col-md-4">
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" role="switch" id="sl-enabled" wire:model="formEnabled">
                                        <label class="form-check-label" for="sl-enabled">{{ __('settings.common.enabled') }}</label>
                                    </div>
                                    <small class="text-muted">{{ __('settings.strategies.enabled_help') }}</small>
                                </div>
                                <div class="col-12 col-md-4">
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" role="switch" id="sl-default" wire:model="formIsDefault">
                                        <label class="form-check-label" for="sl-default">{{ __('settings.strategies.default_strategy') }}</label>
                                    </div>
                                    <small class="text-muted">{{ __('settings.strategies.default_help') }}</small>
                                </div>
                                <div class="col-12 col-md-4">
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" role="switch" id="sl-enforce" wire:model="formEnforce">
                                        <label class="form-check-label" for="sl-enforce">{{ __('settings.strategies.enforce') }}</label>
                                    </div>
                                    <small class="text-muted">{{ __('settings.strategies.enforce_help') }}</small>
                                </div>
                            </div>

                            <div class="row g-2 mb-3">
                                <div class="col-12 col-md-4">
                                    <label class="form-label" for="sl-timeout">{{ __('settings.strategies.timeout') }}</label>
                                    <input type="number" id="sl-timeout" min="1" max="10080" class="form-control @error('formConfirmationTimeout') is-invalid @enderror"
                                           wire:model="formConfirmationTimeout">
                                    @error('formConfirmationTimeout') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                    <small class="text-muted">{{ __('settings.strategies.timeout_help') }}</small>
                                </div>
                            </div>

                            <div class="alert alert-secondary py-2 fs-13 mb-3">
                                <i class="ri-information-line me-1"></i>{{ __('settings.strategies.not_managed_help') }}
                            </div>

                            @foreach ($catalog as $groupKey => $group)
                                <h5 class="mt-3 mb-1 fs-15">
                                    <i class="{{ $group['icon'] }} me-1 text-muted"></i>{{ $group['title'] }}
                                </h5>
                                <p class="text-muted fs-13">{{ $group['help'] }}</p>

                                <div class="row">
                                    @foreach ($group['options'] as $key => $opt)
                                        <div class="col-12 col-md-6 mb-3" wire:key="opt-{{ $key }}">
                                            <label class="form-label mb-1" for="sl-opt-{{ $key }}">{{ $opt['label'] }}</label>
                                            @if ($opt['choices'] !== null)
                                                <select id="sl-opt-{{ $key }}" class="form-select"
                                                        wire:model="formOptions.{{ $key }}">
                                                    <option value="">{{ __('settings.common.not_managed') }}</option>
                                                    @foreach ($opt['choices'] as $value => $choiceLabel)
                                                        <option value="{{ $value }}">{{ $choiceLabel }}</option>
                                                    @endforeach
                                                </select>
                                            @else
                                                <input type="text" id="sl-opt-{{ $key }}"
                                                       class="form-control @error('formOptions.'.$key) is-invalid @enderror"
                                                       placeholder="{{ __('settings.common.not_managed') }}"
                                                       wire:model="formOptions.{{ $key }}">
                                                @error('formOptions.'.$key) <div class="invalid-feedback">{{ $message }}</div> @enderror
                                            @endif
                                            <small class="text-muted d-block">
                                                <code class="fs-12">{{ $opt['key'] }}</code>
                                                @if ($opt['help']) — {{ $opt['help'] }} @endif
                                            </small>
                                        </div>
                                    @endforeach
                                </div>
                            @endforeach
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-light" wire:click="closeModal">{{ __('settings.common.cancel') }}</button>
                            <button type="submit" class="btn btn-primary">
                                <span wire:loading.remove wire:target="save">{{ $editingId === 0 ? __('settings.strategies.create') : __('settings.common.save_changes') }}</span>
                                <span wire:loading wire:target="save">{{ __('settings.common.saving') }}</span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        <div class="modal-backdrop fade show"></div>
    @endif

    {{-- Assignment modal --}}
    @if ($assigning)
        <div class="modal fade show d-block" tabindex="-1" role="dialog" aria-modal="true"
             style="background: rgba(0,0,0,.6);" wire:key="strategy-assign">
            <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">{{ __('settings.strategies.assignment_title', ['name' => $assigning->name]) }}</h5>
                        <button type="button" class="btn-close" wire:click="closeAssign" aria-label="{{ __('settings.common.close') }}"></button>
                    </div>
                    <div class="modal-body">
                        <p class="text-muted fs-13">
                            {{ __('settings.strategies.assignment_help') }}
                        </p>

                        <ul class="nav nav-tabs nav-bordered mb-3">
                            <li class="nav-item">
                                <a href="javascript:void(0);" class="nav-link {{ $assignTab === 'devices' ? 'active' : '' }}"
                                   wire:click="setAssignTab('devices')">
                                    <i class="ri-computer-line me-1"></i>{{ __('settings.common.devices') }}
                                    <span class="badge bg-secondary-subtle text-secondary ms-1">{{ count($assignDeviceIds) }}</span>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a href="javascript:void(0);" class="nav-link {{ $assignTab === 'users' ? 'active' : '' }}"
                                   wire:click="setAssignTab('users')">
                                    <i class="ri-user-line me-1"></i>{{ __('settings.common.users') }}
                                    <span class="badge bg-secondary-subtle text-secondary ms-1">{{ count($assignUserIds) }}</span>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a href="javascript:void(0);" class="nav-link {{ $assignTab === 'groups' ? 'active' : '' }}"
                                   wire:click="setAssignTab('groups')">
                                    <i class="ri-folder-line me-1"></i>{{ __('settings.common.device_groups') }}
                                    <span class="badge bg-secondary-subtle text-secondary ms-1">{{ count($assignGroupIds) }}</span>
                                </a>
                            </li>
                        </ul>

                        @if ($assignTab === 'devices')
                            <input type="search" class="form-control mb-2" placeholder="{{ __('settings.strategies.search') }}"
                                   wire:model.live.debounce.300ms="assignSearch">
                            <div class="rd-scrollbox" style="max-height: 340px; overflow-y: auto;">
                                <table class="table table-sm table-hover mb-0">
                                    <tbody>
                                    @forelse ($assignDevices as $d)
                                        <tr wire:key="ad{{ $d->id }}">
                                            <td style="width:38px;">
                                                <input class="form-check-input" type="checkbox" id="sa-dev-{{ $d->id }}"
                                                       value="{{ $d->id }}" wire:model="assignDeviceIds"
                                                       aria-label="{{ __('settings.strategies.assign') }}: {{ $d->rustdesk_id }}">
                                            </td>
                                            <td>
                                                {{-- The label is the tap target: a 14px checkbox is not one, and
                                                     `for` makes the whole cell toggle it without a second binding. --}}
                                                <label class="rd-picklabel" for="sa-dev-{{ $d->id }}">
                                                    <span class="fw-semibold">{{ $d->rustdesk_id }}</span>
                                                    @if ($d->alias || $d->hostname)
                                                        <small class="text-muted d-block">{{ $d->alias ?: $d->hostname }}</small>
                                                    @endif
                                                </label>
                                            </td>
                                            <td class="text-end">
                                                @if (($assignTaken['devices'][$d->id] ?? null) && $assignTaken['devices'][$d->id] !== $assigning->name)
                                                    <span class="badge bg-secondary-subtle text-secondary">{{ $assignTaken['devices'][$d->id] }}</span>
                                                @endif
                                            </td>
                                        </tr>
                                    @empty
                                        <tr><td class="text-center text-muted py-3">{{ __('settings.strategies.no_devices') }}</td></tr>
                                    @endforelse
                                    </tbody>
                                </table>
                            </div>
                            <small class="text-muted">
                                {{ __('settings.strategies.selected', ['count' => count($assignDeviceIds)]) }}
                                @if ($assignDevices->count() >= 200) · {{ __('settings.strategies.first_200') }} @endif
                            </small>
                        @elseif ($assignTab === 'users')
                            <div class="rd-scrollbox" style="max-height: 340px; overflow-y: auto;">
                                <table class="table table-sm table-hover mb-0">
                                    <tbody>
                                    @forelse ($assignUsers as $u)
                                        <tr wire:key="au{{ $u->id }}">
                                            <td style="width:38px;">
                                                <input class="form-check-input" type="checkbox" id="sa-usr-{{ $u->id }}"
                                                       value="{{ $u->id }}" wire:model="assignUserIds"
                                                       aria-label="{{ __('settings.strategies.assign') }}: {{ $u->username }}">
                                            </td>
                                            <td>
                                                <label class="rd-picklabel" for="sa-usr-{{ $u->id }}">
                                                    <span class="fw-semibold">{{ $u->username }}</span>
                                                    @if ($u->name)
                                                        <small class="text-muted d-block">{{ $u->name }}</small>
                                                    @endif
                                                </label>
                                            </td>
                                            <td class="text-end">
                                                @if (($assignTaken['users'][$u->id] ?? null) && $assignTaken['users'][$u->id] !== $assigning->name)
                                                    <span class="badge bg-secondary-subtle text-secondary">{{ $assignTaken['users'][$u->id] }}</span>
                                                @endif
                                            </td>
                                        </tr>
                                    @empty
                                        <tr><td class="text-center text-muted py-3">{{ __('settings.strategies.no_users') }}</td></tr>
                                    @endforelse
                                    </tbody>
                                </table>
                            </div>
                            <small class="text-muted">{{ __('settings.strategies.users_help') }}</small>
                        @else
                            <div class="rd-scrollbox" style="max-height: 340px; overflow-y: auto;">
                                <table class="table table-sm table-hover mb-0">
                                    <tbody>
                                    @forelse ($assignGroups as $g)
                                        <tr wire:key="ag{{ $g->id }}">
                                            <td style="width:38px;">
                                                <input class="form-check-input" type="checkbox" id="sa-grp-{{ $g->id }}"
                                                       value="{{ $g->id }}" wire:model="assignGroupIds"
                                                       aria-label="{{ __('settings.strategies.assign') }}: {{ $g->name }}">
                                            </td>
                                            <td>
                                                <label class="rd-picklabel" for="sa-grp-{{ $g->id }}">
                                                    <span class="fw-semibold">{{ $g->name }}</span>
                                                </label>
                                            </td>
                                            <td class="text-end">
                                                @if (($assignTaken['groups'][$g->id] ?? null) && $assignTaken['groups'][$g->id] !== $assigning->name)
                                                    <span class="badge bg-secondary-subtle text-secondary">{{ $assignTaken['groups'][$g->id] }}</span>
                                                @endif
                                            </td>
                                        </tr>
                                    @empty
                                        <tr><td class="text-center text-muted py-3">{{ __('settings.strategies.no_groups') }}</td></tr>
                                    @endforelse
                                    </tbody>
                                </table>
                            </div>
                            <small class="text-muted">{{ __('settings.strategies.groups_help') }}</small>
                        @endif
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-light" wire:click="closeAssign">{{ __('settings.common.cancel') }}</button>
                        <button type="button" class="btn btn-primary" wire:click="saveAssign">
                            <span wire:loading.remove wire:target="saveAssign">{{ __('settings.strategies.save_assignment') }}</span>
                            <span wire:loading wire:target="saveAssign">{{ __('settings.common.saving') }}</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
        <div class="modal-backdrop fade show"></div>
    @endif

    {{-- Immutable revision history and comparison --}}
    @if ($historyStrategy)
        <div class="modal fade show d-block" tabindex="-1" role="dialog" aria-modal="true"
             aria-labelledby="strategy-history-title" wire:keydown.escape.window="closeHistory"
             style="background: rgba(0,0,0,.65);" wire:key="strategy-history">
            <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
                <div class="modal-content">
                    <div class="modal-header">
                        <div>
                            <h5 class="modal-title" id="strategy-history-title">{{ __('settings.strategies.revision_history', ['name' => $historyStrategy->name]) }}</h5>
                            <small class="text-muted">{{ __('settings.strategies.revision_help') }}</small>
                        </div>
                        <button type="button" class="btn-close" wire:click="closeHistory" aria-label="{{ __('settings.common.close') }}"></button>
                    </div>
                    <div class="modal-body">
                        @error('history') <div class="alert alert-danger" role="alert">{{ $message }}</div> @enderror
                        @if ($revisionHistory->isNotEmpty())
                            <div class="row g-2 align-items-end mb-3">
                                <div class="col-12 col-md-5">
                                    <label class="form-label" for="compare-from">{{ __('settings.strategies.compare_from') }}</label>
                                    <select id="compare-from" class="form-select" wire:model.live="compareFromRevisionId">
                                        <option value="">{{ __('settings.strategies.choose_revision') }}</option>
                                        @foreach ($revisionHistory->sortBy('revision') as $revision)
                                            <option value="{{ $revision->id }}">{{ __('settings.strategies.revision', ['number' => $revision->revision]) }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-12 col-md-5">
                                    <label class="form-label" for="compare-to">{{ __('settings.strategies.compare_to') }}</label>
                                    <select id="compare-to" class="form-select" wire:model.live="compareToRevisionId">
                                        <option value="">{{ __('settings.strategies.choose_revision') }}</option>
                                        @foreach ($revisionHistory->sortBy('revision') as $revision)
                                            <option value="{{ $revision->id }}">{{ __('settings.strategies.revision', ['number' => $revision->revision]) }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            @if ($compareFromRevisionId && $compareToRevisionId)
                                <div class="table-responsive mb-4">
                                    <table class="table table-sm"><thead><tr><th>{{ __('settings.strategies.field') }}</th><th>{{ __('settings.strategies.from') }}</th><th>{{ __('settings.strategies.to') }}</th></tr></thead><tbody>
                                    @forelse ($revisionComparison as $change)
                                        <tr><td><code>{{ $change['key'] }}</code></td><td>{{ is_bool($change['before']) ? ($change['before'] ? __('settings.common.yes') : __('settings.common.no')) : ($change['before'] ?? __('settings.common.not_managed')) }}</td><td>{{ is_bool($change['after']) ? ($change['after'] ? __('settings.common.yes') : __('settings.common.no')) : ($change['after'] ?? __('settings.common.not_managed')) }}</td></tr>
                                    @empty
                                        <tr><td colspan="3" class="text-muted">{{ __('settings.strategies.identical') }}</td></tr>
                                    @endforelse
                                    </tbody></table>
                                </div>
                            @endif
                            <div class="list-group">
                                @foreach ($revisionHistory as $revision)
                                    <div class="list-group-item d-flex flex-column flex-md-row justify-content-between gap-2" wire:key="revision-{{ $revision->id }}">
                                        <div>
                                            <strong>{{ __('settings.strategies.revision', ['number' => $revision->revision]) }}</strong>
                                            @if ($historyStrategy->active_revision_id === $revision->id)<span class="badge bg-success-subtle text-success ms-1">{{ __('settings.strategies.active') }}</span>@endif
                                            <div class="text-muted fs-13">{{ $revision->created_by_name ?? $revision->creator?->username ?? __('settings.common.system') }} · {{ $revision->created_at->timezone(config('app.timezone'))->locale(app()->getLocale())->isoFormat('L LT') }} · {{ trans_choice('settings.strategies.affected', $revision->affected_devices, ['count' => $revision->affected_devices]) }}</div>
                                            @if ($revision->change_note)<div class="mt-1">{{ $revision->change_note }}</div>@endif
                                        </div>
                                        @if ($historyStrategy->active_revision_id !== $revision->id)
                                            <button type="button" class="btn btn-sm btn-outline-warning align-self-md-center" wire:click="restoreRevision({{ $revision->id }})" wire:confirm="{{ __('settings.strategies.restore_confirm', ['number' => $revision->revision]) }}">{{ __('settings.strategies.restore') }}</button>
                                        @endif
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <div class="text-muted">{{ __('settings.strategies.no_revisions') }}</div>
                        @endif
                    </div>
                    <div class="modal-footer"><button type="button" class="btn btn-light" wire:click="closeHistory">{{ __('settings.common.close') }}</button></div>
                </div>
            </div>
        </div>
        <div class="modal-backdrop fade show"></div>
    @endif

    {{-- Compliance drill-down (admins only) --}}
    @if ($complianceStrategy && $complianceSummary)
        <div class="modal fade show d-block" tabindex="-1" role="dialog" aria-modal="true" aria-labelledby="strategy-compliance-title"
             wire:keydown.escape.window="closeCompliance" style="background: rgba(0,0,0,.65);" wire:key="strategy-compliance">
            <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
                <div class="modal-content">
                    <div class="modal-header">
                        <div>
                            <h5 class="modal-title" id="strategy-compliance-title">{{ __('settings.strategies.compliance_title', ['name' => $complianceStrategy->name]) }}</h5>
                            <small class="text-muted">{{ __('settings.strategies.compliance_help') }}</small>
                        </div>
                        <button type="button" class="btn-close" wire:click="closeCompliance" aria-label="{{ __('settings.common.close') }}"></button>
                    </div>
                    <div class="modal-body">
                        <div class="d-flex flex-wrap gap-2 mb-3">
                            @foreach (['all', 'confirmed', 'pending', 'stale', 'offline', 'overridden'] as $state)
                                @php($label = __('settings.strategies.'.$state))
                                <button type="button" class="btn btn-sm {{ $complianceState === $state ? 'btn-primary' : 'btn-outline-secondary' }}"
                                        wire:click="setComplianceState('{{ $state }}')">
                                    {{ $label }}@if ($state !== 'all') ({{ $complianceSummary['counts'][$state] }})@endif
                                </button>
                            @endforeach
                        </div>
                        @php($complianceTotal = $complianceState === 'all' ? array_sum($complianceSummary['counts']) : ($complianceSummary['counts'][$complianceState] ?? 0))
                        @if ($complianceTotal > count($complianceDevices))
                            <div class="alert alert-info py-2 fs-13">{{ __('settings.strategies.showing', ['shown' => count($complianceDevices), 'total' => $complianceTotal]) }}</div>
                        @endif
                        <div class="table-responsive">
                            <table class="table table-sm table-hover align-middle mb-0">
                                <thead><tr><th>{{ __('settings.common.device') }}</th><th>{{ __('settings.strategies.state') }}</th><th>{{ __('settings.strategies.last_online') }}</th><th>{{ __('settings.notifications.sent') }}</th><th>{{ __('settings.strategies.confirmed') }}</th></tr></thead>
                                <tbody>
                                @forelse ($complianceDevices as $device)
                                    <tr wire:key="compliance-{{ $device['id'] }}-{{ $device['state'] }}">
                                        <td><strong>{{ $device['rustdesk_id'] }}</strong><small class="d-block text-muted">{{ $device['label'] }}</small></td>
                                        <td>
                                            @php($tone = ['confirmed' => 'success', 'pending' => 'info', 'stale' => 'warning', 'offline' => 'secondary', 'overridden' => 'secondary'][$device['state']])
                                            <span class="badge bg-{{ $tone }}-subtle text-{{ $tone }} text-capitalize">{{ __('settings.strategies.'.$device['state']) }}</span>
                                        </td>
                                        <td class="text-nowrap">{{ $device['last_online'] }}</td>
                                        <td class="text-nowrap">{{ $device['sent'] }}</td>
                                        <td class="text-nowrap">{{ $device['confirmed'] }}</td>
                                    </tr>
                                @empty
                                    <tr><td colspan="5" class="text-center text-muted py-4">{{ __('settings.strategies.no_state') }}</td></tr>
                                @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <div class="modal-footer"><button type="button" class="btn btn-light" wire:click="closeCompliance">{{ __('settings.common.close') }}</button></div>
                </div>
            </div>
        </div>
        <div class="modal-backdrop fade show"></div>
    @endif
</div>
