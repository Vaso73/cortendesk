<?php

namespace App\Console\Commands;

use App\Support\LocaleCoverageChecker;
use Illuminate\Console\Command;

/**
 * Locale coverage / quality gate for the PHP translation catalogs.
 *
 * Compares the canonical PHP catalogs against one target or every registered
 * non-base locale and reports per catalog: total keys, missing keys, structural
 * defects, and exact coverage. Complete locales require full key coverage;
 * community locales may use the canonical fallback but must remain
 * structurally valid. Exits non-zero on every policy violation.
 */
class LocaleCheckCommand extends Command
{
    protected $signature = 'locale:check
        {--base=en : Base (canonical) locale}
        {--target=sk : Target locale to check for coverage}
        {--all : Check every registered non-base locale using its declared community policy}
        {--path= : Override the lang/ base directory (defaults to base_path("lang"))}
        {--allow-partial : Allow missing keys to use the base locale while still failing structural defects}
        {--ignore=* : Catalog:key.path entries to ignore (repeatable), e.g. --ignore=ui:fallback_probe. Use for keys that are intentionally English-only fallback probes.}';

    protected $description = 'Report translation coverage and defects for a target locale against the base locale';

    public function handle(LocaleCoverageChecker $checker): int
    {
        $base = (string) $this->option('base');
        $langPath = $this->option('path') ?: base_path('lang');

        if ($base !== 'en') {
            $this->error("Only the canonical base locale 'en' is supported.");

            return self::FAILURE;
        }

        if ((bool) $this->option('all')) {
            return $this->checkAllRegisteredLocales($base, (string) $langPath);
        }

        $target = (string) $this->option('target');
        $allowPartial = (bool) $this->option('allow-partial');

        $baseDir = "{$langPath}/{$base}";
        $targetDir = "{$langPath}/{$target}";

        if (! is_dir($baseDir)) {
            $this->error("Base locale directory not found: {$baseDir}");

            return self::FAILURE;
        }

        if (! is_dir($targetDir)) {
            $this->error("Target locale directory not found: {$targetDir}");

            return self::FAILURE;
        }

        $catalogs = collect(glob("{$baseDir}/*.php"))
            ->map(fn (string $file) => basename($file, '.php'))
            ->sort()
            ->values();

        if ($catalogs->isEmpty()) {
            $this->error("No catalogs found in {$baseDir}");

            return self::FAILURE;
        }

        // --ignore=ui:fallback_probe --ignore=settings:some.key -> ['ui' => ['fallback_probe'], ...]
        $ignoreByCatalog = [];
        $ignoreEntries = array_values(array_unique(array_merge(
            (array) config('locales.php_intentional_fallbacks', []),
            (array) $this->option('ignore')
        )));
        foreach ($ignoreEntries as $entry) {
            if (! str_contains($entry, ':')) {
                continue;
            }
            [$catalogName, $key] = explode(':', $entry, 2);
            $ignoreByCatalog[$catalogName][] = $key;
        }

        $rows = [];
        $anyDefect = false;
        $overallTotal = 0;
        $overallTranslated = 0;

        foreach ($catalogs as $catalog) {
            $baseFile = "{$baseDir}/{$catalog}.php";
            $targetFile = "{$targetDir}/{$catalog}.php";

            /** @var mixed $baseCatalog */
            $baseCatalog = require $baseFile;
            if (! is_array($baseCatalog)) {
                $this->error("Invalid base catalog: {$baseFile} must return an array");
                $rows[] = [$catalog, '0', '0', '1', 'N/A', 'INVALID BASE'];
                $anyDefect = true;

                continue;
            }

            if (! is_file($targetFile)) {
                $baseFlat = $checker->flatten($baseCatalog);
                foreach ($ignoreByCatalog[$catalog] ?? [] as $ignored) {
                    unset($baseFlat[$ignored]);
                }
                $missingCatalogKeys = count($baseFlat);
                $overallTotal += $missingCatalogKeys;
                $this->error("Missing catalog: {$targetDir}/{$catalog}.php does not exist");
                $rows[] = [
                    $catalog,
                    (string) $missingCatalogKeys,
                    (string) $missingCatalogKeys,
                    '1',
                    '0.00%',
                    'MISSING CATALOG',
                ];
                $anyDefect = true;

                continue;
            }

            /** @var mixed $targetCatalog */
            $targetCatalog = require $targetFile;
            if (! is_array($targetCatalog)) {
                $baseFlat = $checker->flatten($baseCatalog);
                foreach ($ignoreByCatalog[$catalog] ?? [] as $ignored) {
                    unset($baseFlat[$ignored]);
                }
                $totalKeys = count($baseFlat);
                $overallTotal += $totalKeys;
                $this->error("Invalid target catalog: {$targetFile} must return an array");
                $rows[] = [
                    $catalog,
                    (string) $totalKeys,
                    (string) $totalKeys,
                    '1',
                    '0.00%',
                    'INVALID CATALOG',
                ];
                $anyDefect = true;

                continue;
            }

            $result = $checker->compare($baseCatalog, $targetCatalog, $target, $ignoreByCatalog[$catalog] ?? []);

            $missingCount = count($result['missing']);
            $defectCount = count($result['extra'])
                + count($result['empty'])
                + count($result['value_defects'])
                + count($result['base_value_defects'])
                + count($result['html_defects'])
                + count($result['placeholder_mismatches'])
                + count($result['plural_defects']);
            $overallTotal += $result['total_keys'];
            $overallTranslated += $result['translated_keys'];

            $status = $result['complete']
                ? 'OK'
                : ($allowPartial && $result['structurally_valid'] ? 'PARTIAL' : 'FAIL');

            $rows[] = [
                $catalog,
                (string) $result['total_keys'],
                (string) $missingCount,
                $defectCount === 0 ? '0' : (string) $defectCount,
                number_format($result['coverage_percent'], 2).'%',
                $status,
            ];

            $passesPolicy = $result['structurally_valid']
                && ($allowPartial || $result['complete']);
            if (! $passesPolicy) {
                $anyDefect = true;
                $this->reportDefects($catalog, $result, includeMissing: ! $allowPartial);
            }
        }

        $this->newLine();
        $this->table(['Catalog', 'Keys', 'Missing', 'Defects', 'Coverage', 'Status'], $rows);
        $overallCoverage = $overallTotal > 0
            ? round(($overallTranslated / $overallTotal) * 100, 2)
            : 100.0;
        $this->line(sprintf(
            'Overall coverage: %d/%d keys (%s%%)',
            $overallTranslated,
            $overallTotal,
            number_format($overallCoverage, 2)
        ));

        if ($anyDefect) {
            $this->error("Locale check FAILED for target locale '{$target}'.");

            return self::FAILURE;
        }

        if ($allowPartial && $overallTranslated < $overallTotal) {
            $this->info("Locale check PASSED for partial target locale '{$target}': missing keys use '{$base}' fallback.");
        } else {
            $this->info("Locale check PASSED for target locale '{$target}': all catalogs fully covered.");
        }

        return self::SUCCESS;
    }

