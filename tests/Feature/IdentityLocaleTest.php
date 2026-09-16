<?php

namespace Tests\Feature;

use App\Models\ApiToken;
use App\Support\Permissions;
use Illuminate\Support\Arr;
use Tests\TestCase;

class IdentityLocaleTest extends TestCase
{
    /** @return array<string, mixed> */
    private function catalogue(string $locale): array
    {
        return require lang_path($locale.'/identity.php');
    }

    public function test_english_and_slovak_identity_catalogues_have_complete_key_and_placeholder_parity(): void
    {
        $english = Arr::dot($this->catalogue('en'));
        $slovak = Arr::dot($this->catalogue('sk'));

        $this->assertGreaterThanOrEqual(180, count($english));
        $this->assertSame(array_keys($english), array_keys($slovak));
        $this->assertNotContains('', $english, true);
        $this->assertNotContains('', $slovak, true);

        foreach ($english as $key => $value) {
            preg_match_all('/(?<!:):[A-Za-z_][A-Za-z0-9_]*/', (string) $value, $englishPlaceholders);
            preg_match_all('/(?<!:):[A-Za-z_][A-Za-z0-9_]*/', (string) $slovak[$key], $slovakPlaceholders);
            $englishPlaceholders[0] = array_values(array_unique($englishPlaceholders[0]));
            $slovakPlaceholders[0] = array_values(array_unique($slovakPlaceholders[0]));
            sort($englishPlaceholders[0]);
            sort($slovakPlaceholders[0]);
            $this->assertSame($englishPlaceholders[0], $slovakPlaceholders[0], "Placeholder mismatch for {$key}");
        }
    }

    public function test_identity_catalogue_has_natural_slovak_contracts_and_correct_plurals(): void
    {
        app()->setLocale('sk');

        $this->assertSame('Používatelia', __('identity.users.title'));
        $this->assertSame('Pozvať používateľa', __('identity.invitations.actions.invite'));
        $this->assertSame('Tokeny API', __('identity.api_tokens.title'));
        $this->assertSame('1 používateľ', trans_choice('identity.roles.user_count', 1, ['count' => 1]));
        $this->assertSame('3 používatelia', trans_choice('identity.roles.user_count', 3, ['count' => 3]));
        $this->assertSame('5 používateľov', trans_choice('identity.roles.user_count', 5, ['count' => 5]));
        $this->assertSame('1 zariadenie', trans_choice('identity.users.device_count', 1, ['count' => 1]));
        $this->assertSame('2 zariadenia', trans_choice('identity.users.device_count', 2, ['count' => 2]));
        $this->assertSame('8 zariadení', trans_choice('identity.users.device_count', 8, ['count' => 8]));
    }

    public function test_every_identity_translation_reference_resolves_in_both_catalogues(): void
    {
        $paths = [
            ...glob(resource_path('views/users/*.blade.php')),
            ...glob(resource_path('views/roles/*.blade.php')),
            resource_path('views/auth/invite.blade.php'),
            resource_path('views/livewire/user-list.blade.php'),
            resource_path('views/livewire/role-list.blade.php'),
            resource_path('views/livewire/invitation-manager.blade.php'),
            resource_path('views/livewire/api-token-manager.blade.php'),
            app_path('Livewire/UserList.php'), app_path('Livewire/RoleList.php'),
            app_path('Livewire/InvitationManager.php'), app_path('Livewire/ApiTokenManager.php'),
            app_path('Http/Controllers/InvitationController.php'), app_path('Support/Permissions.php'),
        ];

        foreach ($paths as $path) {
            preg_match_all('/identity\.[A-Za-z0-9_.]+/', file_get_contents($path), $matches);
            foreach (array_unique($matches[0]) as $reference) {
                $key = rtrim(substr($reference, strlen('identity.')), '.');
                $this->assertTrue(Arr::has($this->catalogue('en'), $key), "Missing English key {$reference} in {$path}");
                $this->assertTrue(Arr::has($this->catalogue('sk'), $key), "Missing Slovak key {$reference} in {$path}");
            }
        }
    }

    public function test_permission_ids_and_token_contracts_are_not_localized(): void
    {
        $this->assertSame(['none', 'r', 'rw'], Permissions::LEVELS);
        $this->assertSame(
            ['device', 'user', 'group', 'address_book', 'audit', 'strategy', 'setting', 'token'],
            Permissions::CONSOLE_RESOURCES,
        );
        $this->assertSame(['device', 'user', 'group', 'strategy', 'address_book', 'audit'], ApiToken::RESOURCES);
        $this->assertSame('device', array_key_first(Permissions::resourceLabels()));
    }

    public function test_identity_views_do_not_contain_hardcoded_user_facing_english(): void
    {
        $paths = [
            resource_path('views/users/index.blade.php'),
            resource_path('views/roles/index.blade.php'),
            resource_path('views/auth/invite.blade.php'),
            resource_path('views/livewire/user-list.blade.php'),
            resource_path('views/livewire/role-list.blade.php'),
            resource_path('views/livewire/invitation-manager.blade.php'),
            resource_path('views/livewire/api-token-manager.blade.php'),
        ];

        foreach ($paths as $path) {
            $source = preg_replace('/\{\{--.*?--\}\}/s', '', file_get_contents($path));
            $source = preg_replace('/@php.*?@endphp/s', '', (string) $source);
            $failures = [];

            foreach (preg_split('/\R/', (string) $source) as $number => $line) {
                if (preg_match('/(?:placeholder|aria-label|title|wire:confirm)="(?=[^"]*[A-Za-z])([^"]*)"/', $line, $match)
                    && ! str_contains($match[1], "__('identity.")
                    && ! str_starts_with(trim($match[1]), '{{')
                    && $match[1] !== 'CortenDesk') {
                    $failures[] = ($number + 1).': '.$line;
                }

                $visibleLine = preg_replace('/\{\{.*?\}\}/', '', $line);
                if (preg_match('/>([^<]*[A-Za-z][^<]*)</', (string) $visibleLine, $match)) {
                    $visible = preg_replace('/@[a-zA-Z]+(?:\([^)]*\))?|&[a-z]+;|[[:punct:]\d\s]+/u', '', $match[1]);
                    if (preg_match('/[A-Za-z]{2}/', (string) $visible)
                        && ! str_contains($line, "__('identity.")
                        && ! str_contains($line, 'auth-brand-wordmark')) {
                        $failures[] = ($number + 1).': '.$line;
                    }
                }
            }

            $this->assertSame([], array_values(array_unique($failures)), "Hardcoded identity UI text in {$path}");
        }
    }

    public function test_identity_runtime_messages_are_translated_instead_of_hardcoded(): void
    {
        $paths = [
            app_path('Livewire/UserList.php'),
            app_path('Livewire/RoleList.php'),
            app_path('Livewire/InvitationManager.php'),
            app_path('Livewire/ApiTokenManager.php'),
            app_path('Http/Controllers/InvitationController.php'),
            app_path('Support/Permissions.php'),
        ];
        $forbidden = [
            'That address already has an account',
            'That username is already taken',
            'Grant at least one resource permission',
            'The passwords do not match',
            'Please confirm your password',
            'An account already uses this username',
            'This invitation has already been used',
            'This invitation is no longer valid',
            "public const LEVEL_LABELS = ['none' => 'None'",
            "'device' => 'View grants the Devices screen",
        ];

        foreach ($paths as $path) {
            $source = file_get_contents($path);
            foreach ($forbidden as $literal) {
                $this->assertStringNotContainsString($literal, $source, "Hardcoded runtime text in {$path}");
            }
        }
    }
}
