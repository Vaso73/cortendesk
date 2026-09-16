<?php

namespace App\Mail;

use App\Support\LocaleNormalizer;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

/**
 * "Send test email" from Settings → Email.
 *
 * Not ShouldQueue — the container runs php-fpm, nginx and the scheduler only
 * (docker/supervisord.conf); a queued message would sit in the jobs table
 * forever. Every mailable in this console sends synchronously, bounded by the
 * 10s SMTP timeout MailSettings pins.
 */
class TestMessage extends Mailable
{
    public function __construct(?string $locale = null)
    {
        $normalizer = app(LocaleNormalizer::class);
        $this->locale($normalizer->normalize($locale)
            ?? $normalizer->fallback());
    }

    public function envelope(): Envelope
    {
        return new Envelope(subject: __('notifications.mail.test.subject', [
            'app' => config('app.name'),
        ]));
    }

    public function content(): Content
    {
        return new Content(view: 'mail.test', text: 'mail.test-text');
    }
}