    private function checkAllRegisteredLocales(string $base, string $langPath): int
    {
        /** @var array<string, array<string, mixed>> $supported */
        $supported = config('locales.supported', []);

        if (! array_key_exists($base, $supported)) {
            $this->error("Base locale '{$base}' is not registered in config/locales.php.");

            return self::FAILURE;
        }

        $failed = [];
        foreach ($supported as $locale => $metadata) {
            if ($locale === $base) {
                continue;
            }

            $this->newLine();
            $this->info("Checking registered locale '{$locale}'...");

            $arguments = [
                '--base' => $base,
                '--target' => $locale,
                '--path' => $langPath,
                '--ignore' => array_values(array_unique(array_merge(
                    (array) config('locales.php_intentional_fallbacks', []),
                    (array) $this->option('ignore')
                ))),
            ];
            if ((bool) ($metadata['community'] ?? false)) {
                $arguments['--allow-partial'] = true;
            }

            if ($this->call('locale:check', $arguments) !== self::SUCCESS) {
                $failed[] = $locale;
            }
        }

        if ($failed !== []) {
            $this->error('Registered locale check FAILED for: '.implode(', ', $failed));

            return self::FAILURE;
        }

        $this->info('All registered locale catalogs PASSED.');

        return self::SUCCESS;
    }

    /**
     * @param  array{missing: string[], extra: string[], empty: string[], value_defects: array<string, string>, base_value_defects: array<string, string>, html_defects: array<string, string>, placeholder_mismatches: array<string, array{expected: string[], actual: string[]}>, plural_defects: array<string, string>}  $result
     */
    private function reportDefects(string $catalog, array $result, bool $includeMissing = true): void
    {
        $this->newLine();
        $this->warn("Defects in [{$catalog}]:");

        if ($includeMissing) {
            foreach ($result['missing'] as $key) {
                $this->line("  MISSING     {$key}");
            }
        }

        foreach ($result['extra'] as $key) {
            $this->line("  EXTRA       {$key}");
        }

        foreach ($result['empty'] as $key) {
            $this->line("  EMPTY       {$key}");
        }

        foreach ($result['base_value_defects'] as $key => $reason) {
            $this->line("  BASE VALUE  {$key} — {$reason}");
        }

        foreach ($result['value_defects'] as $key => $reason) {
            $this->line("  VALUE       {$key} — {$reason}");
        }

        foreach ($result['html_defects'] as $key => $reason) {
            $this->line("  HTML        {$key} — {$reason}");
        }

        foreach ($result['placeholder_mismatches'] as $key => $diff) {
            $expected = implode(', ', $diff['expected']) ?: '(none)';
            $actual = implode(', ', $diff['actual']) ?: '(none)';
            $this->line("  PLACEHOLDER {$key} — expected [{$expected}], got [{$actual}]");
        }

        foreach ($result['plural_defects'] as $key => $reason) {
            $this->line("  PLURAL      {$key} — {$reason}");
        }
    }
}
