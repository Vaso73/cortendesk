<?php

namespace Tests\Feature;

use App\Livewire\DeviceList;
use App\Models\Device;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use RecursiveArrayIterator;
use RecursiveIteratorIterator;
use Tests\TestCase;

class DeviceLocaleTest extends TestCase
{
    use RefreshDatabase;

    private const TECHNICAL_UI_LITERAL_ALLOWLIST = ['CPU', 'ID', 'IP', 'OS', 'RustDesk', 'UUID'];

    private const SCOPED_FILES = [
        'app/Http/Controllers/DashboardController.php',
        'resources/views/overview.blade.php',
        'resources/views/devices/index.blade.php',
        'resources/views/devices/show.blade.php',
        'resources/views/groups/index.blade.php',
        'resources/views/livewire/device-detail.blade.php',
        'resources/views/livewire/device-list.blade.php',
        'resources/views/livewire/device-list-pending.blade.php',
        'resources/views/livewire/live-stats.blade.php',
        'resources/views/livewire/group-list.blade.php',
        'app/Livewire/DeviceDetail.php',
        'app/Livewire/DeviceList.php',
        'app/Livewire/LiveStats.php',
        'app/Livewire/GroupList.php',
    ];

    public function test_device_catalogs_have_complete_en_sk_parity(): void
    {
        $english = require lang_path('en/devices.php');
        $slovak = require lang_path('sk/devices.php');

        $flatEnglish = $this->flatten($english);
        $flatSlovak = $this->flatten($slovak);
        $englishKeys = array_keys($flatEnglish);
        $slovakKeys = array_keys($flatSlovak);
        sort($englishKeys);
        sort($slovakKeys);

        $this->assertSame($englishKeys, $slovakKeys);
        $this->assertGreaterThanOrEqual(150, count($englishKeys));
        $this->assertNotContains('', array_values($flatEnglish));
        $this->assertNotContains('', array_values($flatSlovak));

        foreach ($flatEnglish as $key => $value) {
            preg_match_all('/:([A-Za-z_][A-Za-z0-9_]*)/', $value, $englishArguments);
            preg_match_all('/:([A-Za-z_][A-Za-z0-9_]*)/', $flatSlovak[$key], $slovakArguments);
            $expected = array_values(array_unique($englishArguments[1]));
            $actual = array_values(array_unique($slovakArguments[1]));
            sort($expected);
            sort($actual);
            $this->assertSame($expected, $actual, "Placeholder mismatch at devices.$key");
        }
    }

    public function test_slovak_device_count_uses_natural_plural_forms_including_teens(): void
    {
        app()->setLocale('sk');

        $this->assertSame('1 zariadenie', trans_choice('devices.count.device', 1, ['count' => 1]));
        $this->assertSame('2 zariadenia', trans_choice('devices.count.device', 2, ['count' => 2]));
        $this->assertSame('4 zariadenia', trans_choice('devices.count.device', 4, ['count' => 4]));
        $this->assertSame('12 zariadení', trans_choice('devices.count.device', 12, ['count' => 12]));
        $this->assertSame('14 zariadení', trans_choice('devices.count.device', 14, ['count' => 14]));
        foreach ([
            21 => '21 zariadení',
            22 => '22 zariadení',
            24 => '24 zariadení',
            25 => '25 zariadení',
            102 => '102 zariadení',
            122 => '122 zariadení',
        ] as $count => $expected) {
            $this->assertSame($expected, trans_choice('devices.count.device', $count, ['count' => $count]));
        }
        $this->assertSame('0 zariadení', trans_choice('devices.count.device', 0, ['count' => 0]));
        foreach ([
            0 => 'zariadení', 1 => 'zariadenie', 2 => 'zariadenia', 3 => 'zariadenia', 4 => 'zariadenia',
            6 => 'zariadení', 11 => 'zariadení', 12 => 'zariadení', 14 => 'zariadení',
            21 => 'zariadení', 22 => 'zariadení', 26 => 'zariadení',
        ] as $count => $expected) {
            $this->assertSame($expected, trans_choice('devices.count.device_label', DeviceList::pluralSelector($count)));
        }

        app()->setLocale('en');
        $this->assertSame('device', trans_choice('devices.count.device_label', DeviceList::pluralSelector(1)));
        $this->assertSame('devices', trans_choice('devices.count.device_label', DeviceList::pluralSelector(6)));
    }

    public function test_device_summary_uses_the_correct_slovak_form_for_six_devices(): void
    {
        $user = User::factory()->admin()->create(['locale' => 'sk']);
        foreach (range(1, 6) as $index) {
            Device::create([
                'rustdesk_id' => '60000000'.$index,
                'uuid' => 'plural-device-'.$index,
                'status' => Device::STATUS_ACTIVE,
            ]);
        }

        $this->actingAs($user)->get('/devices')
            ->assertOk()
            ->assertSeeText('6 zariadení')
            ->assertDontSeeText('6 Zariadenia');
    }

