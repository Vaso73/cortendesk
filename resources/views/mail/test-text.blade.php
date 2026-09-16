{{ __('notifications.mail.test.title') }}

{{ __('notifications.mail.test.intro', ['app' => config('app.name')]) }}

{{ __('notifications.mail.test.close') }}

{{ __('notifications.mail.footer', ['app' => config('app.name'), 'url' => rtrim((string) config('app.url'), '/')]) }}
