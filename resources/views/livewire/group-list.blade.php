<div>
    <div class="card">

            {{-- Tabs --}}
            <ul class="nav nav-tabs nav-bordered rd-tabbar">
                <li class="nav-item">
                    <a href="javascript:void(0);" class="nav-link {{ $tab === 'devices' ? 'active' : '' }}"
                       wire:click="setTab('devices')">
                        <i class="ri-computer-line me-1"></i>{{ __('devices.groups.device_groups') }}
                        <span class="badge bg-secondary-subtle text-secondary ms-1">{{ $deviceGroups->count() }}</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="javascript:void(0);" class="nav-link {{ $tab === 'users' ? 'active' : '' }}"
                       wire:click="setTab('users')">
                        <i class="ri-group-line me-1"></i>{{ __('devices.groups.user_groups') }}
                        <span class="badge bg-secondary-subtle text-secondary ms-1">{{ $userGroups->count() }}</span>
                    </a>
                </li>
            </ul>

            @php
                $current = $tab === 'users' ? $userGroups : $deviceGroups;
                $countLabel = $tab === 'users' ? __('devices.groups.users') : __('devices.groups.devices');
                $deleteConfirm = $tab === 'users'
                    ? __('devices.groups.delete_user_confirm')
                    : __('devices.groups.delete_device_confirm');
            @endphp

            {{-- Toolbar --}}
            <div class="rd-toolbar">
                <div>
                    <h4 class="header-title">{{ $tab === 'users' ? __('devices.groups.user_groups') : __('devices.groups.device_groups') }}</h4>
                    <p class="rd-card-sub mb-0">
                        {{ $tab === 'users'
                            ? __('devices.groups.user_intro')
                            : __('devices.groups.device_intro') }}
                    </p>
                </div>
                <div class="rd-toolbar-actions">
                    <button type="button" class="btn btn-primary" wire:click="create('{{ $tab }}')">
                        <i class="ri-add-line"></i>{{ __('devices.groups.add_group') }}
                    </button>
                </div>
            </div>

            {{-- Desktop table (md and up) --}}
            <div class="table-responsive d-none d-md-block">
                <table class="table table-hover table-centered mb-0">
                    <thead>
                    <tr>
                        <th>{{ __('devices.common.name') }}</th>
                        <th>{{ __('devices.common.note') }}</th>
                        <th>{{ $countLabel }}</th>
                        @if ($tab === 'users')
                            <th>{{ __('devices.groups.device_access') }}</th>
                        @endif
                        <th>{{ __('devices.common.created') }}</th>
                        <th class="text-end">{{ __('devices.common.action') }}</th>
                    </tr>
                    </thead>
                    <tbody>
                    @forelse ($current as $group)
                        <tr wire:key="g-{{ $tab }}-{{ $group->id }}">
                            <td class="fw-semibold">{{ $group->name }}</td>
                            <td>{{ $group->note ?: '—' }}</td>
                            <td>
                                <span class="badge bg-secondary-subtle text-secondary">
                                    {{ $tab === 'users' ? $group->users_count : $group->devices_count }}
                                </span>
                            </td>
                            @if ($tab === 'users')
                                <td>
                                    @forelse ($group->deviceGroups as $dg)
                                        <span class="badge bg-primary-subtle text-primary me-1">{{ $dg->name }}</span>
                                    @empty
                                        <span class="text-muted">—</span>
                                    @endforelse
                                </td>
                            @endif
                            <td>
                                <span title="{{ $group->created_at }}">{{ $group->created_at?->format('Y-m-d') }}</span>
                            </td>
                            <td class="text-end rd-rowact">
                                <a href="javascript:void(0);" class="rd-act me-2"
                                   wire:click="edit('{{ $tab }}', {{ $group->id }})">{{ __('devices.common.edit') }}</a>
                                <a href="javascript:void(0);" class="text-danger"
                                   wire:click="deleteGroup('{{ $tab }}', {{ $group->id }})"
                                   wire:confirm="{{ $deleteConfirm }}">{{ __('devices.common.delete') }}</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="{{ $tab === 'users' ? 6 : 5 }}" class="rd-empty-cell">
                                <div class="rd-empty">
                                    <div class="rd-empty-icon"><i class="{{ $tab === 'users' ? 'ri-group-line' : 'ri-folders-line' }}"></i></div>
                                    <p class="rd-empty-title">
                                        {{ $tab === 'users' ? __('devices.groups.no_user_groups_click') : __('devices.groups.no_device_groups_click') }}
                                    </p>
                                    <p class="rd-empty-text">
                                        {{ $tab === 'users'
                                            ? __('devices.groups.user_empty_help')
                                            : __('devices.groups.device_empty_help') }}
                                    </p>
                                    <button type="button" class="btn btn-sm btn-outline-light" wire:click="create('{{ $tab }}')">{{ __('devices.groups.add_group') }}</button>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>

            {{-- Mobile card list (below md) --}}
            <div class="d-md-none rd-cardlist">
                @forelse ($current as $group)
                    <div class="rd-mini" wire:key="mg-{{ $tab }}-{{ $group->id }}">
                            <div class="rd-mini-head">
                                <div class="min-width-0">
                                    <span class="rd-mini-title">{{ $group->name }}</span>
                                    <span class="rd-mini-sub">{{ $group->note ?: __('devices.groups.no_note') }}</span>
                                </div>
                                <span class="badge bg-secondary-subtle text-secondary">
                                    {{ trans_choice($tab === 'users' ? 'devices.count.user' : 'devices.count.device', \App\Livewire\DeviceList::pluralSelector($tab === 'users' ? $group->users_count : $group->devices_count), ['count' => $tab === 'users' ? $group->users_count : $group->devices_count]) }}
                                </span>
                            </div>
                            @if ($tab === 'users' && $group->deviceGroups->isNotEmpty())
                                <div class="mt-2">
                                    @foreach ($group->deviceGroups as $dg)
                                        <span class="badge bg-primary-subtle text-primary me-1">{{ $dg->name }}</span>
                                    @endforeach
                                </div>
                            @endif
                            <div class="rd-mini-foot">
                                <span class="rd-mini-sub">{{ __('devices.groups.created_on', ['date' => $group->created_at?->format('Y-m-d')]) }}</span>
                                <div class="rd-mini-acts">
                                    <a href="javascript:void(0);" class="rd-iconbtn" title="{{ __('devices.common.edit') }}"
                                       wire:click="edit('{{ $tab }}', {{ $group->id }})"><i class="ri-pencil-line"></i></a>
                                    <a href="javascript:void(0);" class="rd-iconbtn text-danger" title="{{ __('devices.common.delete') }}"
                                       wire:click="deleteGroup('{{ $tab }}', {{ $group->id }})"
                                       wire:confirm="{{ $deleteConfirm }}"><i class="ri-delete-bin-line"></i></a>
                                </div>
                            </div>
                    </div>
                @empty
                    <div class="rd-empty">
                        <div class="rd-empty-icon"><i class="{{ $tab === 'users' ? 'ri-group-line' : 'ri-folders-line' }}"></i></div>
                        <p class="rd-empty-title">
                            {{ $tab === 'users' ? __('devices.groups.no_user_groups_tap') : __('devices.groups.no_device_groups_tap') }}
                        </p>
                        <button type="button" class="btn btn-sm btn-outline-light" wire:click="create('{{ $tab }}')">{{ __('devices.groups.add_group') }}</button>
                    </div>
                @endforelse
            </div>
    </div>

    {{-- Create / Edit modal (plain Bootstrap markup, toggled by Livewire) --}}
    @if ($showModal)
        {{-- Scrollable: the two checkbox blocks below grow with the number of groups,
             and without a bounded body "Create Group" is pushed off a 390px screen. --}}
        <div class="modal fade show d-block" tabindex="-1" role="dialog" aria-modal="true">
            <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
                <div class="modal-content">
                    <form wire:submit="save">
                        <div class="modal-header">
                            <h5 class="modal-title">
                                {{ $modalType === 'users'
                                    ? ($editing ? __('devices.groups.edit_user_group') : __('devices.groups.add_user_group'))
                                    : ($editing ? __('devices.groups.edit_device_group') : __('devices.groups.add_device_group')) }}
                            </h5>
                            <button type="button" class="btn-close" wire:click="closeModal" aria-label="{{ __('devices.common.close') }}"></button>
                        </div>
                        <div class="modal-body">
                            <div class="mb-3">
                                <label class="form-label" for="gl-name">{{ __('devices.common.name') }} <span class="text-danger">*</span></label>
                                <input type="text" id="gl-name" class="form-control @error('name') is-invalid @enderror"
                                       wire:model="name" autocomplete="off">
                                @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            <div class="{{ $modalType === 'users' ? 'mb-3' : 'mb-0' }}">
                                <label class="form-label" for="gl-note">{{ __('devices.common.note') }}</label>
                                <textarea id="gl-note" rows="3" class="form-control @error('note') is-invalid @enderror"
                                          wire:model="note"></textarea>
                                @error('note') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>

                            {{-- Device-group access (user groups only) --}}
                            @if ($modalType === 'users')
                                <div class="mb-3">
                                    <label class="form-label">{{ __('devices.groups.device_group_access') }}</label>
                                    <p class="text-muted fs-13 mb-2">{{ __('devices.groups.device_group_access_help') }}</p>
                                    @forelse ($grantableDeviceGroups as $dg)
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" id="gl-dg-{{ $dg->id }}"
                                                   value="{{ $dg->id }}" wire:model="device_group_ids">
                                            <label class="form-check-label" for="gl-dg-{{ $dg->id }}">{{ $dg->name }}</label>
                                        </div>
                                    @empty
                                        <p class="text-muted fs-13">{{ auth()->user()?->is_admin ? __('devices.groups.no_device_groups') : __('devices.groups.no_grantable_device_groups') }}</p>
                                    @endforelse
                                </div>
                            @endif

                            {{-- Accessed from: which user groups may see this group (B4) --}}
                            <div class="mb-0">
                                <label class="form-label">{{ __('devices.groups.accessed_from') }}</label>
                                <p class="text-muted fs-13 mb-2">
                                    @if ($modalType === 'users')
                                        {{ __('devices.groups.user_access_help') }}
                                    @else
                                        {{ __('devices.groups.device_access_help') }}
                                    @endif
                                </p>
                                @php $accessorOptions = $userGroups->where('id', '!=', $editing ?? 0); @endphp
                                @forelse ($accessorOptions as $ug)
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="gl-af-{{ $ug->id }}"
                                               value="{{ $ug->id }}" wire:model="accessor_group_ids">
                                        <label class="form-check-label" for="gl-af-{{ $ug->id }}">{{ $ug->name }}</label>
                                    </div>
                                @empty
                                    <p class="text-muted fs-13 mb-0">{{ __('devices.groups.no_other_user_groups') }}</p>
                                @endforelse
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-light" wire:click="closeModal">{{ __('devices.common.cancel') }}</button>
                            <button type="submit" class="btn btn-primary">
                                <span wire:loading.remove wire:target="save">{{ $editing ? __('devices.common.save_changes') : __('devices.groups.create_group') }}</span>
                                <span wire:loading wire:target="save">{{ __('devices.common.saving') }}</span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        <div class="modal-backdrop fade show"></div>
    @endif
</div>
