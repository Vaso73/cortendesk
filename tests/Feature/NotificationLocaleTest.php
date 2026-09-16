<?php

namespace Tests\Feature;

use App\Mail\LoginVerificationCode;
use App\Mail\PasswordResetLink;
use App\Mail\TestMessage;
use App\Mail\UserInvitation;
use App\Models\Device;
use App\Models\Invitation;
use App\Models\Setting;
use App\Models\User;
use App\Services\AppriseNotifications;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class NotificationLocaleTest extends TestCase
{
    use RefreshDatabase;

    public function test_all_mailables_render_localized_subject_html_and_text_without_leaking_locale(): void
    {
        config(['app.url' => 'https://console.example.test']);
        $user = User::factory()->create(['username' => 'alice']);
        $inviter = User::factory()->create(['username' => 'admin']);

        foreach ([
            'en' => [
                'login_subject' => '482913 is your CortenDesk sign-in code',
                'login_text' => 'Your sign-in code',
                'reset_subject' => 'Reset your CortenDesk password',
                'reset_text' => 'Choose a new password',
                'invite_subject' => 'You have been invited to CortenDesk',
                'invite_text' => 'Accept invitation',
                'test_subject' => 'CortenDesk — test message',
                'test_text' => 'Your mail settings work.',
                'footer' => 'Sent by CortenDesk at https://console.example.test.',
            ],
            'sk' => [
                'login_subject' => '482913 je váš prihlasovací kód do CortenDesk',
                'login_text' => 'Váš prihlasovací kód',
                'reset_subject' => 'Obnovte si heslo do CortenDesk',
                'reset_text' => 'Nastaviť nové heslo',
                'invite_subject' => 'Dostali ste pozvánku do CortenDesk',
                'invite_text' => 'Prijať pozvánku',
                'test_subject' => 'CortenDesk — testovacia správa',
                'test_text' => 'Nastavenie e-mailov funguje.',
                'footer' => 'Odoslané zo služby CortenDesk na adrese https://console.example.test.',
            ],
        ] as $locale => $expected) {
            $user->forceFill(['locale' => $locale]);
            [$invitation] = Invitation::issue([
                'email' => $locale.'@example.test',
                'username' => 'guest-'.$locale,
            ], $inviter->forceFill(['locale' => $locale]));

            $mailables = [
                [new LoginVerificationCode('482913', $user, '192.0.2.44', 2), 'login_subject', 'login_text', ['482913', 'alice', '192.0.2.44']],
                [new PasswordResetLink($user, 'https://console.example.test/reset/A_B-9', 2, '198.51.100.17'), 'reset_subject', 'reset_text', ['alice', 'https://console.example.test/reset/A_B-9', '198.51.100.17']],
                [new UserInvitation($invitation, 'https://console.example.test/invite/X-y_7', 'Admin Žofia'), 'invite_subject', 'invite_text', ['guest-'.$locale, 'https://console.example.test/invite/X-y_7', 'Admin Žofia', $invitation->expires_at->format('Y-m-d H:i').' UTC']],
                [new TestMessage($locale), 'test_subject', 'test_text', []],
            ];

            app()->setLocale($locale === 'en' ? 'sk' : 'en');
            $ambient = app()->getLocale();

            foreach ($mailables as [$mail, $subjectKey, $textKey, $preserved]) {
                $html = $mail->render();

                $this->assertSame($expected[$subjectKey], $mail->subject);
                $this->assertStringContainsString('<html lang="'.$locale.'" dir="ltr">', $html);
                $this->assertStringContainsString($expected[$textKey], $html);
                $this->assertStringContainsString($expected['footer'], $html);
                foreach ($preserved as $value) {
                    $this->assertStringContainsString($value, $html);
                }

                $mail->assertSeeInText($expected[$textKey]);
                foreach ($preserved as $value) {
                    $mail->assertSeeInText($value, false);
                }
                $this->assertSame($ambient, app()->getLocale());
            }
        }
    }

    public function test_mail_count_copy_uses_english_and_slovak_plural_rules(): void
    {
        $user = User::factory()->make(['username' => 'alice']);

        foreach ([
            'en' => [1 => 'The code expires in 1 minute.', 2 => 'The code expires in 2 minutes.'],
            'sk' => [1 => 'Platnosť kódu vyprší o 1 minútu.', 2 => 'Platnosť kódu vyprší o 2 minúty.', 5 => 'Platnosť kódu vyprší o 5 minút.'],
        ] as $locale => $cases) {
            $user->forceFill(['locale' => $locale]);
            foreach ($cases as $minutes => $copy) {
                $this->assertStringContainsString($copy, (new LoginVerificationCode('123456', $user, null, $minutes))->render());
            }
        }
    }

    public function test_apprise_uses_normalized_fallback_not_ambient_locale_and_preserves_technical_values(): void
    {
        $this->configureApprise('device.pending_approval');
        config(['app.fallback_locale' => 'sk-SK']);
        app()->setLocale('en');
        Http::fake(['https://apprise.example.test/*' => Http::response([], 200)]);

        $device = Device::query()->create([
            'rustdesk_id' => 'RD-001_Ž',
            'uuid' => 'uuid-raw',
            'hostname' => 'srv-01.example.test',
            'alias' => 'Brána Žilina',
            'status' => Device::STATUS_PENDING,
        ]);

        $delivery = app(AppriseNotifications::class)->send(
            'device.pending_approval',
            'Device pending approval',
            'Brána Žilina (RD-001_Ž) is awaiting approval.',
            'device:RD-001_Ž',
            $device,
        );

        Http::assertSent(function ($request): bool {
            $this->assertSame('Zariadenie čaká na schválenie', $request['title']);
            $this->assertSame('Zariadenie Brána Žilina (RD-001_Ž) čaká na schválenie.', $request['body']);

            return true;
        });
        $this->assertSame('Zariadenie čaká na schválenie', $delivery?->title);
        $this->assertSame('device:RD-001_Ž', $delivery?->subject);
        $this->assertSame('en', app()->getLocale());
    }

    public function test_apprise_test_notification_uses_english_when_configured_fallback_is_unsupported(): void
    {
        $this->configureApprise();
        config(['app.fallback_locale' => 'xx-YY']);
        app()->setLocale('sk');
        Http::fake(['https://apprise.example.test/*' => Http::response([], 200)]);

        app(AppriseNotifications::class)->test();

        Http::assertSent(function ($request): bool {
            $this->assertSame('CortenDesk notification test', $request['title']);
            $this->assertSame('This is a test notification from CortenDesk.', $request['body']);

            return true;
        });
        $this->assertSame('sk', app()->getLocale());
    }

    public function test_scheduled_device_notification_and_summary_use_explicit_slovak_fallback(): void
    {
        $this->configureApprise('device.offline');
        config(['app.fallback_locale' => 'sk']);
        app()->setLocale('en');
        Setting::put('apprise_offline_grace_minutes', '0');
        Http::fake(['https://apprise.example.test/*' => Http::response([], 200)]);
        Device::query()->create([
            'rustdesk_id' => '908 172 635',
            'uuid' => 'uuid-preserve',
            'hostname' => 'pc-účtáreň',
            'status' => Device::STATUS_ACTIVE,
            'last_online_at' => now()->subMinutes(10),
        ]);

        $this->artisan('cortendesk:check-device-notifications')
            ->expectsOutput('Zistené prechody zariadení: 1 odpojenie a žiadne obnovené pripojenia.')
            ->assertSuccessful();

        Http::assertSent(function ($request): bool {
            $this->assertSame('Zariadenie je odpojené', $request['title']);
            $this->assertSame('Zariadenie pc-účtáreň (908 172 635) prestalo odosielať signál dostupnosti.', $request['body']);

            return true;
        });
        $this->assertSame('en', app()->getLocale());
    }

    public function test_after_response_notification_keeps_the_explicit_fallback_snapshot(): void
    {
        $this->configureApprise('console.login_failed');
        config(['app.fallback_locale' => 'sk']);
        app()->setLocale('en');
        Http::fake(['https://apprise.example.test/*' => Http::response([], 200)]);

        app(AppriseNotifications::class)->sendAfterResponse(
            'console.login_failed',
            'Failed console login',
            'A web-console sign-in attempt failed.',
            'web-login:raw_hash',
        );
        config(['app.fallback_locale' => 'en']);
        $this->app->terminate();

        Http::assertSent(function ($request): bool {
            $this->assertSame('Neúspešné prihlásenie do konzoly', $request['title']);
            $this->assertSame('Pokus o prihlásenie do webovej konzoly zlyhal.', $request['body']);

            return true;
        });
        $this->assertSame('en', app()->getLocale());
    }

    public function test_all_apprise_event_copy_is_localized_while_dynamic_values_are_preserved(): void
    {
        $this->configureApprise();
        config(['app.fallback_locale' => 'sk']);
        app()->setLocale('en');
        Setting::put('apprise_event_device_online', '1');
        Setting::put('apprise_event_console_login_failed', '1');
        Setting::put('apprise_event_security_alarm', '1');
        Setting::put('apprise_event_remote_connection_failure', '1');
        Http::fake(['https://apprise.example.test/*' => Http::response([], 200)]);

        $service = app(AppriseNotifications::class);
        $service->send('device.online', 'Device recovered', 'Node-A (ID_77) is online again.', 'device:ID_77');
        $service->send('console.login_failed', 'Failed console login', 'A web-console sign-in attempt failed.', 'web-login:raw_hash');
        $service->send('console.login_failed', 'Failed console login', 'A RustDesk client sign-in attempt failed.', 'client-login:raw_hash');
        $service->send('security.alarm', 'Many failed attempts (>30)', 'Device ID_77 reported a security alarm.', 'alarm:42');
        $service->send('remote_connection.failure', 'Repeated remote connection failures', 'Device ID_77 reported more than 30 failed connection attempts.', 'device:ID_77');

        $payloads = Http::recorded()->map(fn (array $pair): array => $pair[0]->data())->all();
        $this->assertSame([
            ['title' => 'Pripojenie zariadenia bolo obnovené', 'body' => 'Zariadenie Node-A (ID_77) je znova pripojené.', 'type' => 'success'],
            ['title' => 'Neúspešné prihlásenie do konzoly', 'body' => 'Pokus o prihlásenie do webovej konzoly zlyhal.', 'type' => 'warning'],
            ['title' => 'Neúspešné prihlásenie do konzoly', 'body' => 'Pokus o prihlásenie cez klienta RustDesk zlyhal.', 'type' => 'warning'],
            ['title' => 'Veľa neúspešných pokusov (>30)', 'body' => 'Zariadenie ID_77 nahlásilo bezpečnostný alarm.', 'type' => 'failure'],
            ['title' => 'Opakované zlyhania vzdialeného pripojenia', 'body' => 'Zariadenie ID_77 nahlásilo viac ako 30 neúspešných pokusov o pripojenie.', 'type' => 'failure'],
        ], $payloads);
        $this->assertSame('en', app()->getLocale());
    }

    public function test_apprise_transport_warning_remains_canonical_across_locales(): void
    {
        Log::spy();
        config(['app.fallback_locale' => 'sk']);
        app()->setLocale('en');
        $this->configureApprise('console.login_failed');
        Http::fake(fn () => throw new \Illuminate\Http\Client\ConnectionException('socket down'));

        (new AppriseNotifications())->send('console.login_failed', 'Title', 'Body', 'tester');

        Log::shouldHaveReceived('warning')->with(
            'Apprise notification delivery failed.',
            \Mockery::on(fn (array $context): bool => ($context['event'] ?? null) === 'console.login_failed'
                && ($context['error'] ?? null) === 'socket down'),
        )->once();
    }

    public function test_localized_apprise_failure_wrapper_preserves_raw_transport_error(): void
    {
        $this->configureApprise('device.offline');
        config(['app.fallback_locale' => 'sk']);
        app()->setLocale('en');
        Http::fake(['https://apprise.example.test/*' => Http::response('upstream E_CONN Ω', 503)]);

        $delivery = app(AppriseNotifications::class)->send(
            'device.offline',
            'Device offline',
            'Node-A (ID_77) stopped heartbeating.',
            'device:ID_77',
        );

        $this->assertSame('Služba Apprise vrátila stav HTTP 503. upstream E_CONN Ω', $delivery?->error);
        $this->assertSame('device:ID_77', $delivery?->subject);
        $this->assertSame('en', app()->getLocale());
    }

    public function test_notification_catalogs_have_parity_and_matching_placeholders(): void
    {
        $en = require lang_path('en/notifications.php');
        $sk = require lang_path('sk/notifications.php');
        $flatten = function (array $catalog, string $prefix = '') use (&$flatten): array {
            $flat = [];
            foreach ($catalog as $key => $value) {
                $path = $prefix === '' ? $key : $prefix.'.'.$key;
                if (is_array($value)) {
                    $flat += $flatten($value, $path);
                } else {
                    $flat[$path] = $value;
                }
            }

            return $flat;
        };
        $en = $flatten($en);
        $sk = $flatten($sk);

        $this->assertSame(array_keys($en), array_keys($sk));
        foreach ($en as $key => $value) {
            preg_match_all('/:([A-Za-z_][A-Za-z0-9_]*)/', $value, $enMatches);
            preg_match_all('/:([A-Za-z_][A-Za-z0-9_]*)/', $sk[$key], $skMatches);
            $enMatches[1] = array_values(array_unique($enMatches[1]));
            $skMatches[1] = array_values(array_unique($skMatches[1]));
            sort($enMatches[1]);
            sort($skMatches[1]);
            $this->assertSame($enMatches[1], $skMatches[1], 'Placeholder mismatch at '.$key);
        }
    }

    public function test_localized_sources_do_not_reintroduce_known_hardcoded_user_copy(): void
    {
        $paths = [
            ...glob(resource_path('views/mail/*.blade.php')),
            ...glob(app_path('Mail/*.php')),
            app_path('Services/AppriseNotifications.php'),
            app_path('Console/Commands/CheckDeviceNotifications.php'),
        ];
        $source = implode("\n", array_map(fn (string $path): string => file_get_contents($path), $paths));

        foreach ([
            'Your sign-in code',
            'Reset your password',
            'Accept invitation',
            'Device pending approval',
            'Device offline',
            'Device recovered',
            'stopped heartbeating',
            'notification test',
            'Device presence notifications are disabled',
        ] as $hardcoded) {
            $this->assertStringNotContainsString($hardcoded, $source);
        }
    }

    private function configureApprise(?string $event = null): void
    {
        Setting::put('apprise_enabled', '1');
        Setting::put('apprise_endpoint', Crypt::encryptString('https://apprise.example.test'));
        Setting::put('apprise_config_key', Crypt::encryptString('config-key'));
        Setting::put('apprise_delivery_mode', AppriseNotifications::MODE_CONFIG);
        Setting::put('apprise_cooldown_minutes', '0');
        if ($event !== null) {
            Setting::put('apprise_event_'.str_replace('.', '_', $event), '1');
        }
    }
}
