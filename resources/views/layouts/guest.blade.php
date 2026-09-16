<!DOCTYPE html>
<html lang="{{ config('app.locale_metadata.html_lang', 'en') }}" dir="{{ config('app.locale_metadata.dir', 'ltr') }}" data-bs-theme="dark">
<head>
    <meta charset="utf-8"/>
    <title>@yield('title', 'Sign In') | {{ config('app.name') }}</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="shortcut icon" href="{{ \App\Support\Asset::url('assets/images/cortendesk-sm.svg') }}">

    <script src="{{ \App\Support\Asset::url('assets/js/config.js') }}"></script>
    <link href="{{ \App\Support\Asset::url('assets/css/app.min.css') }}" rel="stylesheet" type="text/css" id="app-style"/>
    <link href="{{ \App\Support\Asset::url('assets/css/icons.min.css') }}" rel="stylesheet" type="text/css"/>
    <link href="{{ \App\Support\Asset::url('assets/css/cortendesk.css') }}" rel="stylesheet" type="text/css"/>
</head>

<body class="authentication-bg position-relative">

    <div class="account-pages pt-2 pt-sm-5 pb-4 pb-sm-5 position-relative">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-xxl-4 col-lg-5">

                    @yield('content')

                    <div class="d-flex justify-content-center mt-3">
                        @include('layouts.partials.language-selector', ['localePickerId' => 'guest'])
                    </div>

                    <div class="text-center mt-3">
                        <p class="rd-auth-note">{{ __('ui.layout.guest_note') }}</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="{{ \App\Support\Asset::url('assets/js/vendor.min.js') }}"></script>
    <script src="{{ \App\Support\Asset::url('assets/js/app.min.js') }}"></script>
</body>
</html>
