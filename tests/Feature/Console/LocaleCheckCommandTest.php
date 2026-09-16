<?php

namespace Tests\Feature\Console;

use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

/**
 * End-to-end coverage of the `locale:check` artisan command: it must fail
 * (non-zero exit) against deliberately broken fixture catalogs and pass
 * (zero exit) against the real, current en/sk catalogs shipped in lang/.
 */
class LocaleCheckCommandTest extends TestCase
{
    public function test_it_fails_against_deliberately_broken_fixture_catalogs(): void
    {
        $fixturePath = base_path('tests/Fixtures/locale-check-broken');

        $exit = Artisan::call('locale:check', [
            '--base' => 'en',
            '--target' => 'sk',
            '--path' => $fixturePath,
        ]);

        $this->assertNotSame(0, $exit);

        $output = Artisan::output();
        $this->assertStringContainsString('MISSING', $output);
        $this->assertStringContainsString('EXTRA', $output);
        $this->assertStringContainsString('EMPTY', $output);
        $this->assertStringContainsString('PLACEHOLDER', $output);
        $this->assertStringContainsString('PLURAL', $output);
        $this->assertStringContainsString('FAIL', $output);
    }

    public function test_it_accepts_a_structurally_valid_partial_catalog_and_reports_exact_coverage(): void
    {
        $fixturePath = base_path('tests/Fixtures/locale-check-partial');

        $exit = Artisan::call('locale:check', [
            '--base' => 'en',
            '--target' => 'de',
            '--path' => $fixturePath,
            '--allow-partial' => true,
        ]);

        $output = Artisan::output();

        $this->assertSame(0, $exit, "partial locale check failed:
{$output}");
        $this->assertStringContainsString('PARTIAL', $output);
        $this->assertStringContainsString('50.00%', $output);
        $this->assertStringContainsString('Overall coverage: 1/2 keys (50.00%)', $output);
    }

    public function test_it_reports_non_string_values_as_blocking_defects(): void
    {
        $fixturePath = base_path('tests/Fixtures/locale-check-malformed');

        $exit = Artisan::call('locale:check', [
            '--base' => 'en',
            '--target' => 'de',
            '--path' => $fixturePath,
            '--allow-partial' => true,
        ]);

        $output = Artisan::output();
        $this->assertNotSame(0, $exit);
        $this->assertStringContainsString('VALUE', $output);
        $this->assertStringContainsString('Overall coverage: 0/1 keys (0.00%)', $output);
    }

    public function test_scalar_target_catalog_is_a_blocking_defect_with_exact_coverage(): void
    {
        $exit = Artisan::call('locale:check', [
            '--base' => 'en',
            '--target' => 'de',
            '--path' => base_path('tests/Fixtures/locale-check-scalar-target'),
            '--allow-partial' => true,
        ]);

        $output = Artisan::output();
        $this->assertNotSame(0, $exit);
        $this->assertStringContainsString('INVALID CATALOG', $output);
        $this->assertStringContainsString('Overall coverage: 0/1 keys (0.00%)', $output);
    }

    public function test_missing_catalog_is_included_in_overall_coverage_denominator(): void
    {
        $fixturePath = base_path('tests/Fixtures/locale-check-missing-catalog');

        $exit = Artisan::call('locale:check', [
            '--base' => 'en',
            '--target' => 'de',
            '--path' => $fixturePath,
            '--allow-partial' => true,
        ]);

        $output = Artisan::output();
        $this->assertNotSame(0, $exit);
        $this->assertStringContainsString('MISSING CATALOG', $output);
        $this->assertStringContainsString('Overall coverage: 1/3 keys (33.33%)', $output);
    }

    public function test_non_english_canonical_base_is_rejected_fail_closed(): void
    {
        $exit = Artisan::call('locale:check', [
            '--base' => 'sk',
            '--target' => 'en',
        ]);

        $this->assertNotSame(0, $exit);
        $this->assertStringContainsString("canonical base locale 'en'", Artisan::output());
    }

    public function test_it_passes_against_the_real_current_catalogs(): void
    {
        $exit = Artisan::call('locale:check', [
            '--base' => 'en',
            '--target' => 'sk',
        ]);

        $output = Artisan::output();

        $this->assertSame(0, $exit, "locale:check failed against real catalogs:\n{$output}");
        $this->assertStringContainsString('PASSED', $output);
        $this->assertStringContainsString('100.00%', $output);
    }

    public function test_it_checks_every_registered_locale_with_its_declared_completion_policy(): void
    {
        $exit = Artisan::call('locale:check', [
            '--all' => true,
        ]);

        $output = Artisan::output();

        $this->assertSame(0, $exit, "locale:check --all failed:\n{$output}");
        foreach (array_keys(config('locales.supported')) as $locale) {
            if ($locale === 'en') {
                continue;
            }
            $this->assertStringContainsString("Checking registered locale '{$locale}'", $output);
        }
        $this->assertStringContainsString('All registered locale catalogs PASSED.', $output);
    }
}
