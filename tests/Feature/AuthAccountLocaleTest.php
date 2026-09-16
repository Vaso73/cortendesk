<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthAccountLocaleTest extends TestCase
{
    use RefreshDatabase;

    public function test_auth_catalogs_exist_and_have_recursive_key_and_placeholder_parity(): void
    {
        $english = require lang_path('en/auth.php');
        $slovak = require lang_path('sk/auth.php');

        $flatten = function (array $catalog, string $prefix = '') use (&$flatten): array {
            $flat = [];
            foreach ($catalog as $key => $value) {
                $path = $prefix === '' ? (string) $key : $prefix.'.'.$key;
                if (is_array($value)) {
                    $flat += $flatten($value, $path);
                } else {
                    $flat[$path] = $value;
                }
            }

            return $flat;
        };

        $en = $flatten($english);
        $sk = $flatten($slovak);
        $this->assertSame(array_keys($en), array_keys($sk));
        $this->assertNotEmpty($en);

        foreach ($en as $key => $value) {
            preg_match_all('/:[A-Za-z_][A-Za-z0-9_]*/', $value, $enPlaceholders);
            preg_match_all('/:[A-Za-z_][A-Za-z0-9_]*/', $sk[$key], $skPlaceholders);
            $enNames = array_values(array_unique($enPlaceholders[0]));
            $skNames = array_values(array_unique($skPlaceholders[0]));
            sort($enNames);
            sort($skNames);
            $this->assertSame($enNames, $skNames, "Placeholder mismatch at {$key}");
            $this->assertNotSame('', trim($sk[$key]), "Empty Slovak value at {$key}");
        }
    }

    public function test_every_referenced_auth_key_exists_in_both_catalogs(): void
    {
        $paths = [
            ...glob(resource_path('views/auth/*.blade.php')),
            ...glob(resource_path('views/account/*.blade.php')),
            resource_path('views/livewire/account-profile.blade.php'),
            resource_path('views/livewire/active-sessions.blade.php'),
            resource_path('views/livewire/two-factor-settings.blade.php'),
            app_path('Livewire/AccountProfile.php'),
            app_path('Livewire/TwoFactorSettings.php'),
            app_path('Http/Controllers/AuthController.php'),
            app_path('Http/Controllers/PasswordResetController.php'),
            app_path('Http/Controllers/OidcController.php'),
        ];

        foreach ($paths as $path) {
            if (str_ends_with($path, '/invite.blade.php')) {
                continue;
            }

            preg_match_all("/(?:__|trans_choice)\\(['\"](auth\\.[A-Za-z0-9_.]+)['\"]/", file_get_contents($path), $matches);
            foreach (array_unique($matches[1]) as $key) {
                $this->assertIsString(trans($key, [], 'en'), "Missing English key {$key}");
                $this->assertIsString(trans($key, [], 'sk'), "Missing Slovak key {$key}");
                $this->assertNotSame($key, trans($key, [], 'en'), "Missing English key {$key}");
                $this->assertNotSame($key, trans($key, [], 'sk'), "Missing Slovak key {$key}");
            }
        }
    }

    public function test_slovak_challenge_switches_to_another_user_naturally(): void
    {
        $catalog = require lang_path('sk/auth.php');

        $this->assertSame('Prihlásiť sa ako iný používateľ', $catalog['email_challenge']['switch_user']);
    }

    public function test_slovak_login_validation_is_localized(): void
    {
        $this->withSession(['locale' => 'sk'])->post('/login', [])
            ->assertSessionHasErrors([
                'username' => 'Pole používateľské meno je povinné.',
                'password' => 'Pole heslo je povinné.',
            ]);
    }

    public function test_auth_views_do_not_keep_user_facing_english_literals(): void
    {
        $paths = [
            'resources/views/auth/login.blade.php',
            'resources/views/auth/email-challenge.blade.php',
            'resources/views/auth/password-request.blade.php',
            'resources/views/auth/password-reset.blade.php',
            'resources/views/auth/two-factor-challenge.blade.php',
            'resources/views/auth/oidc-client-result.blade.php',
        ];
        $forbidden = [
            'Check your email', 'Verification code', 'Send a new code',
            'Reset Password', 'Choose a New Password', 'Two-Step Verification',
            'Authentication code', 'Signed in to RustDesk', 'Sign-in failed',
        ];

        foreach ($paths as $path) {
            $source = file_get_contents(base_path($path));
            $this->assertStringContainsString("__('auth.", $source, $path);
            foreach ($forbidden as $literal) {
                $this->assertStringNotContainsString($literal, $source, "{$path} contains {$literal}");
            }
        }
    }

    public function test_account_views_and_livewire_components_localize_user_facing_text(): void
    {
        $paths = [
            'resources/views/account/index.blade.php',
            'resources/views/account/two-factor.blade.php',
            'resources/views/livewire/account-profile.blade.php',
            'resources/views/livewire/active-sessions.blade.php',
            'resources/views/livewire/two-factor-settings.blade.php',
            'app/Livewire/AccountProfile.php',
            'app/Livewire/TwoFactorSettings.php',
        ];
        $forbidden = [
            'My Account', 'Your account has no email address', 'Profile saved.',
            'Save Changes', 'Change Password', 'Active Sessions',
            'No active sessions', 'Disconnecting…', 'Save your recovery codes',
            'Regenerate recovery codes', 'Enable two-factor authentication',
            'That password is incorrect.',
        ];

        foreach ($paths as $path) {
            $source = file_get_contents(base_path($path));
            $userFacingSource = preg_replace('/{{--.*?--}}|\/\*.*?\*\//s', '', $source);
            $this->assertStringContainsString("__('auth.", $userFacingSource, $path);
            foreach ($forbidden as $literal) {
                $this->assertStringNotContainsString($literal, $userFacingSource, "{$path} contains {$literal}");
            }
        }
    }

    public function test_slovak_login_uses_the_auth_catalog(): void
    {
        $this->withSession(['locale' => 'sk'])->get('/login')
            ->assertOk()
            ->assertSee('Prihlásenie')
            ->assertSee('Používateľské meno');
    }

    public function test_account_restriction_banners_use_localized_stable_messages(): void
    {
        $account = file_get_contents(resource_path('views/account/index.blade.php'));
        $twoFactor = file_get_contents(resource_path('views/account/two-factor.blade.php'));

        $this->assertStringNotContainsString("{{ session('email_required') }}", $account);
        $this->assertStringContainsString("__('auth.account.email_required')", $account);
        $this->assertStringNotContainsString("{{ session('twofactor_enforced') }}", $twoFactor);
        $this->assertStringContainsString("__('auth.two_factor.enforced')", $twoFactor);
    }

    public function test_active_session_types_are_localized_instead_of_using_model_english(): void
    {
        $source = file_get_contents(resource_path('views/livewire/active-sessions.blade.php'));

        $this->assertStringNotContainsString('AuditConnection::typeLabel', $source);
        foreach (['remote_control', 'file_transfer', 'port_forwarding', 'view_camera', 'terminal', 'unknown_type'] as $key) {
            $this->assertStringContainsString("auth.sessions.{$key}", $source);
        }
    }

    public function test_owned_blades_have_no_untranslated_static_user_text(): void
    {
        $paths = [
            'resources/views/auth/login.blade.php',
            'resources/views/auth/email-challenge.blade.php',
            'resources/views/auth/password-request.blade.php',
            'resources/views/auth/password-reset.blade.php',
            'resources/views/auth/two-factor-challenge.blade.php',
            'resources/views/auth/oidc-client-result.blade.php',
            'resources/views/account/index.blade.php',
            'resources/views/account/two-factor.blade.php',
            'resources/views/livewire/account-profile.blade.php',
            'resources/views/livewire/active-sessions.blade.php',
            'resources/views/livewire/two-factor-settings.blade.php',
        ];
        $allowedText = ['Corten', 'Desk'];

        foreach ($paths as $path) {
            $source = preg_replace('/{{--.*?--}}|@php.*?@endphp/s', '', file_get_contents(base_path($path)));
            $source = preg_replace('/@(?:if|elseif|else|endif|unless|endunless|foreach|endforeach|switch|endswitch|case|break|default)\b[^\n]*/', '', $source);

            preg_match_all('/>([^<>]+)</s', $source, $nodes);
            foreach ($nodes[1] as $node) {
                if (str_contains($node, '{{') || str_contains($node, '}}') || str_contains($node, '{!!') || str_contains($node, '@') || str_contains($node, '$')) {
                    continue;
                }

                $text = trim(html_entity_decode(preg_replace('/\s+/', ' ', $node)));
                if ($text !== '' && preg_match('/\pL/u', $text)) {
                    $this->assertContains($text, $allowedText, "Untranslated text in {$path}: {$text}");
                }
            }

            preg_match_all('/\b(?:title|placeholder|aria-label|wire:confirm)="([^"]+)"/', $source, $attributes);
            foreach ($attributes[1] as $value) {
                if (! str_contains($value, '{{') && preg_match('/\pL/u', $value)) {
                    $this->fail("Untranslated user-facing attribute in {$path}: {$value}");
                }
            }
        }
    }

    public function test_owned_php_surfaces_use_translation_keys_for_user_messages(): void
    {
        $paths = [
            'app/Http/Controllers/AuthController.php',
            'app/Http/Controllers/PasswordResetController.php',
            'app/Http/Controllers/OidcController.php',
            'app/Livewire/AccountProfile.php',
            'app/Livewire/TwoFactorSettings.php',
        ];

        foreach ($paths as $path) {
            $source = preg_replace('/\/\*.*?\*\/|\/\/[^\n]*/s', '', file_get_contents(base_path($path)));
            $this->assertDoesNotMatchRegularExpression(
                '/(?:withErrors|addError|withMessages)\s*\([^;]*?["\'][A-Z][^"\']*["\']/s',
                $source,
                "Hard-coded user message in {$path}",
            );
        }
    }

    public function test_oidc_client_result_localizes_the_shell_but_preserves_the_result_message(): void
    {
        app()->setLocale('sk');
        $message = 'Provider result detail';

        $this->view('auth.oidc-client-result', ['ok' => false, 'message' => $message])
            ->assertSee(__('auth.oidc.client_failure_heading'))
            ->assertSee($message);
    }

    public function test_oidc_controller_localizes_static_errors_without_replacing_dynamic_results(): void
    {
        $source = file_get_contents(app_path('Http/Controllers/OidcController.php'));

        $this->assertStringContainsString("__('auth.oidc.not_enabled')", $source);
        $this->assertStringContainsString("withErrors(['username' => \$e->getMessage()])", $source);
        $this->assertStringContainsString("withErrors(['username' => \$outcome['message']])", $source);
    }
}