    public function test_dashboard_device_counters_render_as_natural_slovak_count_phrases(): void
    {
        $user = User::factory()->admin()->create(['locale' => 'sk']);
        foreach (range(1, 6) as $index) {
            Device::create([
                'rustdesk_id' => '61000000'.$index,
                'uuid' => 'dashboard-plural-device-'.$index,
                'status' => Device::STATUS_ACTIVE,
            ]);
        }

        $this->actingAs($user)->get('/')
            ->assertOk()
            ->assertSeeInOrder(['Celkový počet', '6 zariadení'])
            ->assertDontSee('<div class="rd-stat-label">Zariadenia</div>', false);

        $overview = file_get_contents(resource_path('views/overview.blade.php'));
        $this->assertStringContainsString("trans_choice('devices.count.device_label', \App\Livewire\DeviceList::pluralSelector(\$platformTotal))", $overview);
        $this->assertStringNotContainsString("@json(__('devices.common.devices'))", $overview);
    }

    public function test_dashboard_connection_chart_metadata_follows_slovak_locale(): void
    {
        $user = User::factory()->admin()->create(['locale' => 'sk']);
        Carbon::setTestNow(Carbon::parse('2026-09-15 12:00:00'));

        try {
            $this->actingAs($user)->get('/')
                ->assertOk()
                ->assertViewHas('connectionSeries', fn (array $series): bool =>
                    array_column($series['series'], 'name') === ['Vzdialené ovládanie', 'Prenos súborov', 'Iné']
                    && $series['labels'][0] === '2. sep'
                    && $series['labels'][13] === '15. sep'
                );
        } finally {
            Carbon::setTestNow();
        }
    }

    public function test_dashboard_chart_metadata_and_unknown_labels_remain_english_for_english_locale(): void
    {
        $user = User::factory()->admin()->create(['locale' => 'en']);
        Device::create([
            'rustdesk_id' => '620000000',
            'uuid' => 'dashboard-english-unknown',
            'os' => '',
            'version' => '',
            'status' => Device::STATUS_ACTIVE,
        ]);
        Carbon::setTestNow(Carbon::parse('2026-09-15 12:00:00'));

        try {
            $this->actingAs($user)->get('/')
                ->assertOk()
                ->assertViewHas('connectionSeries', fn (array $series): bool =>
                    array_column($series['series'], 'name') === ['Remote Control', 'File Transfer', 'Other']
                    && $series['labels'][0] === 'Sep 2'
                    && $series['labels'][13] === 'Sep 15'
                )
                ->assertViewHas('platformMix', fn (array $mix): bool => $mix['labels'] === ['Unknown'])
                ->assertViewHas('versionCounts', fn (array $versions): bool => $versions['labels'] === ['Unknown']);
        } finally {
            Carbon::setTestNow();
        }
    }

    public function test_dashboard_unknown_platform_follows_slovak_locale(): void
    {
        $user = User::factory()->admin()->create(['locale' => 'sk']);
        Device::create([
            'rustdesk_id' => '620000001',
            'uuid' => 'dashboard-unknown-platform',
            'os' => '',
            'version' => '1.4.9',
            'status' => Device::STATUS_ACTIVE,
        ]);

        $this->actingAs($user)->get('/')
            ->assertOk()
            ->assertViewHas('platformMix', fn (array $mix): bool => $mix['labels'] === ['Neznáma']);
    }

    public function test_dashboard_unknown_version_follows_slovak_locale(): void
    {
        $user = User::factory()->admin()->create(['locale' => 'sk']);
        Device::create([
            'rustdesk_id' => '620000002',
            'uuid' => 'dashboard-unknown-version',
            'os' => 'Windows 11',
            'version' => '',
            'status' => Device::STATUS_ACTIVE,
        ]);

        $this->actingAs($user)->get('/')
            ->assertOk()
            ->assertViewHas('versionCounts', fn (array $versions): bool => $versions['labels'] === ['Neznáma']);
    }

    public function test_devices_groups_and_dashboard_render_in_slovak(): void
    {
        $user = User::factory()->admin()->create(['locale' => 'sk']);

        $this->actingAs($user)->get('/devices')
            ->assertOk()
            ->assertSee('Zariadenia')
            ->assertSee('Hľadať podľa ID, názvu, hostiteľa, používateľa alebo IP…');

        $this->actingAs($user)->get('/groups')
            ->assertOk()
            ->assertSee('Skupiny zariadení')
            ->assertSee('Pridať skupinu');

        $this->actingAs($user)->get('/')
            ->assertOk()
            ->assertSee('Prehľad')
            ->assertSee('Platformy zariadení');
    }

