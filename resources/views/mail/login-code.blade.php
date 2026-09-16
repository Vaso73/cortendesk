@component('mail.layout')
    <p style="margin:0 0 12px;font-size:17px;font-weight:600;">{{ __('notifications.mail.login.title') }}</p>

    <p style="margin:0 0 12px;">{!! __('notifications.mail.login.intro', [
        'username' => '<strong style="font-family:ui-monospace,SFMono-Regular,Menlo,monospace;">'.e($username).'</strong>',
    ]) !!}</p>

    <p style="margin:20px 0;font-size:32px;font-weight:700;letter-spacing:8px;font-family:ui-monospace,SFMono-Regular,Menlo,monospace;">
        {{ $code }}
    </p>

    <p style="margin:0 0 12px;font-size:13px;color:#7b8794;">
        {{ trans_choice('notifications.mail.login.expires', $minutes, ['count' => $minutes]) }}
        @if ($ip)
            {{ __('notifications.mail.login.ip', ['ip' => $ip]) }}
        @endif
    </p>

    <p style="margin:0;font-size:13px;color:#7b8794;">
        {{ __('notifications.mail.login.recovery') }}
    </p>
@endcomponent
