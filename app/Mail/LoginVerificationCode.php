<?php

namespace App\Mail;

use App\Models\User;
use App\Support\LocaleNormalizer;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

/**
 * The 6-digit new-device sign-in code (PLAN D1). Not queued — see TestMessage;
 * the login request blocks on this send, which is why the transport timeout is
 * pinned low.
 */
class LoginVerificationCode extends Mailable
{
    public string $username;

    public function __construct(
        public string $code,
        User $user,
        public ?string $ip = null,
        public int $minutes = 10,
    ) {
        $this->username = $user->username;
        $normalizer = app(LocaleNormalizer::class);
        $this->locale($user->preferredLocale()
            ?? $normalizer->fallback());
    }

    public function envelope(): Envelope
    {
        return new Envelope(subject: __('notifications.mail.login.subject', [
            'code' => $this->code,
            'app' => config('app.name'),
        ]));
    }

    public function content(): Content
    {
        return new Content(view: 'mail.login-code', text: 'mail.login-code-text');
    }
}