    public function test_slovak_device_statuses_and_group_copy_use_natural_terms(): void
    {
        $catalog = $this->flatten(require lang_path('sk/devices.php'));

        foreach ($catalog as $key => $value) {
            $this->assertDoesNotMatchRegularExpression('/\b(?:online|offline)\b/i', $value, "English status term remains at devices.$key");
        }

        $this->assertSame('Pripojené', $catalog['common.online']);
        $this->assertSame('Odpojené', $catalog['common.offline']);
        $this->assertSame('Priečinky zariadení. Zariadenie môže patriť najviac do jednej skupiny.', $catalog['groups.device_intro']);
        $this->assertSame('Prístup používateľských skupín', $catalog['groups.accessed_from']);
    }

    public function test_bulk_device_feedback_and_validation_follow_the_active_locale(): void
    {
        $user = User::factory()->admin()->create(['locale' => 'sk']);
        $device = Device::create(['rustdesk_id' => '123456789', 'uuid' => 'test-device']);

        app()->setLocale('sk');
        Livewire::actingAs($user)
            ->test(DeviceList::class)
            ->set('selected', [(string) $device->id])
            ->call('bulkDelete')
            ->assertSet('bulkResult', '1 zariadenie bolo presunuté do koša.');

        $device->restore();
        Livewire::actingAs($user)
            ->test(DeviceList::class)
            ->set('selected', [(string) $device->id])
            ->set('moveGroupId', -1)
            ->call('moveSelectedToGroup')
            ->assertHasErrors(['moveGroupId' => 'Vyberte skupinu zariadení.']);
    }

    public function test_scoped_surfaces_do_not_reintroduce_known_hardcoded_user_visible_english(): void
    {
        $forbidden = [
            'Show every device', 'Search ID, alias, hostname, user, IP', 'All statuses',
            'Back to Devices', 'Add Device', 'Recycle Bin', 'Move to Group',
            'No devices match your filters', 'Pending approval',
            'Device Groups', 'User Groups', 'Add Group', 'No note', 'Accessed from',
            'Connect your first device', 'Device Platforms', 'Client Versions',
            'Remote Control', 'File Transfer', 'Other', 'Unknown',
            'View all devices', 'Online now', 'No change vs last 14 days',
            'Recent connections', 'Recent file transfers', 'Activity history needs',
        ];

        $technicalFound = [];
        foreach (self::SCOPED_FILES as $file) {
            $source = file_get_contents(base_path($file));
            if (! str_ends_with($file, '.blade.php')) {
                $source = implode("\n", array_map(
                    fn ($token) => is_array($token) && $token[0] === T_CONSTANT_ENCAPSED_STRING ? $token[1] : '',
                    token_get_all($source),
                ));
            } else {
                $source = preg_replace('/\{\{--.*?--\}\}/s', '', $source) ?? $source;
                $visibleSource = preg_replace([
                    '/@push\(.*?@endpush/s',
                    '/@php.*?@endphp/s',
                    '/\{\{.*?\}\}/s',
                    '/@[A-Za-z]+[^\n]*/',
                ], '', $source) ?? $source;
                preg_match_all('/>([^<>]+)</s', $visibleSource, $textMatches);
                preg_match_all('/\b(?:title|placeholder|aria-label|wire:confirm)="([^"]+)"/', $visibleSource, $attributeMatches);
                $literals = array_values(array_unique(array_filter(array_map(
                    fn (string $literal): string => preg_replace('/\s+/', ' ', trim($literal)) ?? trim($literal),
                    array_merge($textMatches[1], $attributeMatches[1]),
                ), fn (string $literal): bool => preg_match('/[A-Za-z]{2}/', $literal) === 1)));
                sort($literals);
                $this->assertSame(
                    [],
                    array_values(array_diff($literals, self::TECHNICAL_UI_LITERAL_ALLOWLIST)),
                    "$file has an undocumented hardcoded user-visible literal",
                );
                $technicalFound = array_values(array_unique(array_merge($technicalFound, $literals)));
            }
            foreach ($forbidden as $literal) {
                $this->assertStringNotContainsString($literal, $source, "$file contains hardcoded UI text: $literal");
            }
        }

        sort($technicalFound);
        $this->assertSame(self::TECHNICAL_UI_LITERAL_ALLOWLIST, $technicalFound);
    }

    /** @return array<string, string> */
    private function flatten(array $messages): array
    {
        $flat = [];
        $iterator = new RecursiveIteratorIterator(new RecursiveArrayIterator($messages));
        foreach ($iterator as $value) {
            $keys = [];
            for ($depth = 0; $depth <= $iterator->getDepth(); $depth++) {
                $keys[] = $iterator->getSubIterator($depth)->key();
            }
            $flat[implode('.', $keys)] = (string) $value;
        }

        return $flat;
    }
}
