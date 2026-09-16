@extends('layouts.guest')

@section('title', __('auth.two_factor_challenge.title'))

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
                <h4 class="rd-auth-title">{{ __('auth.two_factor_challenge.heading') }}</h4>
                <p class="rd-auth-sub">{{ __('auth.two_factor_challenge.intro') }}</p>
            </div>

            @if ($errors->any())
                <div class="alert alert-danger" role="alert">
                    {{ $errors->first() }}
                </div>
            @endif

            <form method="POST" action="{{ route('login.2fa.attempt') }}">
                @csrf

                <div class="mb-3">
                    <label for="code" class="form-label">{{ __('auth.two_factor_challenge.code') }}</label>
                    <input class="form-control rd-code-input rd-mono" type="text" id="code" name="code"
                           required autofocus autocomplete="one-time-code" inputmode="text"
                           placeholder="123456">
                    <div class="form-text">{{ __('auth.two_factor_challenge.recovery_help') }}</div>
                </div>

                <div class="mb-0 d-grid">
                    <button class="btn btn-primary" type="submit">
                        <i class="ri-shield-check-line me-1"></i> {{ __('auth.common.verify') }}
                    </button>
                </div>
            </form>

            <div class="rd-auth-foot">
                <a href="{{ route('login') }}">{{ __('auth.email_challenge.switch_user') }}</a>
            </div>
        </div>
    </div>
@endsection
