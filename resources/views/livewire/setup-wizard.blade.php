<div wire:poll.10s="refreshDeviceStatus">
    <div class="d-flex flex-wrap justify-content-between align-items-start gap-2 mb-3">
        <div>
            <h4 class="mb-1">{{ __('settings.setup.title') }}</h4>
            <p class="text-muted mb-0">{{ __('settings.setup.subtitle') }}</p>
        </div>
        @if (auth()->user()?->consoleAllows('setting', 'rw'))
            <button type="button" class="btn btn-sm btn-outline-light" wire:click="dismiss">{{ __('settings.setup.later') }}</button>
        @endif
    </div>

    <div class="row g-3">
        <div class="col-xl-8">
            <div class="card">
                <div class="card-header"><h5 class="card-title mb-0">{{ __('settings.setup.step1') }}</h5></div>
                <div class="card-body">
                    {{-- Settings → Client Setup already lists these four values
                         with copy buttons. Repeating them here would mean two
                         screens to keep in step. --}}
                    <p>{{ __('settings.setup.step1_help') }}</p>
                    @if ($idServer === '' || $relayServer === '' || $apiUrl === '' || $publicKey === '')
                        <div class="alert alert-warning">
                            {{ __('settings.setup.missing') }}
                            <a class="alert-link" href="{{ route('settings', ['tab' => 'server']) }}">{{ __('settings.setup.complete_server') }}</a> {{ __('settings.setup.first') }}
                        </div>
                    @endif
                    <a class="btn btn-outline-light" href="{{ route('settings', ['tab' => 'client']) }}">
                        <i class="ri-file-copy-line me-1"></i>{{ __('settings.setup.copy') }}
                    </a>
                </div>
            </div>

            <div class="card">
                <div class="card-header"><h5 class="card-title mb-0">{{ __('settings.setup.step2') }}</h5></div>
                <div class="card-body">
                    @if ($deviceConnected)
                        <div class="alert alert-success mb-3"><i class="ri-check-line me-1"></i>{{ __('settings.setup.connected') }}</div>
                    @else
                        <div class="alert alert-info mb-3"><i class="ri-loader-4-line me-1"></i>{{ __('settings.setup.waiting') }}</div>
                    @endif
                    @error('device')<div class="alert alert-danger">{{ $message }}</div>@enderror
                    @if (auth()->user()?->consoleAllows('setting', 'rw'))
                        <button type="button" class="btn btn-primary" wire:click="complete" @disabled(! $deviceConnected)>
                            {{ __('settings.setup.finish') }}
                        </button>
                    @endif
                </div>
            </div>
        </div>

        <div class="col-xl-4">
            <div class="card">
                <div class="card-header"><h5 class="card-title mb-0">{{ __('settings.setup.step3') }}</h5></div>
                <div class="card-body">
                    <ul class="list-unstyled mb-0">
                        <li class="mb-3"><i class="{{ $approvalEnabled ? 'ri-checkbox-circle-line text-success' : 'ri-error-warning-line text-warning' }} me-1"></i><strong>{{ __('settings.setup.approval') }}</strong><br><span class="text-muted fs-13">{{ __('settings.setup.approval_help') }}</span></li>
                        <li><i class="{{ $twoFactorRequired ? 'ri-checkbox-circle-line text-success' : 'ri-error-warning-line text-warning' }} me-1"></i><strong>{{ __('settings.setup.admin_2fa') }}</strong><br><span class="text-muted fs-13">{{ __('settings.setup.admin_2fa_help') }}</span></li>
                    </ul>
                    @if (! $approvalEnabled || ! $twoFactorRequired)
                        <a href="{{ route('settings', ['tab' => 'security']) }}" class="btn btn-sm btn-outline-light mt-3">{{ __('settings.setup.review_security') }}</a>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>