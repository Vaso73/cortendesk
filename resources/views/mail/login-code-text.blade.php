{{ __('notifications.mail.login.title') }}

{{ __('notifications.mail.login.intro', ['username' => $username]) }}

{{ $code }}

{{ trans_choice('notifications.mail.login.expires', $minutes, ['count' => $minutes]) }}
@if ($ip)
{{ __('notifications.mail.login.ip', ['ip' => $ip]) }}
@endif

{{ __('notifications.mail.login.recovery') }}

{{ __('notifications.mail.footer', ['app' => config('app.name'), 'url' => rtrim((string) config('app.url'), '/')]) }}
