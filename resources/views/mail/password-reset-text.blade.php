{{ __('notifications.mail.password_reset.title') }}

{{ __('notifications.mail.password_reset.intro', ['username' => $user->username, 'app' => config('app.name')]) }}

{{ __('notifications.mail.password_reset.action') }}:
{{ $resetUrl }}

{{ trans_choice('notifications.mail.password_reset.expires', $ttlMinutes, ['count' => $ttlMinutes]) }}
@if ($requestedIp)
{{ __('notifications.mail.password_reset.ip', ['ip' => $requestedIp]) }}
@endif

{{ __('notifications.mail.password_reset.copy_link') }}
{{ $resetUrl }}

{{ __('notifications.mail.password_reset.ignore') }}

{{ __('notifications.mail.footer', ['app' => config('app.name'), 'url' => rtrim((string) config('app.url'), '/')]) }}
