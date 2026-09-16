@extends('layouts.guest')

@section('title', __('auth.email_challenge.title'))

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
                <h4 class="rd-auth-title">{{ __('auth.email_challenge.heading') }}</h4>
                <p class="rd-auth-sub">
                    @if ($sentTo)
                        {!! __('auth.email_challenge.intro_sent_to', ['email' => '<span class="rd-mono">'.e($sentTo).'</span>']) !!}
                    @else
                        {{ __('auth.email_challenge.intro') }}
                    @endif
                </p>
            </div>

            @if ($errors->any())
                <div class="alert alert-danger" role="alert">
                    {{ $errors->first() }}
                </div>
            @endif

            @if (session('status'))
                <div class="alert alert-success" role="alert">{{ session('status') }}</div>
            @endif

            <form method="POST" action="{{ route('login.email.attempt') }}">
                @csrf

                <div class="mb-3">
                    <label for="code" class="form-label">{{ __('auth.email_challenge.code') }}</label>
                    <input class="form-control rd-code-input rd-mono" type="text" id="code" name="code"
                           required autofocus autocomplete="one-time-code" inputmode="numeric" maxlength="6"
                           placeholder="123456">
                </div>

                <div class="mb-0 d-grid">
                    <button class="btn btn-primary" type="submit">
                        <i class="ri-mail-check-line me-1"></i> {{ __('auth.email_challenge.submit') }}
                    </button>
                </div>
            </form>

            <div class="rd-auth-foot">
                <form method="POST" action="{{ route('login.email.resend') }}" class="d-inline">
                    @csrf
                    <button type="submit" class="btn btn-link btn-sm p-0">{{ __('auth.email_challenge.resend') }}</button>
                </form>
                <span class="mx-1">·</span>
                <a href="{{ route('login') }}">{{ __('auth.email_challenge.switch_user') }}</a>
                <span class="rd-auth-foot-note">
                    {{ __('auth.email_challenge.remembered', ['days' => \App\Models\TrustedDevice::trustDays()]) }}
                </span>
            </div>
        </div>
    </div>
@endsection
