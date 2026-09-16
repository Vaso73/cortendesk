<div>
    @php
        $resourceLabels = [
            'device' => __('identity.permissions.resources.device'),
            'user' => __('identity.permissions.resources.user'),
            'group' => __('identity.permissions.resources.group'),
            'strategy' => __('identity.permissions.resources.strategy'),
            'address_book' => __('identity.permissions.resources.address_book'),
            'audit' => __('identity.permissions.resources.audit'),
        ];
        $levelLabels = ['none' => __('identity.common.none'), 'r' => __('identity.common.read'), 'rw' => __('identity.common.read_write')];
    @endphp

    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center gap-2 flex-wrap">
            <div>
                <h4 class="header-title">{{ __('identity.api_tokens.title') }}</h4>
                <p class="rd-card-sub mb-0">{{ __('identity.api_tokens.intro', ['path' => '/api/v1/…', 'docs' => 'docs/admin-api.md']) }}</p>
            </div>
            @if (auth()->user()?->consoleAllows('token', 'rw'))
                <div class="rd-card-actions">
                    <button type="button" class="btn btn-primary" wire:click="create">
                        <i class="ri-add-line"></i>{{ __('identity.api_tokens.new') }}
                    </button>
                </div>
            @endif
        </div>

            {{-- One-time plaintext reveal --}}
            @if ($plaintext)
                <div class="rd-toolbar">
                <div class="alert alert-success mb-0 w-100">
                    <div class="d-flex justify-content-between align-items-start">
                        <div class="me-2">
                            <strong><i class="ri-check-line me-1"></i>{{ __('identity.api_tokens.created') }}</strong>
                            <div class="fs-13">{{ __('identity.api_tokens.copy_once') }}</div>
                        </div>
                        <button type="button" class="btn-close" wire:click="dismissPlaintext" aria-label="{{ __('identity.common.dismiss') }}"></button>
                    </div>
                    <div class="input-group input-group-sm mt-2">
                        <input type="text" class="form-control font-monospace" readonly value="{{ $plaintext }}">
                        <button class="btn btn-light" type="button" aria-label="{{ __('identity.common.copy') }}"
                                onclick="rdCopyPrevious(this)">
                            <i class="ri-file-copy-line"></i>
                        </button>
                    </div>
                </div>
                </div>
            @endif

            {{-- Desktop table --}}
            <div class="table-responsive d-none d-md-block">
                <table class="table table-hover table-centered mb-0">
                    <thead>
                    <tr>
                        <th>{{ __('identity.common.name') }}</th>
                        <th>{{ __('identity.common.permissions') }}</th>
                        <th>{{ __('identity.api_tokens.created_by') }}</th>
                        <th>{{ __('identity.api_tokens.last_used') }}</th>
                        <th>{{ __('identity.common.expires') }}</th>
                        <th class="text-end">{{ __('identity.common.action') }}</th>
                    </tr>
                    </thead>
                    <tbody>
                    @forelse ($tokens as $token)
                        <tr wire:key="t{{ $token->id }}">
                            <td>
                                <div class="rd-cell rd-tone-amber">
                                    <span class="rd-avatar"><i class="ri-key-2-line"></i></span>
                                    <div class="min-width-0">
                                        <span class="rd-cell-title">{{ $token->name }}</span>
                                        <span class="rd-cell-sub rd-mono">{{ $token->token_prefix }}…</span>
                                    </div>
                                </div>
                            </td>
                            <td>
                                @foreach ($token->permissions as $res => $lvl)
                                    @if ($lvl !== 'none')
                                        <span class="badge {{ $lvl === 'rw' ? 'bg-primary-subtle text-primary' : 'bg-secondary-subtle text-secondary' }}">
                                            {{ $resourceLabels[$res] ?? $res }}: {{ $levelLabels[$lvl] ?? $lvl }}
                                        </span>
                                    @endif
                                @endforeach
                            </td>
                            <td>{{ $token->user?->username ?? '—' }}</td>
                            <td>
                                @if ($token->last_used_at)
                                    <span title="{{ $token->last_used_at }}">{{ $token->last_used_at->diffForHumans() }}</span>
                                @else
                                    <span class="text-muted">{{ __('identity.common.never') }}</span>
                                @endif
                            </td>
                            <td>
                                @if ($token->expires_at)
                                    <span class="{{ $token->isExpired() ? 'text-danger' : '' }}" title="{{ $token->expires_at }}">
                                        {{ $token->expires_at->format('Y-m-d') }}
                                    </span>
                                @else
                                    <span class="text-muted">{{ __('identity.common.never') }}</span>
                                @endif
                            </td>
                            <td class="text-end rd-rowact">
                                @if (auth()->user()?->consoleAllows('token', 'rw'))
                                    <a href="javascript:void(0);" class="text-danger"
                                       wire:click="revoke({{ $token->id }})"
                                       wire:confirm="{{ __('identity.api_tokens.revoke_confirm', ['name' => $token->name]) }}">{{ __('identity.common.revoke') }}</a>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="rd-empty-cell">
                                <div class="rd-empty">
                                    <div class="rd-empty-icon"><i class="ri-key-2-line"></i></div>
                                    <p class="rd-empty-title">{{ __('identity.api_tokens.empty') }}</p>
                                    <p class="rd-empty-text">{{ __('identity.api_tokens.empty_help') }}</p>
                                    @if (auth()->user()?->consoleAllows('token', 'rw'))
                                        <button type="button" class="btn btn-sm btn-outline-light" wire:click="create">{{ __('identity.api_tokens.new') }}</button>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>

            {{-- Mobile card list --}}
            <div class="d-md-none rd-cardlist">
                @forelse ($tokens as $token)
                    <div class="rd-mini" wire:key="mt{{ $token->id }}">
                            <div class="rd-mini-head">
                                <div class="min-width-0">
                                    <span class="rd-mini-title text-truncate">{{ $token->name }}</span>
                                    <span class="rd-mini-sub rd-mono">{{ $token->token_prefix }}…</span>
                                </div>
                                @if (auth()->user()?->consoleAllows('token', 'rw'))
                                    <a href="javascript:void(0);" class="rd-iconbtn text-danger flex-shrink-0" title="{{ __('identity.common.revoke') }}"
                                       wire:click="revoke({{ $token->id }})"
                                       wire:confirm="{{ __('identity.api_tokens.revoke_confirm', ['name' => $token->name]) }}">
                                        <i class="ri-delete-bin-line"></i>
                                    </a>
                                @endif
                            </div>
                            <div class="mt-2">
                                @foreach ($token->permissions as $res => $lvl)
                                    @if ($lvl !== 'none')
                                        <span class="badge {{ $lvl === 'rw' ? 'bg-primary-subtle text-primary' : 'bg-secondary-subtle text-secondary' }}">
                                            {{ $resourceLabels[$res] ?? $res }}: {{ $levelLabels[$lvl] ?? $lvl }}
                                        </span>
                                    @endif
                                @endforeach
                            </div>
                            <span class="rd-mini-sub mt-2">
                                {{ __('identity.api_tokens.last_used_value', ['value' => $token->last_used_at ? $token->last_used_at->diffForHumans() : __('identity.common.never')]) }} ·
                                {{ __('identity.api_tokens.expires_value', ['value' => $token->expires_at ? $token->expires_at->format('Y-m-d') : __('identity.common.never')]) }}
                            </span>
                    </div>
                @empty
                    <div class="rd-empty">
                        <div class="rd-empty-icon"><i class="ri-key-2-line"></i></div>
                        <p class="rd-empty-title">{{ __('identity.api_tokens.empty') }}</p>
                        @if (auth()->user()?->consoleAllows('token', 'rw'))
                            <button type="button" class="btn btn-sm btn-outline-light" wire:click="create">{{ __('identity.api_tokens.new') }}</button>
                        @endif
                    </div>
                @endforelse
            </div>
    </div>

    {{-- Create modal --}}
    @if ($showModal)
        <div class="modal fade show d-block" tabindex="-1" role="dialog" aria-modal="true">
            <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
                <div class="modal-content">
                    <form wire:submit="save">
                        <div class="modal-header">
                            <h5 class="modal-title">{{ __('identity.api_tokens.modal') }}</h5>
                            <button type="button" class="btn-close" wire:click="closeModal" aria-label="{{ __('identity.common.close') }}"></button>
                        </div>
                        <div class="modal-body">
                            <div class="mb-3">
                                <label class="form-label" for="at-name">{{ __('identity.common.name') }} <span class="text-danger">*</span></label>
                                <input type="text" id="at-name" class="form-control @error('name') is-invalid @enderror"
                                       wire:model="name" placeholder="{{ __('identity.api_tokens.name_placeholder') }}" autocomplete="off">
                                @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>

                            <div class="mb-3">
                                <label class="form-label" for="at-expires">{{ __('identity.api_tokens.expires_days') }}</label>
                                <input type="number" id="at-expires" min="1" max="3650" style="max-width:160px;"
                                       class="form-control @error('expiresDays') is-invalid @enderror"
                                       wire:model="expiresDays" placeholder="{{ __('identity.api_tokens.never_placeholder') }}">
                                @error('expiresDays') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                <div class="form-text">{{ __('identity.api_tokens.expires_help') }}</div>
                            </div>

                            <label class="form-label">{{ __('identity.common.permissions') }}</label>
                            @unless (auth()->user()?->is_admin)
                                <div class="form-text mb-1">{{ __('identity.api_tokens.permission_ceiling') }}</div>
                            @endunless
                            @error('permissions') <div class="text-danger fs-13 mb-1">{{ $message }}</div> @enderror
                            {{-- Desktop: radio grid --}}
                            <div class="table-responsive d-none d-md-block">
                                <table class="table table-sm table-centered mb-0">
                                    <thead>
                                    <tr>
                                        <th>{{ __('identity.permissions.resource') }}</th>
                                        <th class="text-center">{{ __('identity.common.none') }}</th>
                                        <th class="text-center">{{ __('identity.common.read') }}</th>
                                        <th class="text-center">{{ __('identity.common.read_write') }}</th>
                                    </tr>
                                    </thead>
                                    <tbody>
                                    @foreach ($resources as $res)
                                        <tr wire:key="perm-{{ $res }}">
                                            <td>{{ $resourceLabels[$res] ?? $res }}</td>
                                            @foreach ($levels as $lvl)
                                                <td class="text-center">
                                                    <input class="form-check-input" type="radio"
                                                           aria-label="{{ ($resourceLabels[$res] ?? $res).' '.($levelLabels[$lvl] ?? $lvl) }}"
                                                           wire:model="permissions.{{ $res }}" value="{{ $lvl }}">
                                                </td>
                                            @endforeach
                                        </tr>
                                    @endforeach
                                    </tbody>
                                </table>
                            </div>

                            {{-- Mobile: one select per resource (four radio columns do not fit 390px) --}}
                            <div class="d-md-none">
                                @foreach ($resources as $res)
                                    <div class="mb-2" wire:key="mperm-{{ $res }}">
                                        <label class="form-label mb-1" for="at-perm-{{ $res }}">{{ $resourceLabels[$res] ?? $res }}</label>
                                        <select id="at-perm-{{ $res }}" class="form-select" wire:model="permissions.{{ $res }}">
                                            @foreach ($levels as $lvl)
                                                <option value="{{ $lvl }}">{{ $levelLabels[$lvl] ?? $lvl }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-light" wire:click="closeModal">{{ __('identity.common.cancel') }}</button>
                            <button type="submit" class="btn btn-primary">
                                <span wire:loading.remove wire:target="save"><i class="ri-key-2-line me-1"></i>{{ __('identity.api_tokens.create') }}</span>
                                <span wire:loading wire:target="save">{{ __('identity.api_tokens.creating') }}</span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        <div class="modal-backdrop fade show"></div>
    @endif
</div>
