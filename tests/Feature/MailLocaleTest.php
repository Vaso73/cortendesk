<?php

namespace Tests\Feature;

use App\Mail\LoginVerificationCode;
use App\Mail\PasswordResetLink;
use App\Mail\TestMessage;
use App\Mail\UserInvitation;
use App\Models\Invitation;
use App\Models\Setting;
use App\Models\User;
use App\Services\MailSettings;
use Illuminate\Contracts\Translation\HasLocalePreference as HasLocalePreferenceContract;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class MailLocaleTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_implements_laravel_locale_preference_contract(): void
    {
        $user = new User;

        $this->assertInstanceOf(HasLocalePreferenceContract::class, $user);
        $this->assertNotContains('locale', $user->getFillable());
    }

    public function test_stored_user_locale_controls_mailable_and_layout_without_leaking(): void
    {
        $user = User::factory()->make();
        $user->forceFill(['locale' => 'sk']);
        app()->setLocale('en');

        $mail = new PasswordResetLink($user, 'https://console.test/reset/token', 60);

        $this->assertSame('sk', $mail->locale);
        $this->assertStringContainsString('<html lang="sk" dir="ltr">', $mail->render());
        $this->assertSame('en', app()->getLocale());
    }

    public function test_login_code_uses_the_recipient_locale_without_leaking(): void
    {
        $user = User::factory()->make(['username' => 'alice']);
        $user->forceFill(['locale' => 'sk']);
        app()->setLocale('en');

        $mail = new LoginVerificationCode('123456', $user, '192.0.2.1');

        $this->assertSame('sk', $mail->locale);
        $this->assertSame('alice', $mail->username);
        $this->assertStringContainsString('<html lang="sk" dir="ltr">', $mail->render());
        $this->assertSame('en', app()->getLocale());
    }

    public function test_invitation_snapshots_the_inviting_users_locale_without_leaking(): void
    {
        $inviter = User::factory()->create();
        $inviter->forceFill(['locale' => 'sk'])->save();
        app()->setLocale('en');

        [$invitation] = Invitation::issue([
            'email' => 'guest@example.test',
            'username' => 'guest',
        ], $inviter);
        $mail = new UserInvitation($invitation, 'https://console.test/invite/token', $inviter->displayName());

        $this->assertSame('sk', $invitation->locale);
        $this->assertSame('sk', $mail->locale);
        $this->assertStringContainsString('<html lang="sk" dir="ltr">', $mail->render());
        $this->assertSame('en', app()->getLocale());
    }

    public function test_test_message_uses_an_explicit_locale_and_does_not_leak_it(): void
    {
        Setting::put('smtp_host', 'mail.example.test');
        Setting::put('smtp_from_address', 'console@example.test');
        Mail::fake();
        app()->setLocale('en');

        $result = app(MailSettings::class)->test('operator@example.test', 'sk');

        $this->assertTrue($result['ok']);
        Mail::assertSent(TestMessage::class, function (TestMessage $mail): bool {
            $this->assertSame('sk', $mail->locale);
            $this->assertStringContainsString('<html lang="sk" dir="ltr">', $mail->render());

            return true;
        });
        $this->assertSame('en', app()->getLocale());
    }

    public function test_guest_invitation_snapshots_the_stable_fallback_locale(): void
    {
        config(['app.locale' => 'en']);
        app()->setLocale('sk');

        [$invitation] = Invitation::issue([
            'email' => 'guest@example.test',
            'username' => 'guest',
        ]);
        $mail = new UserInvitation($invitation, 'https://console.test/invite/token', 'An administrator');

        $this->assertSame('en', $invitation->locale);
        $this->assertSame('en', $mail->locale);
        $this->assertStringContainsString('<html lang="en" dir="ltr">', $mail->render());
        $this->assertSame('sk', app()->getLocale());
    }

    public function test_mail_without_a_stored_preference_uses_fallback_not_request_locale(): void
    {
        app()->setLocale('sk');
        $user = User::factory()->make(['username' => 'alice']);

        $this->assertSame('en', (new PasswordResetLink($user, 'https://console.test/reset/token', 60))->locale);
        $this->assertSame('en', (new LoginVerificationCode('123456', $user))->locale);
        $this->assertSame('en', (new TestMessage)->locale);
    }

    public function test_legacy_invitation_without_snapshot_uses_stable_fallback(): void
    {
        app()->setLocale('sk');
        $invitation = new Invitation(['email' => 'guest@example.test']);

        $mail = new UserInvitation($invitation, 'https://console.test/invite/token', 'An administrator');

        $this->assertSame('en', $mail->locale);
    }
}
