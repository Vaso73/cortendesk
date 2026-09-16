<?php

namespace Tests\Feature;

use App\Livewire\AlarmLogList;
use App\Livewire\ConnectionLog;
use App\Livewire\ConsoleAuditList;
use App\Livewire\FileTransferLog;
use App\Livewire\LoginLogList;
use App\Models\AlarmLog;
use App\Models\AuditConnection;
use App\Models\AuditFileTransfer;
use App\Models\ConsoleAudit;
use App\Models\LoginLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Arr;
use Tests\TestCase;

class AuditLocaleTest extends TestCase
{
    use RefreshDatabase;

    public function test_english_and_slovak_audit_catalogs_have_key_and_placeholder_parity(): void
    {
        $english = Arr::dot(require lang_path('en/audit.php'));
        $slovak = Arr::dot(require lang_path('sk/audit.php'));

        $this->assertSame(array_keys($english), array_keys($slovak));
        $this->assertNotEmpty($english);

        foreach ($english as $key => $value) {
            preg_match_all('/(?<!:):[A-Za-z_][A-Za-z0-9_]*/', (string) $value, $englishPlaceholders);
            preg_match_all('/(?<!:):[A-Za-z_][A-Za-z0-9_]*/', (string) $slovak[$key], $slovakPlaceholders);
            $englishPlaceholders[0] = array_values(array_unique($englishPlaceholders[0]));
            $slovakPlaceholders[0] = array_values(array_unique($slovakPlaceholders[0]));
            sort($englishPlaceholders[0]);
            sort($slovakPlaceholders[0]);
            $this->assertSame($englishPlaceholders[0], $slovakPlaceholders[0], "Placeholder mismatch at {$key}");
        }
    }

    public function test_audit_domain_has_complete_natural_slovak_contract(): void
    {
        app()->setLocale('sk');

        $this->assertSame('Záznam alarmov', __('audit.pages.alarms.title'));
        $this->assertSame('Záznam pripojení', __('audit.pages.connections.title'));
        $this->assertSame('Prenosy súborov', __('audit.pages.file_transfers.title'));
        $this->assertSame('História prihlásení', __('audit.pages.logins.title'));
        $this->assertSame('Audit konzoly', __('audit.pages.console.title'));
        $this->assertSame('Zobrazené 1–20 z 42', __('audit.pagination.summary', ['from' => 1, 'to' => 20, 'total' => 42]));
        $this->assertSame('5 súborov', trans_choice('audit.file_count', 5, ['count' => 5]));
    }

    public function test_known_enum_labels_follow_locale_and_unknown_values_keep_their_identifier(): void
    {
        app()->setLocale('sk');

        $alarm = new AlarmLog(['typ' => 100]);
        $this->assertSame('Útok hrubou silou na konzolu', $alarm->typeLabel());
        $this->assertSame('Vzdialené ovládanie', AuditConnection::typeLabel(0));
        $this->assertSame('Typ 73', AuditConnection::typeLabel(73));
        $this->assertSame('Vytvorenie používateľa', ConsoleAuditList::actionLabel('user.create'));
        $this->assertSame('custom.raw-action', ConsoleAuditList::actionLabel('custom.raw-action'));
        $this->assertSame('Mobil', LoginLog::clientLabel('mobile'));
        $this->assertSame('custom-client', LoginLog::clientLabel('custom-client'));
        $this->assertSame('Prijaté', AuditFileTransfer::directionLabel(1));
        $this->assertSame('Odoslané', AuditFileTransfer::directionLabel(0));

        app()->setLocale('en');
        $this->assertSame('Console brute force', $alarm->typeLabel());
        $this->assertSame('Remote control', AuditConnection::typeLabel(0));
    }

    public function test_scoped_views_do_not_retain_user_visible_english_literals(): void
    {
        $paths = [
            ...glob(resource_path('views/logs/*.blade.php')),
            resource_path('views/livewire/alarm-log-list.blade.php'),
            resource_path('views/livewire/connection-log.blade.php'),
            resource_path('views/livewire/console-audit-list.blade.php'),
            resource_path('views/livewire/file-transfer-log.blade.php'),
            resource_path('views/livewire/login-log-list.blade.php'),
        ];
        $forbidden = [
            'Search device', 'Search operator', 'Search username', 'From date', 'To date',
            'Rows per page', '>Reset<', 'Export CSV', '>When<', '>Time<', '>Device<',
            '>Type<', '>Details<', '>From<', '>Direction<', '>Files<', '>Result<',
            '>Active<', '>Success<', '>Failed<', '>Receive<', '>Send<',
            'No alarms match', 'No connections match', 'No audit entries match',
            'No file transfers match', 'No logins match', '>Clear filters<', '>Showing ',
        ];

        foreach ($paths as $path) {
            $source = file_get_contents($path);
            $this->assertStringContainsString("__('audit.", $source, $path);
            foreach ($forbidden as $literal) {
                $this->assertStringNotContainsString($literal, $source, "{$literal} remains in {$path}");
            }
        }
    }

