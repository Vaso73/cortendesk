@extends('layouts.guest')

@section('title', __('settings.downloads.title'))

@section('content')
    <div class="card">

        <div class="card-header rd-auth-head py-3 text-center d-flex align-items-center justify-content-center">
            <a href="{{ url('/') }}" class="auth-brand mb-0">
                <img src="{{ \App\Support\Asset::url('assets/images/cortendesk-sm.svg') }}" alt="CortenDesk" width="60" height="60" class="auth-brand-logo">
                <span class="auth-brand-wordmark">Corten<span>Desk</span></span>
            </a>
        </div>

        <div class="card-body p-4">

            <div class="text-center mb-4">
                <h4 class="rd-auth-title">{{ __('settings.downloads.title') }}</h4>
                <p class="rd-auth-sub">
                    {{ __('settings.downloads.public_subtitle') }}
                </p>
            </div>

            @if ($downloads->isEmpty())
                <div class="rd-empty">
                    <div class="rd-empty-icon"><i class="ri-download-cloud-line"></i></div>
                    <p class="rd-empty-title">{{ __('settings.downloads.public_empty') }}</p>
                    <p class="rd-empty-text">{{ __('settings.downloads.public_empty_help') }}</p>
                </div>
            @else
                @foreach ($downloads->groupBy('platform') as $platform => $group)
                    <p class="rd-dl-group">
                        <x-platform-icon :platform="$platform" size="fs-16" class="me-1" />
                        {{ \App\Support\ClientPlatform::label($platform) }}
                    </p>
                    <x-client-download-links :downloads="$group" class="mb-3" />
                @endforeach

                <p class="rd-auth-note mb-0 text-center">
                    {{ __('settings.downloads.trust_note') }}
                </p>
            @endif

            <div class="text-center mt-3">
                <a href="{{ route('login') }}" class="rd-auth-quiet">{{ __('settings.downloads.sign_in') }}</a>
            </div>
        </div>
    </div>
@endsection
