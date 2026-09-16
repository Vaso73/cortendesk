<?php

namespace App\Mail;

use App\Models\User;
use App\Support\LocaleNormalizer;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

/** Password reset link. Not queued — see TestMessage. */
class PasswordResetLink extends Mailable
{
    public function __construct(
        public User $user,
        public string $resetUrl,
        public int $ttlMinutes,
        public ?string $requestedIp = null,
    ) {
        $normalizer = app(LocaleNormalizer::class);
        $this->locale($user->preferredLocale()
            ?? $normalizer->fallback());
    }

    public function envelope(): Envelope
    {
        return new Envelope(subject: __('notifications.mail.password_reset.subject', [
            'app' => config('app.name'),
        ]));
    }

    public function content(): Content
    {
        return new Content(view: 'mail.password-reset', text: 'mail.password-reset-text');
    }
}
