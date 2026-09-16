<?php

namespace Tests\Feature;

use App\Contracts\TcpProbe;
use App\Livewire\SettingsPage;
use App\Livewire\StrategyList;
use App\Models\Device;
use App\Models\User;
use App\Services\FleetDiagnostics;
use App\Services\MailSettings;
use App\Services\OidcService;
use App\Support\ClientPlatform;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Livewire\Livewire;
use Mockery\MockInterface;
use Tests\TestCase;

class SettingsLocaleTest extends TestCase
{
    use RefreshDatabase;

    public function test_settings_catalogs_have_complete_key_and_placeholder_parity(): void
    {
        $en = require lang_path('en/settings.php');
        $sk = require lang_path('sk/settings.php');

        $flatten = function (array $messages, string $prefix = '') use (&$flatten): array {
            $flat = [];
            foreach ($messages as $key => $value) {
                $path = $prefix === '' ? (string) $key : $prefix.'.'.$key;
                if (is_array($value)) {
                    $flat += $flatten($value, $path);
                } else {
                    $flat[$path] = $value;
                }
            }

            return $flat;
        };

        $english = $flatten($en);
        $slovak = $flatten($sk);
        $this->assertSame(array_keys($english), array_keys($slovak));
        $this->assertGreaterThanOrEqual(150, count($english));

        foreach ($english as $key => $message) {
            preg_match_all('/:[A-Za-z_][A-Za-z0-9_]*/', (string) $message, $enPlaceholders);
            preg_match_all('/:[A-Za-z_][A-Za-z0-9_]*/', (string) $slovak[$key], $skPlaceholders);
            $enPlaceholders[0] = array_values(array_unique($enPlaceholders[0]));
            $skPlaceholders[0] = array_values(array_unique($skPlaceholders[0]));
            sort($enPlaceholders[0]);
            sort($skPlaceholders[0]);
            $this->assertSame($enPlaceholders[0], $skPlaceholders[0], "Placeholder mismatch for [$key]");
            $this->assertNotSame('', trim((string) $slovak[$key]), "Empty Slovak translation for [$key]");
        }
    }

    public function test_scoped_surfaces_use_the_settings_catalog_instead_of_known_english_literals(): void
    {
        $paths = [
            resource_path('views/settings'),
            resource_path('views/setup'),
            resource_path('views/strategies'),
            resource_path('views/client-downloads'),
            resource_path('views/downloads'),
            resource_path('views/diagnostics'),
            resource_path('views/livewire/settings-page.blade.php'),
            resource_path('views/livewire/setup-wizard.blade.php'),
            resource_path('views/livewire/strategy-list.blade.php'),
            resource_path('views/livewire/client-download-manager.blade.php'),
        ];

        $source = '';
        foreach ($paths as $path) {
            if (is_dir($path)) {
                foreach (glob($path.'/*.blade.php') ?: [] as $file) {
                    $source .= file_get_contents($file);
                }
            } else {
                $source .= file_get_contents($path);
            }
        }

        $source = preg_replace('/{{--.*?--}}/s', '', $source) ?? $source;

        foreach (['Settings saved.', 'Client Setup', 'Add Strategy', 'Upload Build', 'Fleet diagnostics', 'Not configured.'] as $literal) {
            $this->assertStringNotContainsString($literal, $source, "Hard-coded UI text remains: [$literal]");
        }
        $this->assertStringContainsString("__('settings.", $source);
    }

    public function test_slovak_requests_render_each_localized_surface(): void
    {
        $admin = User::factory()->admin()->create();
        $headers = ['Accept-Language' => 'sk-SK,sk;q=0.9'];

        $this->actingAs($admin)->withHeaders($headers)->get('/settings')
            ->assertOk()->assertSee('Nastavenie klienta')->assertSee('Odchádzajúci e-mail');
        $this->actingAs($admin)->withHeaders($headers)->get('/setup')
            ->assertOk()->assertSee('Nastavte prvé zariadenie RustDesk');
        $this->actingAs($admin)->withHeaders($headers)->get('/strategies')
            ->assertOk()->assertSee('Stratégie')->assertSee('Pridať stratégiu');
        $this->actingAs($admin)->withHeaders($headers)->get('/client-downloads')
            ->assertOk()->assertSee('Klient na stiahnutie')->assertSee('Nahrať zostavu');
        $this->withHeaders($headers)->get('/downloads')
            ->assertOk()->assertSee('Klient na stiahnutie');
        $this->actingAs($admin)->withHeaders($headers)->get('/diagnostics')
            ->assertOk()->assertSee('Diagnostika zariadení')->assertSee('Súhrn zariadení');
    }

