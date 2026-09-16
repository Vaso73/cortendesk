<?php

namespace Tests\Feature;

use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Lang;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class LocaleFoundationTest extends TestCase
{
    use RefreshDatabase;

    public function test_locale_middleware_is_prepended_but_prioritized_after_the_session(): void
    {
        $bootstrap = file_get_contents(base_path('bootstrap/app.php'));

        $this->assertStringContainsString("prependToGroup('web', SetLocale::class)", $bootstrap);
        $this->assertStringContainsString('appendToPriorityList(StartSession::class, SetLocale::class)', $bootstrap);
    }

    public function test_existing_users_remain_compatible_with_nullable_locale(): void
    {
        $user = User::factory()->create();

        $this->assertTrue(Schema::hasColumn('users', 'locale'));
        $this->assertNull($user->fresh()->locale);
        $this->assertNull($user->preferredLocale());
    }

    public function test_guest_can_select_locale_in_the_session_with_a_safe_local_redirect(): void
    {
        $response = $this->from('/login')->post('/locale', [
            'locale' => 'sk-SK',
            'redirect' => '/login',
        ]);

        $response->assertRedirect('/login');
        $response->assertSessionHas('locale', 'sk');
        $response->assertSessionHas('status', 'Jazyk bol zmenený.');

        $this->post('/locale', ['locale' => 'en', 'redirect' => '//evil.example'])
            ->assertRedirect('/login');
    }

    public function test_locale_redirect_rejects_network_absolute_backslash_and_control_targets(): void
    {
        foreach ([
            '//evil.example',
            '/\\evil.example',
            'https://evil.example/path',
            'http://evil.example/path',
            "/settings\nX-Injected: yes",
        ] as $target) {
            $this->post('/locale', ['locale' => 'en', 'redirect' => $target])
                ->assertRedirect('/login');
        }
    }

    public function test_locale_redirect_preserves_a_local_path_query_and_fragment(): void
    {
        $this->post('/locale', [
            'locale' => 'en',
            'redirect' => '/settings?tab=x#y',
        ])->assertRedirect('/settings?tab=x#y');
    }

    public function test_authenticated_selection_is_persisted(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post('/locale', [
            'locale' => 'sk',
            'redirect' => '/',
        ])->assertRedirect('/');

        $this->assertSame('sk', $user->fresh()->locale);
        $this->assertSame('sk', session('locale'));
    }

    public function test_two_factor_restricted_user_can_persist_locale_from_the_enrollment_screen(): void
    {
        Setting::put('two_factor_required', '1');
        $user = User::factory()->create();

        $this->actingAs($user)->post('/locale', [
            'locale' => 'sk',
            'redirect' => '/account/two-factor',
        ])->assertRedirect('/account/two-factor');

        $this->assertSame('sk', $user->fresh()->locale);
        $this->assertSame('sk', session('locale'));
    }

    public function test_email_address_restricted_user_can_persist_locale_from_the_account_screen(): void
    {
        Setting::put('email_login_verification', '1');
        Setting::put('smtp_enabled', '1');
        Setting::put('smtp_host', 'mail.example.test');
        Setting::put('smtp_from_address', 'console@example.test');
        $user = User::factory()->create(['email' => null]);

        $this->actingAs($user)->post('/locale', [
            'locale' => 'sk',
            'redirect' => '/account',
        ])->assertRedirect('/account');

        $this->assertSame('sk', $user->fresh()->locale);
        $this->assertSame('sk', session('locale'));
    }

    public function test_mail_repair_restricted_user_can_persist_locale_from_the_settings_screen(): void
    {
        Setting::put('email_login_verification', '1');
        Setting::put('smtp_enabled', '1');
        Setting::put('smtp_host', 'mail.example.test');
        Setting::put('smtp_from_address', 'console@example.test');
        Setting::put('smtp_failed_at', '2026-09-15 12:00:00');
        $user = User::factory()->admin()->create();

        $this->actingAs($user)->withSession(['mail_repair' => true])->post('/locale', [
            'locale' => 'sk',
            'redirect' => '/settings?tab=email',
        ])->assertRedirect('/settings?tab=email');

        $this->assertSame('sk', $user->fresh()->locale);
        $this->assertSame('sk', session('locale'));
    }

    public function test_php_config_is_loaded_from_the_shared_json_registry(): void
    {
        $registryPath = config_path('locales.json');

        $this->assertFileExists($registryPath);
        $registry = json_decode((string) file_get_contents($registryPath), true, flags: JSON_THROW_ON_ERROR);
        $this->assertSame($registry, config('locales'));
    }

    public function test_shared_locale_registry_survives_laravel_config_cache(): void
    {
        $registry = json_decode(
            (string) file_get_contents(config_path('locales.json')),
            true,
            flags: JSON_THROW_ON_ERROR
        );

        try {
            $this->assertSame(0, Artisan::call('config:cache'));
            $cached = require app()->getCachedConfigPath();
            $this->assertSame($registry, $cached['locales']);
        } finally {
            Artisan::call('config:clear');
        }
    }

    public function test_english_is_the_unchanged_default_and_missing_slovak_keys_fall_back(): void
    {
        $response = $this->get('/login');

        $response->assertOk();
        $response->assertSee('<html lang="en" dir="ltr"', false);
        $response->assertSee('Sign In');
        $this->assertSame('English-only fallback', __('ui.fallback_probe'));
    }

    public function test_accept_language_renders_the_slovak_login_shell(): void
    {
        $response = $this->withHeader('Accept-Language', 'sk-SK,sk;q=0.9,en;q=0.8')->get('/login');

        $response->assertOk();
        $response->assertSee('<html lang="sk" dir="ltr"', false);
        $response->assertSee('Prihlásenie');
        $response->assertSee('Používateľské meno');
    }

    public function test_api_requests_do_not_resolve_accept_language(): void
    {
        app()->setLocale('en');

        $this->withHeader('Accept-Language', 'sk-SK')->get('/api/version')->assertOk();

        $this->assertSame('en', app()->getLocale());
    }
}
