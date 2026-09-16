{{ __('notifications.mail.invitation.title', ['app' => config('app.name')]) }}

{{ __('notifications.mail.invitation.intro', ['invited_by' => $invitedBy, 'username' => $invitation->username]) }}

{{ __('notifications.mail.invitation.action') }}:
{{ $acceptUrl }}

{{ __('notifications.mail.invitation.copy_link') }}
{{ $acceptUrl }}

{{ __('notifications.mail.invitation.expiry', ['relative' => $invitation->expires_at->diffForHumans(), 'timestamp' => $invitation->expires_at->format('Y-m-d H:i')]) }}
{{ __('notifications.mail.invitation.ignore') }}

{{ __('notifications.mail.footer', ['app' => config('app.name'), 'url' => rtrim((string) config('app.url'), '/')]) }}