    public function test_settings_breadcrumbs_render_in_slovak(): void
    {
        $admin = User::factory()->admin()->create(['locale' => 'sk']);

        $this->actingAs($admin)->get('/strategies')
            ->assertOk()
            ->assertSee('Správa')
            ->assertDontSee('>Manage<', false);
        $this->actingAs($admin)->get('/settings')
            ->assertOk()
            ->assertSee('Systém')
            ->assertDontSee('>System<', false);
        $this->actingAs($admin)->get('/client-downloads')
            ->assertOk()
            ->assertSee('Systém')
            ->assertDontSee('>System<', false);
    }

    public function test_strategy_catalog_and_platform_labels_follow_the_active_locale(): void
    {
        app()->setLocale('sk');

        $catalog = StrategyList::catalog();
        $this->assertSame(['permissions', 'security', 'display'], \App\Models\Strategy::GROUP_ORDER);
        $this->assertSame('Oprávnenia', $catalog['permissions']['title']);
        $this->assertSame('Iné', ClientPlatform::label('unknown'));
        $this->assertSame('Univerzálna', ClientPlatform::archLabel('universal'));
    }

    public function test_slovak_operational_statuses_and_relay_labels_are_natural(): void
    {
        $flatten = function (array $messages, string $prefix = '') use (&$flatten): array {
            $flat = [];
            foreach ($messages as $key => $value) {
                $path = $prefix === '' ? (string) $key : $prefix.'.'.$key;
                $flat += is_array($value) ? $flatten($value, $path) : [$path => (string) $value];
            }

            return $flat;
        };

        foreach (['settings', 'notifications'] as $domain) {
            foreach ($flatten(require lang_path("sk/$domain.php")) as $key => $value) {
                $visibleValue = preg_replace('/:[A-Za-z_][A-Za-z0-9_]*/', '', $value) ?? $value;
                $this->assertDoesNotMatchRegularExpression('/\b(?:online|offline)\b/i', $visibleValue, "English status term remains at $domain.$key");
            }
        }

        $settings = $flatten(require lang_path('sk/settings.php'));
        $this->assertSame('Prenosový server (hbbr)', $settings['server.relay']);
        $this->assertSame('Prenosové servery', $settings['relays.title']);
        $this->assertSame('Prenosový server', $settings['diagnostics.relay_server']);
        $this->assertSame('Pripojené', $settings['diagnostics.online']);
        $this->assertSame('Odpojené', $settings['diagnostics.offline']);

        $diagnostics = file_get_contents(resource_path('views/diagnostics/index.blade.php'));
        $this->assertStringContainsString("__('settings.diagnostics.unknown')", $diagnostics);
    }