    public function test_csv_exports_preserve_the_canonical_schema_and_values_across_locales(): void
    {
        $this->actingAs(User::factory()->admin()->create());
        app()->setLocale('sk');

        AlarmLog::create(['rustdesk_id' => 'host-alarm.example', 'typ' => 100, 'info' => '{"error":"raw alarm"}', 'conn_id' => 901]);
        AuditConnection::create(['rustdesk_id' => 'host-connection.example', 'from_peer' => 'peer-raw', 'from_name' => 'Operator Raw', 'ip' => '192.0.2.10', 'conn_type' => 0]);
        AuditFileTransfer::create(['rustdesk_id' => 'host-file.example', 'from_peer' => 'peer-file', 'path' => '/Raw/Path.bin', 'direction' => 1, 'file_count' => 1, 'ip' => '192.0.2.11']);
        ConsoleAudit::create(['username' => 'admin-raw', 'action' => 'user.create', 'target_type' => 'user', 'target_id' => 'target-raw', 'summary' => 'Evidence stays raw', 'ip' => '192.0.2.12']);
        LoginLog::create(['username' => 'login-raw', 'client' => 'mobile', 'device_id' => 'device-raw', 'device_os' => 'OS Raw', 'ip' => '192.0.2.13', 'successful' => false, 'note' => 'Failure payload raw']);

        $exports = [
            AlarmLogList::class => [['When', 'Device', 'Type', 'Info', 'Conn ID'], ['host-alarm.example', 'Console brute force', '{"error":"raw alarm"}', '901']],
            ConnectionLog::class => [['When', 'Controlled Device', 'From ID', 'From Name', 'Type', 'IP', 'Closed At', 'Duration (s)'], ['host-connection.example', 'peer-raw', 'Operator Raw', 'Remote Control', '192.0.2.10', 'active', '']],
            FileTransferLog::class => [['When', 'Device', 'From ID', 'From Name', 'Direction', 'Path', 'Files', 'IP'], ['host-file.example', 'peer-file', '', 'Receive', '/Raw/Path.bin', '1', '192.0.2.11']],
            ConsoleAuditList::class => [['When', 'Operator', 'Action', 'Target Type', 'Target', 'Details', 'IP'], ['admin-raw', 'user.create', 'user', 'target-raw', 'Evidence stays raw', '192.0.2.12']],
            LoginLogList::class => [['When', 'Username', 'Client', 'Device ID', 'Device OS', 'IP', 'Result', 'Note'], ['login-raw', 'mobile', 'device-raw', 'OS Raw', '192.0.2.13', 'Failed', 'Failure payload raw']],
        ];

        foreach ($exports as $componentClass => [$expectedHeader, $expectedRowWithoutTimestamp]) {
            $rowsByLocale = [];
            foreach (['en', 'sk'] as $locale) {
                app()->setLocale($locale);
                $component = app($componentClass);
                $component->mount();
                ob_start();
                $component->export()->sendContent();
                $lines = preg_split('/\R/', trim((string) ob_get_clean()));
                $header = str_getcsv($lines[0], escape: '');
                $row = str_getcsv($lines[1], escape: '');

                $this->assertSame($expectedHeader, $header, "{$componentClass} ({$locale})");
                $this->assertSame($expectedRowWithoutTimestamp, array_slice($row, 1), "{$componentClass} ({$locale})");
                $rowsByLocale[$locale] = $row;
            }
            $this->assertSame($rowsByLocale['en'], $rowsByLocale['sk'], "{$componentClass} changed across locales");
        }
    }

    public function test_raw_audit_evidence_is_rendered_without_translation_or_mutation(): void
    {
        $views = [
            'alarm-log-list.blade.php' => ['$alarm->rustdesk_id', '$alarm->info', '$alarm->conn_id'],
            'connection-log.blade.php' => ['$conn->rustdesk_id', '$conn->from_peer', '$conn->from_name', '$conn->ip'],
            'console-audit-list.blade.php' => ['$audit->username', '$audit->target_type', '$audit->target_id', '$audit->summary', '$audit->ip'],
            'file-transfer-log.blade.php' => ['$transfer->rustdesk_id', '$transfer->from_peer', '$transfer->from_name', '$transfer->path', '$transfer->ip'],
            'login-log-list.blade.php' => ['$log->username', '$log->device_id', '$log->device_os', '$log->ip', '$log->note'],
        ];

        foreach ($views as $file => $evidence) {
            $source = file_get_contents(resource_path('views/livewire/'.$file));
            foreach ($evidence as $expression) {
                $this->assertStringContainsString('{{ '.$expression, $source, "Missing raw evidence {$expression}");
                $this->assertStringNotContainsString("__({$expression}", $source, "Translated raw evidence {$expression}");
            }
        }
    }

    public function test_persisted_console_audit_summaries_force_the_canonical_english_locale(): void
    {
        $paths = [
            app_path('Http/Controllers/InvitationController.php'),
            app_path('Livewire/AddressBookManager.php'),
            app_path('Livewire/ApiTokenManager.php'),
            app_path('Livewire/InvitationManager.php'),
            app_path('Livewire/RoleList.php'),
            app_path('Livewire/UserList.php'),
        ];

        foreach ($paths as $path) {
            $source = file_get_contents($path);
            preg_match_all("/(?:__|trans_choice)\\(\s*['\"](?:address_books|identity)\\.[^'\"]*audit[^'\"]*['\"]/", $source, $matches);
            $this->assertNotEmpty($matches[0], $path);
            foreach ($matches[0] as $match) {
                $offset = strpos($source, $match);
                $call = substr($source, $offset, 700);
                $this->assertMatchesRegularExpression("/,\s*['\"]en['\"]\s*\)/", $call, "Audit translation is not canonical in {$path}: {$match}");
            }
        }
    }
}
