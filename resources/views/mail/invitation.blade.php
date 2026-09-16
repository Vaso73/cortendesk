@component('mail.layout')
    <p style="margin:0 0 12px;font-size:17px;font-weight:600;">{{ __('notifications.mail.invitation.title', ['app' => config('app.name')]) }}</p>

    <p style="margin:0 0 12px;">{!! __('notifications.mail.invitation.intro', [
        'invited_by' => e($invitedBy),
        'username' => '<strong style="font-family:ui-monospace,SFMono-Regular,Menlo,monospace;">'.e($invitation->username).'</strong>',
    ]) !!}</p>

    <p style="margin:20px 0;">
        <a href="{{ $acceptUrl }}"
           style="display:inline-block;background:#e2652e;color:#ffffff;text-decoration:none;padding:11px 22px;border-radius:6px;font-weight:600;">
            {{ __('notifications.mail.invitation.action') }}
        </a>
    </p>

    <p style="margin:0 0 12px;font-size:13px;color:#7b8794;">{{ __('notifications.mail.invitation.copy_link') }}</p>
    <p style="margin:0 0 16px;font-size:12px;word-break:break-all;font-family:ui-monospace,SFMono-Regular,Menlo,monospace;color:#26303e;">
        {{ $acceptUrl }}
    </p>

    <p style="margin:0;font-size:13px;color:#7b8794;">
        {{ __('notifications.mail.invitation.expiry', [
            'relative' => $invitation->expires_at->diffForHumans(),
            'timestamp' => $invitation->expires_at->format('Y-m-d H:i'),
        ]) }}
        {{ __('notifications.mail.invitation.ignore') }}
    </p>
@endcomponent