    public function test_slovak_diagnostics_distinguish_newest_and_unknown_versions_and_use_natural_copy(): void
    {
        app()->setLocale('sk');
        foreach ([['1.4.9', 'diag-newest'], ['1.4.6', 'diag-old'], [null, 'diag-unknown']] as [$version, $uuid]) {
            Device::create([
                'rustdesk_id' => (string) (700000000 + Device::query()->count()),
                'uuid' => $uuid,
                'version' => $version,
                'status' => Device::STATUS_ACTIVE,
            ]);
        }

        $tcp = new class implements TcpProbe
        {
            public function check(string $host, int $port, float $timeout): array
            {
                return ['ok' => true, 'latency_ms' => 1, 'error' => null];
            }
        };
        $diagnostics = new FleetDiagnostics($tcp);
        $report = $diagnostics->report();
        $versions = collect($report['fleet']['versions'])->keyBy('version');

        $this->assertSame('Newest or unknown', $versions['1.4.9']['status']);
        $this->assertSame('Behind newest fleet version', $versions['1.4.6']['status']);
        $this->assertSame('Newest or unknown', $versions['unknown']['status']);
        $this->assertSame('Readiness only; explicit endpoints or the APP_URL same-origin fallback are accepted. Remote endpoints are not contacted.', $report['services']['websocket_bridge']['note']);
        $this->assertContains($report['smtp']['note'], [
            'SMTP is not configured. No message is sent automatically.',
            'Configured, but no send result has been observed. No message is sent automatically.',
            'Health reflects the last observed send. No message is sent automatically.',
        ]);

        $sanitized = $diagnostics->sanitized();
        $this->assertSame($report['services']['websocket_bridge']['note'], $sanitized['services']['websocket_bridge']['note']);
        $this->assertSame($report['fleet']['versions'], $sanitized['fleet']['versions']);
        $this->assertSame($report['smtp']['note'], $sanitized['smtp']['note']);

        $admin = User::factory()->admin()->create(['locale' => 'sk']);
        $this->actingAs($admin)->get('/diagnostics')
            ->assertOk()
            ->assertSee('Najnovšia')
            ->assertSee('Staršia než najnovšia verzia v skupine')
            ->assertSee('Neznáma');

        $settings = require lang_path('sk/settings.php');
        $this->assertSame('Základné kontroly služieb a stavov zariadení. Testovací e-mail sa neodosiela automaticky.', $settings['diagnostics']['subtitle']);
        $this->assertSame('WebSocket premostenie', $settings['diagnostics']['websocket']);
        $this->assertSame('Bez hlásenia viac ako 24 h', $settings['diagnostics']['silent']);
        $this->assertSame('Časový limit dostupnosti (sekundy)', $settings['server']['online_window']);
        $this->assertSame('Adresa sprostredkovacieho servera vo formáte hostiteľ:port, ku ktorému sa klienti registrujú.', $settings['server']['id_help']);
        $this->assertSame('Server má nastavenú IP adresu', $settings['diagnostics']['configured_ip']);
        $this->assertSame(':host a používa port :port.', $settings['diagnostics']['on_port']);
    }

    public function test_diagnostics_total_renders_as_a_natural_slovak_count_phrase(): void
    {
        $admin = User::factory()->admin()->create(['locale' => 'sk']);
        foreach (range(1, 6) as $index) {
            Device::create([
                'rustdesk_id' => '62000000'.$index,
                'uuid' => 'diagnostics-plural-device-'.$index,
                'status' => Device::STATUS_ACTIVE,
            ]);
        }

        $this->actingAs($admin)->get('/diagnostics')
            ->assertOk()
            ->assertSeeInOrder(['Celkový počet', '6 zariadení']);
    }

    public function test_smtp_exception_keeps_its_detail_inside_a_localized_message(): void
    {
        config(['mail.default' => 'array']);
        Mail::shouldReceive('forgetMailers')->once();
        Mail::shouldReceive('to')->once()->andThrow(new \RuntimeException('smtp-secret-detail'));

        $settings = $this->createPartialMock(MailSettings::class, ['isConfigured']);
        $settings->method('isConfigured')->willReturn(true);

        app()->setLocale('sk');
        $result = $settings->test('operator@example.test');

        $this->assertFalse($result['ok']);
        $this->assertSame('Odoslanie zlyhalo: smtp-secret-detail', $result['message']);
    }

    public function test_oidc_connection_test_preserves_the_dynamic_provider_result(): void
    {
        app()->setLocale('sk');
        $admin = User::factory()->admin()->create();

        $this->mock(OidcService::class, function (MockInterface $mock): void {
            $mock->shouldReceive('isConfigured')->andReturnTrue();
            $mock->shouldReceive('test')->once()->andReturn([
                'ok' => false,
                'message' => 'oidc-provider-secret-detail',
            ]);
        });

        Livewire::actingAs($admin)
            ->test(SettingsPage::class)
            ->call('testOidc')
            ->assertSet('oidcTestOk', false)
            ->assertSet('oidcTestMessage', 'oidc-provider-secret-detail');
    }
}
