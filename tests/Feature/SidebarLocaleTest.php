<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SidebarLocaleTest extends TestCase
{
    use RefreshDatabase;

    public function test_sidebar_catalog_has_complete_en_sk_parity(): void
    {
        $english = require lang_path('en/ui.php');
        $slovak = require lang_path('sk/ui.php');

        $this->assertArrayHasKey('sidebar', $english);
        $this->assertArrayHasKey('sidebar', $slovak);

        $englishKeys = array_keys($english['sidebar']);
        $slovakKeys = array_keys($slovak['sidebar']);
        sort($englishKeys);
        sort($slovakKeys);

        $this->assertSame($englishKeys, $slovakKeys);
        $this->assertNotContains('', array_values($english['sidebar']));
        $this->assertNotContains('', array_values($slovak['sidebar']));
    }

    public function test_sidebar_renders_in_slovak_via_session_locale(): void
    {
        $user = User::factory()->admin()->create(['locale' => 'sk']);

        $response = $this->actingAs($user)->get('/');

        $response->assertOk();
        $response->assertSee('Prehľad');
        $response->assertSee('Zariadenia');
        $response->assertSee('Skupiny');
        $response->assertSee('Používatelia');
        $response->assertSee('Nastavenia');
        $response->assertSee('Denníky');
    }

    public function test_sidebar_renders_in_slovak_via_accept_language_header(): void
    {
        $user = User::factory()->admin()->create();

        $response = $this->actingAs($user)
            ->withHeader('Accept-Language', 'sk-SK,sk;q=0.9,en;q=0.8')
            ->get('/');

        $response->assertOk();
        $response->assertSee('Prehľad');
        $response->assertSee('Zariadenia');
        $response->assertSee('Skupiny');
        $response->assertSee('Používatelia');
        $response->assertSee('Nastavenia');
        $response->assertSee('Denníky');
    }

    public function test_shared_layout_and_client_download_navigation_use_natural_slovak(): void
    {
        $user = User::factory()->admin()->create(['locale' => 'sk']);

        $response = $this->actingAs($user)->get('/');

        $response->assertOk();
        $response->assertSee('Vlastná konzola RustDesk');
        $response->assertDontSee('Self-hosted RustDesk console');
        $this->assertSame('Klient na stiahnutie', __('ui.sidebar.nav.client_downloads'));
    }

    public function test_sidebar_release_status_footer_is_localized_in_slovak(): void
    {
        $user = User::factory()->admin()->create(['locale' => 'sk']);

        $response = $this->actingAs($user)->get('/');

        $response->assertOk();
        // One of the three release-status footer strings must be present,
        // and it must be the Slovak translation, not the English one.
        $content = $response->getContent();
        $hasSlovakStatus = str_contains($content, 'Dostupná aktualizácia')
            || str_contains($content, 'Používate najnovšiu verziu')
            || str_contains($content, 'Kontrola vydania nie je dostupná');
        $this->assertTrue($hasSlovakStatus, 'No Slovak release-status footer text found');
        $this->assertSame('Používate najnovšiu verziu', (require lang_path('sk/ui.php'))['sidebar']['status']['latest_release']);

        $this->assertStringNotContainsString('Update available', $content);
        $this->assertStringNotContainsString('Running the latest release', $content);
        $this->assertStringNotContainsString('Release check unavailable', $content);
    }

    public function test_sidebar_does_not_leak_english_sidebar_only_literals_in_slovak_locale(): void
    {
        $user = User::factory()->admin()->create(['locale' => 'sk']);

        $response = $this->actingAs($user)->get('/');
        $response->assertOk();

        $content = $response->getContent();

        // These are sidebar-only literals: they must not appear anywhere
        // in the Slovak-locale response (other surfaces use their own
        // separately-owned catalogs and are out of scope here).
        $forbidden = [
            '>Overview<', '>Devices<', '>Manage<', '>Monitor<', '>System<', '>Settings<',
            '>Address Books<', '>Web Client<', '>Groups<', '>Users<', '>Roles<',
            '>Strategies<', '>Logs<', '>Connections<', '>File Transfers<', '>Alarms<',
            '>Logins<', '>Console<', '>Diagnostics<', '>Client Downloads<', '>Build Installers<',
            'Show Full Sidebar',
        ];

        foreach ($forbidden as $literal) {
            $this->assertStringNotContainsString($literal, $content, "Found English sidebar literal: {$literal}");
        }
    }

    public function test_sidebar_release_status_tooltips_are_localized_in_slovak(): void
    {
        $user = User::factory()->admin()->create(['locale' => 'sk']);

        $response = $this->actingAs($user)->get('/');
        $response->assertOk();

        $content = $response->getContent();

        $this->assertStringNotContainsString(
            'title="CortenDesk compares the running version against the latest published release."',
            $content
        );
        $this->assertStringNotContainsString(
            'is available. Opens the release notes.',
            $content
        );
    }

    public function test_sidebar_view_uses_translation_keys_not_hardcoded_english(): void
    {
        $source = file_get_contents(base_path('resources/views/layouts/partials/sidebar.blade.php'));

        $this->assertStringContainsString("__('ui.sidebar.", $source);

        $forbidden = [
            '> Overview <', '> Devices <', '>Manage<', '>Monitor<', '>System<', '> Settings <',
            '> Address Books <', '> Web Client <', '> Groups <', '> Users <', '> Roles <',
            '> Strategies <', '> Logs <', '>Connections<', '>File Transfers<', '>Alarms<',
            '>Logins<', '>Console<', '> Diagnostics <', '> Client Downloads <', '> Build Installers <',
            'title="Show Full Sidebar"',
            'Update available', 'Running the latest release', 'Release check unavailable',
            'Update to v{{',
            'title="CortenDesk compares the running version against the latest published release."',
            'is available. Opens the release notes.',
        ];

        foreach ($forbidden as $literal) {
            $this->assertStringNotContainsString($literal, $source, "sidebar.blade.php still contains: {$literal}");
        }
    }
}
