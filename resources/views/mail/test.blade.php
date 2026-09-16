@component('mail.layout')
    <p style="margin:0 0 12px;font-size:17px;font-weight:600;">{{ __('notifications.mail.test.title') }}</p>
    <p style="margin:0 0 12px;">{{ __('notifications.mail.test.intro', ['app' => config('app.name')]) }}</p>
    <p style="margin:0;color:#7b8794;font-size:13px;">{{ __('notifications.mail.test.close') }}</p>
@endcomponent
