@component('mail.layout')
    <p style="margin:0 0 12px;font-size:17px;font-weight:600;">{{ __('notifications.mail.password_reset.title') }}</p>

    <p style="margin:0 0 12px;">{!! __('notifications.mail.password_reset.intro', [
        'username' => '<strong style="font-family:ui-monospace,SFMono-Regular,Menlo,monospace;">'.e($user->username).'</strong>',
        'app' => e(config('app.name')),
    ]) !!}</p>

    <p style="margin:20px 0;">
        <a href="{{ $resetUrl }}"
           style="display:inline-block;padding:11px 20px;background:#f26c23;color:#ffffff;text-decoration:none;border-radius:5px;font-weight:600;">
            {{ __('notifications.mail.password_reset.action') }}
        </a>
    </p>

    <p style="margin:0 0 12px;font-size:13px;color:#7b8794;">
        {{ trans_choice('notifications.mail.password_reset.expires', $ttlMinutes, ['count' => $ttlMinutes]) }}
        @if ($requestedIp)
            {{ __('notifications.mail.password_reset.ip', ['ip' => $requestedIp]) }}
        @endif
    </p>

    <p style="margin:0 0 12px;font-size:13px;color:#7b8794;">
        {{ __('notifications.mail.password_reset.copy_link') }}<br>
        <span style="word-break:break-all;">{{ $resetUrl }}</span>
    </p>

    <p style="margin:0;font-size:13px;color:#7b8794;">
        {{ __('notifications.mail.password_reset.ignore') }}
    </p>
@endcomponent
