@extends('layouts.guest')

@section('title', __('identity.invite_accept.title'))

@section('content')
    <div class="card">
        <div class="card-header rd-auth-head py-3 text-center d-flex align-items-center justify-content-center">
            <a href="{{ url('/') }}" class="auth-brand mb-0">
                <img src="{{ asset('assets/images/cortendesk-sm.svg') }}" alt="CortenDesk" width="60" height="60" class="auth-brand-logo">
                <span class="auth-brand-wordmark">Corten<span>Desk</span></span>
            </a>
        </div>

        <div class="card-body p-4">
            <div class="text-center mb-4">
                <h4 class="rd-auth-title">{{ __('identity.invite_accept.heading') }}</h4>
                <p class="rd-auth-sub">{{ __('identity.invite_accept.intro') }}</p>
            </div>

            @if ($errors->any())
                <div class="alert alert-danger" role="alert">
                    {{ $errors->first() }}
                </div>
            @endif

            <form method="POST" action="{{ route('invite.accept', $token) }}">
                @csrf

                <div class="mb-3">
                    <label for="username" class="form-label">{{ __('identity.common.username') }}</label>
                    <input class="form-control" type="text" id="username" value="{{ $invitation->username }}" readonly disabled>
                    <div class="form-text">{{ __('identity.invite_accept.username_help') }}</div>
                </div>

                <div class="mb-3">
                    <label for="email" class="form-label">{{ __('identity.common.email') }}</label>
                    <input class="form-control" type="text" id="email" value="{{ $invitation->email }}" readonly disabled>
                </div>

                <div class="mb-3">
                    <label for="name" class="form-label">{{ __('identity.common.display_name') }}</label>
                    <input class="form-control" type="text" id="name" name="name"
                           value="{{ old('name', $invitation->name) }}" autocomplete="name" placeholder="{{ __('identity.common.optional') }}">
                </div>

                <div class="mb-3">
                    <label for="password" class="form-label">{{ __('identity.users.password') }}</label>
                    <div class="input-group input-group-merge">
                        <input type="password" id="password" name="password" class="form-control"
                               required autofocus autocomplete="new-password" placeholder="{{ __('identity.invite_accept.password_placeholder') }}">
                        <div class="input-group-text" data-password="false" aria-label="{{ __('identity.common.show_password') }}">
                            <span class="password-eye"></span>
                        </div>
                    </div>
                </div>

                <div class="mb-3">
                    <label for="password_confirmation" class="form-label">{{ __('identity.invite_accept.confirm_password') }}</label>
                    <input type="password" id="password_confirmation" name="password_confirmation" class="form-control"
                           required autocomplete="new-password" placeholder="{{ __('identity.invite_accept.confirm_placeholder') }}">
                </div>

                <div class="mb-0 d-grid">
                    <button class="btn btn-primary" type="submit">
                        <i class="ri-user-add-line me-1"></i> {{ __('identity.invite_accept.create_account') }}
                    </button>
                </div>
            </form>

            <div class="rd-auth-foot">
                {{ __('identity.invite_accept.expiry', ['relative' => $invitation->expires_at->diffForHumans()]) }}
            </div>
        </div>
    </div>
@endsection
