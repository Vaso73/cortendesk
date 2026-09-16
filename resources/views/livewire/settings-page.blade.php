<div>
    @php $canManageSettings = auth()->user()?->consoleAllows('setting', 'rw'); @endphp

    @unless ($canManageSettings)
        <div class="alert alert-info py-2">
            <i class="ri-eye-line me-1"></i>{{ __('settings.page.view_only') }}
        </div>
    @endunless

    @php
        $mailSvc = app(\App\Services\MailSettings::class);
        $mailBroken = \App\Support\LoginEmailVerification::isActive() && ! $mailSvc->isHealthy();
    @endphp

    @if ($mailBroken || session('mail_broken'))
        <div class="alert alert-danger">
            <i class="ri-mail-close-line me-1"></i><strong>{{ __('settings.page.email_failing') }}</strong>
            {{ __('settings.page.email_failing_help') }}
        </div>
    @endif

    @if ($saved)
        <div class="alert alert-success py-2" wire:poll.4s="$set('saved', false)">
            <i class="ri-check-line me-1"></i>{{ __('settings.page.saved') }}
        </div>
    @endif

    {{-- Nothing was written if any field failed — say so where it can be seen.
         save() also jumps $tab to the failing field, since one giant validate()
         covers every tab and the error may otherwise render off-screen. --}}
    @if ($errors->any())
        <div class="alert alert-danger py-2">
            <i class="ri-error-warning-line me-1"></i>{{ __('settings.page.not_saved') }}
        </div>
    @endif

    {{-- Tab nav. Active tab lives in the $tab Livewire prop (survives save re-renders). --}}
    @php
        $tabs = [
            'server' => ['ri-server-line', __('settings.tabs.server')],
            'client' => ['ri-download-2-line', __('settings.tabs.client')],
            'security' => ['ri-shield-keyhole-line', __('settings.tabs.security')],
            'sso' => ['ri-shield-user-line', __('settings.tabs.sso')],
            'email' => ['ri-mail-send-line', __('settings.tabs.email')],
            'notifications' => ['ri-notification-3-line', __('settings.tabs.notifications')],
            'maintenance' => ['ri-database-2-line', __('settings.tabs.maintenance')],
        ];
    @endphp
    {{-- The label stays visible at every width. Six tabs do not fit across a
         390px screen, but .rd-tabbar is a horizontal scroller (section 17 of
         cortendesk.css) so the strip slides instead of wrapping — and six
         unlabelled icons, two of them shields, are not a navigation. Livewire
         morphs this list rather than replacing it, so the scroll position
         survives a tab change. --}}
    <ul class="nav nav-tabs nav-bordered rd-tabbar mb-3">
        @foreach ($tabs as $key => [$icon, $label])
            <li class="nav-item">
                <a href="#" wire:click.prevent="$set('tab', '{{ $key }}')"
                   class="nav-link {{ $tab === $key ? 'active' : '' }}">
                    <i class="{{ $icon }} me-1"></i><span>{{ $label }}</span>
                </a>
            </li>
        @endforeach
    </ul>

    <div class="tab-content">

        {{-- ============================ SERVER ============================ --}}
        <div class="tab-pane {{ $tab === 'server' ? 'show active' : '' }}">
            <div class="row">
                <div class="col-lg-7">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title mb-0">{{ __('settings.server.title') }}</h5>
                        </div>
                        <div class="card-body">
                            <form wire:submit="save">
                                <div class="mb-3">
                                    <label class="form-label">{{ __('settings.server.id') }}</label>
                                    <input type="text" class="form-control" wire:model="idServer" placeholder="e.g. hbbs.example.com:21116">
                                    <div class="form-text">{{ __('settings.server.id_help') }}</div>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">{{ __('settings.server.relay') }}</label>
                                    <input type="text" class="form-control" wire:model="relayServer" placeholder="e.g. hbbs.example.com:21117">
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">{{ __('settings.server.public_key') }}</label>
                                    <input type="text" class="form-control rd-mono" wire:model="publicKey" placeholder="{{ __('settings.server.public_key_placeholder') }}">
                                    <div class="form-text">{!! __('settings.server.public_key_help') !!}</div>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">{{ __('settings.server.build_url') }}</label>
                                    <input type="text" class="form-control @error('rdgenUrl') is-invalid @enderror"
                                           wire:model="rdgenUrl" placeholder="https://rdgen.crayoneater.org">
                                    @error('rdgenUrl') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                    <div class="form-text">{{ __('settings.server.build_url_help') }}</div>
                                </div>
                                <div class="mb-3">
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" role="switch" id="downloadsOnLogin"
                                               wire:model="downloadsOnLogin">
                                        <label class="form-check-label" for="downloadsOnLogin">{{ __('settings.server.downloads_login') }}</label>
                                    </div>
                                    <div class="form-text">{{ __('settings.server.downloads_login_help') }}</div>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">{{ __('settings.server.online_window') }}</label>
                                    <input type="number" class="form-control @error('onlineWindow') is-invalid @enderror"
                                           wire:model="onlineWindow" min="20" max="600" style="max-width: 140px;">
                                    @error('onlineWindow') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                    <div class="form-text">{{ __('settings.server.online_window_help') }}</div>
                                </div>
                                <div class="mb-3">
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" role="switch" id="requireDeviceApproval"
                                               wire:model="requireDeviceApproval">
                                        <label class="form-check-label" for="requireDeviceApproval">{{ __('settings.server.require_approval') }}</label>
                                    </div>
                                    <div class="form-text">{{ __('settings.server.require_approval_help') }}</div>
                                </div>
                                <button type="submit" class="btn btn-primary" @disabled(! $canManageSettings)><i class="ri-save-line me-1"></i>{{ __('settings.common.save_settings') }}</button>
                            </form>
                        </div>
                    </div>

                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title mb-0">{{ __('settings.relays.title') }}</h5>
                        </div>
                        <div class="card-body">
                            <p class="text-muted fs-13 mb-3">{{ __('settings.relays.help') }}</p>

                            <form wire:submit="save">
                                {{-- Address takes the whole first line on a phone; the geo tag and
                                     the remove button share the second. The old split put the button
                                     in a col-1, which is a 17px content box at 390px against the
                                     ~45px a button needs — it wrapped under the inputs. col-sm-auto
                                     sizes the button to itself instead of to a twelfth of the row,
                                     so it stays honest at every width. --}}
                                @forelse ($relayServers as $i => $relay)
                                    <div class="row g-2 mb-2 align-items-start" wire:key="relay-{{ $i }}">
                                        <div class="col-12 col-sm">
                                            <input type="text" class="form-control @error('relayServers.'.$i.'.address') is-invalid @enderror"
                                                   wire:model="relayServers.{{ $i }}.address" placeholder="relay.example.com:21117">
                                            @error('relayServers.'.$i.'.address') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                        </div>
                                        <div class="col-8 col-sm-4">
                                            <input type="text" class="form-control @error('relayServers.'.$i.'.geo') is-invalid @enderror"
                                                   wire:model="relayServers.{{ $i }}.geo" placeholder="{{ __('settings.relays.geo') }}">
                                            @error('relayServers.'.$i.'.geo') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                        </div>
                                        <div class="col-4 col-sm-auto d-grid">
                                            <button type="button" class="btn btn-light text-danger" wire:click="removeRelay({{ $i }})"
                                                    title="{{ __('settings.relays.remove') }}" @disabled(! $canManageSettings)><i class="ri-close-line"></i></button>
                                        </div>
                                    </div>
                                @empty
                                    <p class="text-muted fs-13 fst-italic mb-2">{{ __('settings.relays.empty') }}</p>
                                @endforelse

                                <div class="d-flex gap-2 mt-3">
                                    <button type="button" class="btn btn-light" wire:click="addRelay" @disabled(! $canManageSettings)>
                                        <i class="ri-add-line me-1"></i>{{ __('settings.relays.add') }}
                                    </button>
                                    <button type="submit" class="btn btn-primary" @disabled(! $canManageSettings)><i class="ri-save-line me-1"></i>{{ __('settings.common.save_settings') }}</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- ========================= CLIENT SETUP ========================= --}}
        <div class="tab-pane {{ $tab === 'client' ? 'show active' : '' }}">
            <div class="row">
                <div class="col-lg-6">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title mb-0">{{ __('settings.tabs.client') }}</h5>
                        </div>
                        <div class="card-body">
                            <p class="text-muted fs-13">{{ __('settings.client.help') }}</p>

                            <label class="form-label fs-13 text-muted mb-0">{{ __('settings.client.id') }}</label>
                            <div class="input-group input-group-sm mb-2">
                                <input type="text" class="form-control rd-mono" readonly value="{{ $idServer }}">
                                <button class="btn btn-light" type="button" onclick="rdCopyPrevious(this)"><i class="ri-file-copy-line"></i></button>
                            </div>

                            <label class="form-label fs-13 text-muted mb-0">{{ __('settings.client.relay') }}</label>
                            <div class="input-group input-group-sm mb-2">
                                <input type="text" class="form-control rd-mono" readonly value="{{ $relayServer }}">
                                <button class="btn btn-light" type="button" onclick="rdCopyPrevious(this)"><i class="ri-file-copy-line"></i></button>
                            </div>

                            <label class="form-label fs-13 text-muted mb-0">{{ __('settings.client.api') }}</label>
                            <div class="input-group input-group-sm mb-2">
                                <input type="text" class="form-control rd-mono" readonly value="{{ $apiUrl }}">
                                <button class="btn btn-light" type="button" onclick="rdCopyPrevious(this)"><i class="ri-file-copy-line"></i></button>
                            </div>

                            <label class="form-label fs-13 text-muted mb-0">{{ __('settings.client.key') }}</label>
                            <div class="input-group input-group-sm">
                                <input type="text" class="form-control rd-mono" readonly value="{{ $publicKey }}">
                                <button class="btn btn-light" type="button" onclick="rdCopyPrevious(this)"><i class="ri-file-copy-line"></i></button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- =========================== SECURITY =========================== --}}
        <div class="tab-pane {{ $tab === 'security' ? 'show active' : '' }}">
            <div class="row">
                <div class="col-lg-7">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title mb-0">{{ __('settings.security.title') }}</h5>
                        </div>
                        <div class="card-body">
                            <form wire:submit="save">
                                <div class="mb-3">
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" role="switch" id="twoFactorRequired"
                                               wire:model="twoFactorRequired">
                                        <label class="form-check-label" for="twoFactorRequired">{{ __('settings.security.require_all') }}</label>
                                    </div>
                                    <div class="form-text">{{ __('settings.security.require_all_help') }}</div>
                                </div>
                                <div class="mb-3">
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" role="switch" id="twoFactorRequiredAdmins"
                                               wire:model="twoFactorRequiredAdmins" @if($twoFactorRequired) disabled @endif>
                                        <label class="form-check-label" for="twoFactorRequiredAdmins">{{ __('settings.security.require_admins') }}</label>
                                    </div>
                                    <div class="form-text">{{ __('settings.security.require_admins_help') }}</div>
                                </div>

                                {{-- Email sign-in verification sits beside 2FA because it is the
                                     same kind of policy; the relay itself is configured on the Email tab. --}}
                                <div class="mb-3">
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" role="switch" id="emailLoginVerification"
                                               wire:model="emailLoginVerification" @unless($mailEnabled) disabled @endunless>
                                        <label class="form-check-label" for="emailLoginVerification">{{ __('settings.security.email_code') }}</label>
                                    </div>
                                    <div class="form-text">
                                        @if ($mailEnabled)
                                            {{ __('settings.security.email_code_help', ['days' => $emailTrustedDeviceDays]) }}
                                            @if ($usersWithoutEmail > 0)
                                                <span class="text-warning d-block mt-1">
                                                    {{ trans_choice('settings.security.no_email_accounts', $usersWithoutEmail, ['count' => $usersWithoutEmail]) }}
                                                </span>
                                            @else
                                                <span class="d-block mt-1">{{ __('settings.security.mail_fallback') }}</span>
                                            @endif
                                        @else
                                            {{ __('settings.security.configure_email') }}
                                        @endif
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label">{{ __('settings.security.remember_days') }}</label>
                                    <input type="number" class="form-control @error('emailTrustedDeviceDays') is-invalid @enderror"
                                           wire:model="emailTrustedDeviceDays" min="1" max="365" style="max-width: 140px;">
                                    @error('emailTrustedDeviceDays') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                    <div class="form-text">{{ __('settings.security.remember_help') }}</div>
                                </div>

                                <button type="submit" class="btn btn-primary" @disabled(! $canManageSettings)><i class="ri-save-line me-1"></i>{{ __('settings.common.save_settings') }}</button>
                            </form>
                        </div>
                    </div>

                    {{-- API tokens live under Security — stable key keeps it mounted across saves --}}
                    {{-- Nested component: a 403 in the child kills the whole Settings page,
                         so a role with settings but no token access simply does not render it. --}}
                    @if (auth()->user()?->consoleAllows('token', 'r'))
                        @livewire(App\Livewire\ApiTokenManager::class, [], 'api-tokens')
                    @endif
                </div>
            </div>
        </div>

        {{-- ============================= SSO ============================= --}}
        <div class="tab-pane {{ $tab === 'sso' ? 'show active' : '' }}">
            <div class="row">
                <div class="col-lg-8">
                    <div class="card">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h5 class="card-title mb-0">{{ __('settings.sso.title') }}</h5>
                            @if ($oidcEnabled)
                                <span class="badge bg-success-subtle text-success">{{ __('settings.common.enabled') }}</span>
                            @else
                                <span class="badge bg-secondary-subtle text-secondary">{{ __('settings.common.disabled') }}</span>
                            @endif
                        </div>
                        <div class="card-body">
                            <p class="text-muted fs-13">
                                {{ __('settings.sso.intro') }}
                            </p>
                            <div class="mb-3">
                                <input type="text" class="form-control form-control-sm rd-mono"
                                       value="{{ $oidcCallbackUrl }}" readonly onfocus="this.select()">
                            </div>

                            <form wire:submit="save">
                                <div class="mb-3">
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" role="switch" id="oidcEnabled"
                                               wire:model="oidcEnabled">
                                        <label class="form-check-label" for="oidcEnabled">{{ __('settings.sso.enable') }}</label>
                                    </div>
                                    <div class="form-text">{{ __('settings.sso.enable_help') }}</div>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label">{{ __('settings.sso.provider_url') }}</label>
                                    <input type="text" class="form-control @error('oidcDiscoveryUrl') is-invalid @enderror"
                                           wire:model="oidcDiscoveryUrl" placeholder="https://idp.example.com/realms/main">
                                    @error('oidcDiscoveryUrl') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                    <div class="form-text">
                                        {!! __('settings.sso.provider_help') !!}
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">{{ __('settings.sso.client_id') }}</label>
                                        <input type="text" class="form-control" wire:model="oidcClientId">
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">{{ __('settings.sso.client_secret') }}</label>
                                        <input type="password" class="form-control" wire:model="oidcClientSecret"
                                               autocomplete="new-password"
                                               placeholder="{{ $oidcClientSecretSet ? __('settings.common.stored_keep') : __('settings.common.required') }}">
                                        <div class="form-text">{{ __('settings.sso.secret_help') }}</div>
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <button type="button" class="btn btn-sm btn-outline-secondary" wire:click="testOidc"
                                            wire:loading.attr="disabled" wire:target="testOidc" @disabled(! $canManageSettings)>
                                        <i class="ri-plug-line me-1"></i>
                                        <span wire:loading.remove wire:target="testOidc">{{ __('settings.sso.test') }}</span>
                                        <span wire:loading wire:target="testOidc">{{ __('settings.sso.testing') }}</span>
                                    </button>
                                    @if ($oidcTestMessage)
                                        <div class="mt-2 alert {{ $oidcTestOk ? 'alert-success' : 'alert-danger' }} py-2 mb-0 fs-13">
                                            {{ $oidcTestMessage }}
                                        </div>
                                    @endif
                                </div>

                                <hr>

                                <div class="mb-3">
                                    <label class="form-label">{{ __('settings.sso.new_users') }}</label>
                                    <select class="form-select" wire:model="oidcNewUserPolicy">
                                        <option value="deny">{{ __('settings.sso.policy_deny') }}</option>
                                        <option value="pending">{{ __('settings.sso.policy_pending') }}</option>
                                        <option value="active">{{ __('settings.sso.policy_active') }}</option>
                                    </select>
                                    <div class="form-text">
                                        {{ __('settings.sso.policy_help') }}
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" role="switch" id="oidcRequireVerifiedEmail"
                                               wire:model="oidcRequireVerifiedEmail">
                                        <label class="form-check-label" for="oidcRequireVerifiedEmail">{{ __('settings.sso.require_verified') }}</label>
                                    </div>
                                    <div class="form-text">
                                        {{ __('settings.sso.require_verified_help') }}
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">{{ __('settings.sso.default_group') }}</label>
                                        <select class="form-select" wire:model="oidcDefaultGroupId">
                                            <option value="0">{{ __('settings.common.none') }}</option>
                                            @foreach ($userGroups as $group)
                                                <option value="{{ $group->id }}">{{ $group->name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">{{ __('settings.sso.allowed_domains') }}</label>
                                        <input type="text" class="form-control" wire:model="oidcAllowedDomains"
                                               placeholder="example.com, example.org">
                                        <div class="form-text">{{ __('settings.sso.domains_help') }}</div>
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" role="switch" id="oidcDefaultAdmin"
                                               wire:model="oidcDefaultAdmin">
                                        <label class="form-check-label" for="oidcDefaultAdmin">{{ __('settings.sso.default_admin') }}</label>
                                    </div>
                                    <div class="form-text text-warning">
                                        {{ __('settings.sso.default_admin_warning') }}
                                    </div>
                                </div>

                                <hr>

                                <div class="mb-3">
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" role="switch" id="oidcLogoutEnabled"
                                               wire:model="oidcLogoutEnabled">
                                        <label class="form-check-label" for="oidcLogoutEnabled">{{ __('settings.sso.logout_provider') }}</label>
                                    </div>
                                    <div class="form-text">{{ __('settings.sso.logout_help') }}</div>
                                </div>

                                <div class="mb-3">
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" role="switch" id="oidcDisableLocalLogin"
                                               wire:model="oidcDisableLocalLogin">
                                        <label class="form-check-label" for="oidcDisableLocalLogin">{{ __('settings.sso.disable_password') }}</label>
                                    </div>
                                    <div class="form-text">
                                        {!! __('settings.sso.disable_password_help') !!}
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label">{{ __('settings.sso.advanced') }}</label>
                                    <input type="text" class="form-control mb-2" wire:model="oidcScopes" placeholder="openid email profile">
                                    <div class="form-text mb-2">{{ __('settings.sso.scopes_help') }}</div>
                                    <input type="text" class="form-control mb-2" wire:model="oidcButtonLabel" placeholder="{{ __('settings.sso.button_placeholder') }}">
                                    <div class="form-text mb-2">{{ __('settings.sso.button_help') }}</div>
                                    <input type="text" class="form-control" wire:model="oidcPublicBaseUrl"
                                           placeholder="https://idp.example.com">
                                    <div class="form-text">
                                        {{ __('settings.sso.public_url_help') }}
                                    </div>
                                </div>

                                <button type="submit" class="btn btn-primary" @disabled(! $canManageSettings)><i class="ri-save-line me-1"></i>{{ __('settings.common.save_settings') }}</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- ============================ EMAIL ============================ --}}
        <div class="tab-pane {{ $tab === 'email' ? 'show active' : '' }}">
            <div class="row">
                <div class="col-lg-7">
                    <div class="card">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h5 class="card-title mb-0">{{ __('settings.email.title') }}</h5>
                            @if ($smtpEnabled)
                                <span class="badge bg-success-subtle text-success">{{ __('settings.common.enabled') }}</span>
                            @else
                                <span class="badge bg-secondary-subtle text-secondary">{{ __('settings.common.disabled') }}</span>
                            @endif
                        </div>
                        <div class="card-body">
                            <p class="text-muted fs-13">
                                {{ __('settings.email.intro') }}
                            </p>

                            <form wire:submit="save">
                                <div class="mb-3">
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" role="switch" id="smtpEnabled"
                                               wire:model="smtpEnabled">
                                        <label class="form-check-label" for="smtpEnabled">{{ __('settings.email.enable') }}</label>
                                    </div>
                                    <div class="form-text">{{ __('settings.email.enable_help') }}</div>
                                </div>

                                <div class="row">
                                    <div class="col-md-8 mb-3">
                                        <label class="form-label">{{ __('settings.email.host') }}</label>
                                        <input type="text" class="form-control @error('smtpHost') is-invalid @enderror"
                                               wire:model="smtpHost" placeholder="smtp.example.com">
                                        @error('smtpHost') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <label class="form-label">{{ __('settings.email.port') }}</label>
                                        <input type="number" class="form-control @error('smtpPort') is-invalid @enderror"
                                               wire:model="smtpPort" min="1" max="65535">
                                        @error('smtpPort') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label">{{ __('settings.email.encryption') }}</label>
                                    <select class="form-select" wire:model="smtpEncryption" style="max-width: 420px;">
                                        <option value="starttls">{{ __('settings.email.starttls') }}</option>
                                        <option value="ssl">{{ __('settings.email.ssl') }}</option>
                                        <option value="none">{{ __('settings.email.none') }}</option>
                                    </select>
                                    <div class="form-text">
                                        {{ __('settings.email.encryption_help') }}
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">{{ __('settings.email.username') }}</label>
                                        <input type="text" class="form-control @error('smtpUsername') is-invalid @enderror"
                                               wire:model="smtpUsername" autocomplete="off" placeholder="{{ __('settings.common.optional') }}">
                                        @error('smtpUsername') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">{{ __('settings.email.password') }}</label>
                                        <input type="password" class="form-control @error('smtpPassword') is-invalid @enderror"
                                               wire:model="smtpPassword" autocomplete="new-password"
                                               placeholder="{{ $smtpPasswordSet ? __('settings.common.stored_keep') : __('settings.common.optional') }}">
                                        @error('smtpPassword') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                        <div class="form-text">{{ __('settings.email.password_help') }}</div>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">{{ __('settings.email.from_address') }}</label>
                                        <input type="text" class="form-control @error('smtpFromAddress') is-invalid @enderror"
                                               wire:model="smtpFromAddress" placeholder="console@example.com">
                                        @error('smtpFromAddress') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                        <div class="form-text">{{ __('settings.email.from_help') }}</div>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">{{ __('settings.email.from_name') }}</label>
                                        <input type="text" class="form-control @error('smtpFromName') is-invalid @enderror"
                                               wire:model="smtpFromName" placeholder="{{ config('app.name') }}">
                                        @error('smtpFromName') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                    </div>
                                </div>

                                <button type="submit" class="btn btn-primary" @disabled(! $canManageSettings)><i class="ri-save-line me-1"></i>{{ __('settings.common.save_settings') }}</button>
                            </form>

                            <hr>

                            <label class="form-label">{{ __('settings.email.test_label') }}</label>
                            <div class="row g-2 align-items-start">
                                <div class="col-sm-7">
                                    <input type="email" class="form-control" wire:model="smtpTestTo" placeholder="you@example.com">
                                </div>
                                <div class="col-sm-5 d-grid">
                                    <button type="button" class="btn btn-outline-secondary" wire:click="sendTestEmail"
                                            wire:loading.attr="disabled" wire:target="sendTestEmail" @disabled(! $canManageSettings)>
                                        <i class="ri-send-plane-line me-1"></i>
                                        <span wire:loading.remove wire:target="sendTestEmail">{{ __('settings.email.send_test') }}</span>
                                        <span wire:loading wire:target="sendTestEmail">{{ __('settings.email.sending') }}</span>
                                    </button>
                                </div>
                            </div>
                            <div class="form-text">{{ __('settings.email.saved_help') }}</div>
                            @if ($smtpTestMessage)
                                <div class="mt-2 alert {{ $smtpTestOk ? 'alert-success' : 'alert-danger' }} py-2 mb-0 fs-13">
                                    {{ $smtpTestMessage }}
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- ======================== NOTIFICATIONS ======================== --}}
        <div class="tab-pane {{ $tab === 'notifications' ? 'show active' : '' }}">
            <div class="row">
                <div class="col-lg-8">
                    <div class="card">
                        <div class="card-header"><h5 class="card-title mb-0">{{ __('settings.notifications.title') }}</h5></div>
                        <div class="card-body">
                            <p class="text-muted fs-13">{{ __('settings.notifications.intro') }}</p>
                            <form wire:submit="save">
                                <div class="form-check form-switch mb-3">
                                    <input class="form-check-input" type="checkbox" id="appriseEnabled" wire:model="appriseEnabled">
                                    <label class="form-check-label" for="appriseEnabled">{{ __('settings.notifications.enable') }}</label>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">{{ __('settings.notifications.endpoint') }}</label>
                                    <input type="url" class="form-control @error('appriseEndpoint') is-invalid @enderror" wire:model="appriseEndpoint"
                                           autocomplete="off" data-1p-ignore data-lpignore="true"
                                           placeholder="{{ $appriseEndpointSet ? __('settings.common.stored_keep') : 'https://apprise.example.com' }}">
                                    @error('appriseEndpoint')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                    <div class="form-text">{!! __('settings.notifications.endpoint_help') !!}</div>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">{{ __('settings.notifications.mode') }}</label>
                                    <select class="form-select" wire:model.live="appriseMode">
                                        <option value="config">{{ __('settings.notifications.mode_config') }}</option>
                                        <option value="urls">{{ __('settings.notifications.mode_urls') }}</option>
                                    </select>
                                </div>
                                @if ($appriseMode === 'config')
                                    <div class="mb-3"><label class="form-label">{{ __('settings.notifications.config_key') }}</label><input type="password" class="form-control" wire:model="appriseConfigKey"
                                               autocomplete="new-password" data-1p-ignore data-lpignore="true"
                                               placeholder="{{ $appriseConfigKeySet ? __('settings.common.stored_keep') : 'office-alerts' }}"></div>
                                @else
                                    <div class="mb-3"><label class="form-label">{{ __('settings.notifications.urls') }}</label><textarea class="form-control rd-mono" rows="4" wire:model="appriseUrls"
                                                       autocomplete="off" data-1p-ignore data-lpignore="true"
                                                       placeholder="{{ $appriseUrlsSet ? __('settings.common.stored_keep') : __('settings.notifications.one_url') }}"></textarea><div class="form-text">{{ __('settings.notifications.write_only') }}</div></div>
                                @endif
                                <div class="mb-3"><label class="form-label">{{ __('settings.notifications.cooldown') }}</label><input type="number" class="form-control" style="max-width: 140px" min="0" max="1440" wire:model="appriseCooldownMinutes"></div>
                                <div class="mb-3"><label class="form-label">{{ __('settings.notifications.offline_grace') }}</label><input type="number" class="form-control @error('appriseOfflineGraceMinutes') is-invalid @enderror" style="max-width: 140px" min="0" max="1440" wire:model="appriseOfflineGraceMinutes"><div class="form-text">{{ __('settings.notifications.offline_grace_help') }}</div>@error('appriseOfflineGraceMinutes')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
                                <div class="row">
                                    @foreach ($appriseEventLabels as $event => $label)
                                        @php $eventKey = str_replace('.', '_', $event); @endphp
                                        <div class="col-md-6 mb-3">
                                            <div class="form-check form-switch">
                                                <input class="form-check-input" type="checkbox" id="event-{{ str_replace('.', '-', $event) }}" wire:model="appriseEvents.{{ $eventKey }}">
                                                <label class="form-check-label" for="event-{{ str_replace('.', '-', $event) }}">{{ $label }}</label>
                                            </div>
                                            @if (str_starts_with($event, 'device.'))
                                                <select class="form-select form-select-sm mt-2" wire:model.live="appriseScopes.{{ $eventKey }}">
                                                    <option value="all">{{ __('settings.notifications.all_devices') }}</option>
                                                    <option value="selected">{{ __('settings.notifications.selected') }}</option>
                                                </select>
                                                @if (($appriseScopes[$eventKey] ?? 'all') === 'selected')
                                                    <select class="form-select form-select-sm mt-2" multiple wire:model="appriseScopeGroups.{{ $eventKey }}" aria-label="{{ __('settings.common.device_groups') }}: {{ $label }}">
                                                        @foreach ($appriseDeviceGroups as $group)<option value="{{ $group->id }}">{{ __('settings.notifications.group_prefix', ['name' => $group->name]) }}</option>@endforeach
                                                    </select>
                                                    <select class="form-select form-select-sm mt-2" multiple wire:model="appriseScopeDevices.{{ $eventKey }}" aria-label="{{ __('settings.common.devices') }}: {{ $label }}">
                                                        @foreach ($appriseDevices as $device)<option value="{{ $device->id }}">{{ $device->alias ?: ($device->hostname ?: $device->rustdesk_id) }}</option>@endforeach
                                                    </select>
                                                @endif
                                            @endif
                                        </div>
                                    @endforeach
                                </div>
                                <button class="btn btn-primary mt-2" type="submit" @disabled(! $canManageSettings)><i class="ri-save-line me-1"></i>{{ __('settings.common.save_settings') }}</button>
                                <button class="btn btn-outline-secondary mt-2" type="button" wire:click="sendTestNotification" @disabled(! $canManageSettings)>{{ __('settings.notifications.send_test') }}</button>
                                @if ($appriseTestMessage)<div class="alert {{ $appriseTestOk ? 'alert-success' : 'alert-danger' }} py-2 mt-3 mb-0">{{ $appriseTestMessage }}</div>@endif
                            </form>
                        </div>
                    </div>
                    @if (auth()->user()?->is_admin)
                        <div class="card">
                            <div class="card-header"><h5 class="card-title mb-0">{{ __('settings.notifications.maintenance_title') }}</h5></div>
                        <div class="card-body">
                            <p class="text-muted fs-13">{{ __('settings.notifications.maintenance_help') }}</p>
                            <form wire:submit="createPresenceSnooze" class="row g-2 align-items-end">
                                <div class="col-md-3"><label class="form-label">{{ __('settings.notifications.apply_to') }}</label><select class="form-select" wire:model.live="presenceSnoozeTargetType"><option value="device">{{ __('settings.common.device') }}</option><option value="group">{{ __('settings.notifications.group') }}</option></select></div>
                                <div class="col-md-5"><label class="form-label">{{ __('settings.notifications.target') }}</label><select class="form-select @error('presenceSnoozeTargetId') is-invalid @enderror" wire:model="presenceSnoozeTargetId"><option value="0">{{ __('settings.notifications.choose_target') }}</option>@if($presenceSnoozeTargetType === 'group')@foreach($presenceSnoozeGroups as $group)<option value="{{ $group->id }}">{{ $group->name }}</option>@endforeach @else @foreach($presenceSnoozeDevices as $device)<option value="{{ $device->id }}">{{ $device->alias ?: ($device->hostname ?: $device->rustdesk_id) }}</option>@endforeach @endif</select></div>
                                <div class="col-md-2"><label class="form-label">{{ __('settings.notifications.minutes') }}</label><input type="number" class="form-control @error('presenceSnoozeMinutes') is-invalid @enderror" min="15" max="10080" wire:model="presenceSnoozeMinutes"></div>
                                <div class="col-md-2"><button class="btn btn-outline-warning w-100" type="submit" @disabled(! $canManageSettings)>{{ __('settings.notifications.snooze') }}</button></div>
                            </form>
                            @error('presenceSnoozeTargetId')<div class="text-danger fs-13 mt-2">{{ $message }}</div>@enderror @error('presenceSnoozeMinutes')<div class="text-danger fs-13 mt-2">{{ $message }}</div>@enderror
                            <div class="mt-3">
                                @forelse($presenceSnoozes as $snooze)
                                    @php $target = $snooze->target_type === 'group' ? $presenceSnoozeGroups->firstWhere('id', $snooze->target_id) : $presenceSnoozeDevices->firstWhere('id', $snooze->target_id); @endphp
                                    <div class="d-flex justify-content-between align-items-center border-top py-2"><div><strong>{{ $snooze->target_type === 'group' ? __('settings.notifications.group') : __('settings.common.device') }}: {{ $target?->name ?: $target?->alias ?: $target?->hostname ?: $target?->rustdesk_id ?: __('settings.notifications.deleted_target') }}</strong><div class="text-muted fs-13">{{ __('settings.notifications.muted_until', ['date' => $snooze->expires_at->locale(app()->getLocale())->isoFormat('L LT'), 'relative' => $snooze->expires_at->locale(app()->getLocale())->diffForHumans()]) }}</div></div><button type="button" class="btn btn-sm btn-outline-secondary" wire:click="clearPresenceSnooze({{ $snooze->id }})" @disabled(! $canManageSettings)>{{ __('settings.notifications.end_now') }}</button></div>
                                @empty
                                    <div class="text-muted fs-13">{{ __('settings.notifications.no_snoozes') }}</div>
                                @endforelse
                            </div>
                        </div>
                        </div>
                    @endif
                    <div class="card">
                        <div class="card-header"><h5 class="card-title mb-0">{{ __('settings.notifications.recent') }}</h5></div>
                        <div class="card-body">
                            @forelse ($notificationDeliveries as $delivery)
                                <div class="d-flex justify-content-between gap-3 border-bottom py-2">
                                    @php $rdDevice = $notificationDeliveryDevices[$delivery->id] ?? null; @endphp
                                    <div><strong>{{ $delivery->title }}</strong>
                                        <div class="text-muted fs-13">
                                            {{ __('settings.notifications.events.'.str_replace('.', '_', $delivery->event)) }}
                                            @if ($rdDevice)
                                                · <a href="{{ route('devices.show', $rdDevice->id) }}">{{ trim((string) ($rdDevice->alias ?: $rdDevice->hostname)) ?: __('settings.common.device') }} ({{ $rdDevice->rustdesk_id }})</a>
                                            @endif
                                            · <span title="{{ $delivery->created_at?->format('Y-m-d H:i:s T') }}">{{ $delivery->created_at?->diffForHumans() }}</span>
                                        </div>
                                        @if($delivery->error)<div class="text-danger fs-13 text-break">{{ $delivery->error }}</div>@endif
                                    </div>
                                    <span class="badge {{ $delivery->status === 'sent' ? 'bg-success-subtle text-success' : 'bg-danger-subtle text-danger' }}">{{ __('settings.notifications.'.($delivery->status === 'sent' ? 'sent' : 'failed')) }}</span>
                                </div>
                            @empty
                                <p class="text-muted mb-0">{{ __('settings.notifications.no_deliveries') }}</p>
                            @endforelse
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- ========================= MAINTENANCE ========================= --}}
        <div class="tab-pane {{ $tab === 'maintenance' ? 'show active' : '' }}">
            <div class="row">
                <div class="col-lg-5">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title mb-0">{{ __('settings.maintenance.title') }}</h5>
                        </div>
                        <div class="card-body">
                            <div class="mb-3">
                                <label class="form-label">{{ __('settings.maintenance.days') }}</label>
                                <input type="number" class="form-control @error('logRetentionDays') is-invalid @enderror"
                                       wire:model="logRetentionDays" min="0" max="3650" style="max-width: 140px;">
                                @error('logRetentionDays') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                <div class="form-text">{{ __('settings.maintenance.help') }}</div>
                            </div>
                            <div class="d-flex gap-2">
                                <button type="button" class="btn btn-primary" wire:click="save" @disabled(! $canManageSettings)><i class="ri-save-line me-1"></i>{{ __('settings.common.save') }}</button>
                                <button type="button" class="btn btn-light" wire:click="pruneNow"
                                        wire:confirm="{{ __('settings.maintenance.confirm', ['days' => $logRetentionDays]) }}"
                                        @if($logRetentionDays < 1 || ! $canManageSettings) disabled @endif>
                                    <i class="ri-delete-bin-6-line me-1"></i>{{ __('settings.maintenance.prune') }}
                                </button>
                            </div>
                            @if ($pruneResult)
                                {{-- pre-line keeps the line breaks the pruner emits; rd-mono adds the
                                     break-anywhere guard, since the counts are printed per table name. --}}
                                <div class="alert alert-info py-2 mt-2 mb-0 rd-mono" style="white-space: pre-line;">{{ $pruneResult }}</div>
                            @endif
                        </div>
                    </div>

                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title mb-0">{{ __('settings.maintenance.about') }}</h5>
                        </div>
                        <div class="card-body">
                            <dl class="rd-deflist">
                                <div class="rd-def"><dt>CortenDesk</dt><dd class="rd-mono">v{{ config('cortendesk.api_version') }}</dd></div>
                                <div class="rd-def"><dt>Laravel</dt><dd class="rd-mono">{{ app()->version() }}</dd></div>
                                <div class="rd-def"><dt>PHP</dt><dd class="rd-mono">{{ PHP_VERSION }}</dd></div>
                            </dl>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </div>
</div>
