<div>
    {{-- ---------------------------------------------------------------- Profile --}}
    <div class="card">
        <div class="card-header">
            <h4 class="header-title">{{ __('auth.account.profile') }}</h4>
        </div>
        <div class="card-body">
            {{-- Identity strip: who you are signed in as, before the fields that change it. --}}
            <div class="rd-cell {{ $user->is_admin ? 'rd-tone-accent' : 'rd-tone-purple' }} mb-3">
                <span class="rd-avatar rd-avatar-lg">{{ strtoupper(substr($user->username, 0, 1)) }}</span>
                <div class="min-width-0">
                    <span class="rd-cell-title">{{ $user->name ?: $user->username }}</span>
                    <span class="rd-cell-sub">
                        {{ $user->email ?: __('auth.account.no_email') }}
                        @if ($user->isSsoLinked())
                            · {{ __('auth.account.signed_in_with_sso') }}
                        @endif
                    </span>
                </div>
            </div>

            @if ($profileSaved)
                <div class="alert alert-success py-2" wire:poll.4s="$set('profileSaved', false)">
                    <i class="ri-check-line me-1"></i>{{ __('auth.account.profile_saved') }}
                </div>
            @endif

            <form wire:submit="saveProfile">
                <div class="mb-3">
                    <label class="form-label">{{ __('auth.account.username') }}</label>
                    <input type="text" class="form-control" value="{{ $user->username }}" disabled>
                    <div class="form-text">
                        {{ __('auth.account.username_help') }}
                    </div>
                </div>

                <div class="mb-3">
                    <label for="account-name" class="form-label">{{ __('auth.account.display_name') }}</label>
                    <input type="text" id="account-name" class="form-control @error('name') is-invalid @enderror"
                           wire:model="name" placeholder="{{ __('auth.account.name_placeholder') }}">
                    @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>

                <div class="mb-3">
                    <label for="account-email" class="form-label">{{ __('auth.account.email') }}</label>
                    <input type="email" id="account-email" class="form-control @error('email') is-invalid @enderror"
                           wire:model="email" placeholder="{{ __('auth.account.email_placeholder') }}">
                    @error('email') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    @if ($user->isSsoLinked())
                        <div class="form-text">
                            <i class="ri-information-line me-1"></i>{{ __('auth.account.sso_profile_help') }}
                        </div>
                    @endif
                </div>

                <button type="submit" class="btn btn-primary">
                    <i class="ri-save-line me-1"></i>{{ __('auth.account.save_changes') }}
                </button>
            </form>
        </div>
    </div>

    {{-- --------------------------------------------------------------- Password --}}
    @if ($user->isSsoProvisioned())
        <div class="card">
            <div class="card-header">
                <h4 class="header-title">{{ __('auth.account.password') }}</h4>
            </div>
            <div class="card-body">
                <div class="d-flex align-items-start gap-2 rd-inset mb-0">
                    <i class="ri-shield-user-line fs-20 text-primary"></i>
                    <div class="text-muted">
                        {{ __('auth.account.sso_password_help') }}
                    </div>
                </div>
            </div>
        </div>
    @else
        <div class="card">
            <div class="card-header">
                <h4 class="header-title">{{ __('auth.account.change_password') }}</h4>
            </div>
            <div class="card-body">
                @if ($passwordSaved)
                    <div class="alert alert-success py-2" wire:poll.4s="$set('passwordSaved', false)">
                        <i class="ri-check-line me-1"></i>{{ __('auth.account.password_changed') }}
                    </div>
                @endif

                <form wire:submit="updatePassword">
                    <div class="mb-3">
                        <label for="current-password" class="form-label">{{ __('auth.account.current_password') }}</label>
                        <input type="password" id="current-password" autocomplete="current-password"
                               class="form-control @error('currentPassword') is-invalid @enderror"
                               wire:model="currentPassword">
                        @error('currentPassword') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div class="mb-3">
                        <label for="new-password" class="form-label">{{ __('auth.account.new_password') }}</label>
                        <input type="password" id="new-password" autocomplete="new-password"
                               class="form-control @error('password') is-invalid @enderror"
                               wire:model="password">
                        @error('password') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        <div class="form-text">{{ __('auth.password_reset.minimum') }}</div>
                    </div>

                    <div class="mb-3">
                        <label for="confirm-password" class="form-label">{{ __('auth.account.confirm_password') }}</label>
                        <input type="password" id="confirm-password" autocomplete="new-password"
                               class="form-control @error('passwordConfirmation') is-invalid @enderror"
                               wire:model="passwordConfirmation">
                        @error('passwordConfirmation') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <button type="submit" class="btn btn-outline-primary">
                        <i class="ri-key-2-line me-1"></i>{{ __('auth.account.change_password') }}
                    </button>
                </form>
            </div>
        </div>
    @endif
</div>
