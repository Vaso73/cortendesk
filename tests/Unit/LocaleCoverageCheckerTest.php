<?php

namespace Tests\Unit;

use App\Support\LocaleCoverageChecker;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class LocaleCoverageCheckerTest extends TestCase
{
    private function checker(): LocaleCoverageChecker
    {
        return new LocaleCoverageChecker;
    }

    // -- RED-style cases: deliberately broken fixtures must be reported. --

    public function test_it_detects_a_missing_key(): void
    {
        $base = ['greeting' => 'Hello', 'farewell' => 'Bye'];
        $target = ['greeting' => 'Ahoj'];

        $result = $this->checker()->compare($base, $target, 'sk');

        $this->assertFalse($result['ok']);
        $this->assertSame(['farewell'], $result['missing']);
        $this->assertSame([], $result['extra']);
    }

    public function test_partial_catalog_separates_missing_coverage_from_structural_defects(): void
    {
        $base = ['greeting' => 'Hello', 'farewell' => 'Bye'];
        $target = ['greeting' => 'Hola'];

        $result = $this->checker()->compare($base, $target, 'es');

        $this->assertTrue($result['structurally_valid']);
        $this->assertFalse($result['complete']);
        $this->assertFalse($result['ok']);
        $this->assertSame(['farewell'], $result['missing']);
        $this->assertSame(50.0, $result['coverage_percent']);
    }

    public function test_it_rejects_non_string_messages_without_counting_them_as_coverage(): void
    {
        foreach (['null' => null, 'number' => 42, 'empty_array' => [], 'list' => ['text']] as $case => $value) {
            $result = $this->checker()->compare(['message' => 'Hello'], ['message' => $value], 'de');

            $this->assertFalse($result['structurally_valid'], $case);
            $this->assertFalse($result['complete'], $case);
            $this->assertArrayHasKey('message', $result['value_defects'], $case);
            $this->assertSame(0.0, $result['coverage_percent'], $case);
        }
    }

    public function test_it_rejects_non_string_canonical_leaf_without_counting_coverage(): void
    {
        foreach (['number' => 42, 'list' => ['text']] as $case => $value) {
            $result = $this->checker()->compare(['message' => $value], ['message' => 'Preklad'], 'sk');

            $this->assertFalse($result['structurally_valid'], $case);
            $this->assertArrayHasKey('message', $result['base_value_defects'], $case);
            $this->assertSame(0.0, $result['coverage_percent'], $case);
        }
    }

    public function test_invalid_canonical_key_missing_from_target_is_counted_once(): void
    {
        $result = $this->checker()->compare(['bad' => 42], [], 'de');

        $this->assertSame(1, $result['total_keys']);
        $this->assertSame(0, $result['translated_keys']);
        $this->assertSame(0.0, $result['coverage_percent']);
        $this->assertFalse($result['structurally_valid']);
    }

    public function test_it_rejects_tag_like_html_declarations_in_canonical_and_target_values(): void
    {
        foreach (['<!-- comment -->', '<!DOCTYPE html>', '<?xml version="1.0"?>'] as $fragment) {
            $canonical = $this->checker()->compare(['message' => $fragment], ['message' => 'Safe'], 'de');
            $target = $this->checker()->compare(['message' => 'Safe'], ['message' => $fragment], 'de');

            $this->assertArrayHasKey('message', $canonical['base_value_defects'], $fragment);
            $this->assertArrayHasKey('message', $target['html_defects'], $fragment);
            $this->assertFalse($canonical['structurally_valid'], $fragment);
            $this->assertFalse($target['structurally_valid'], $fragment);
        }
    }

    public function test_it_rejects_html_in_a_translation_value(): void
    {
        $result = $this->checker()->compare(
            ['message' => 'Safe text'],
            ['message' => '<img src=x onerror=alert(1'],
            'de'
        );

        $this->assertFalse($result['structurally_valid']);
        $this->assertArrayHasKey('message', $result['html_defects']);
        $this->assertSame(0.0, $result['coverage_percent']);
    }

    public function test_ignored_canonical_fallback_key_must_remain_absent_from_target(): void
    {
        $result = $this->checker()->compare(
            ['fallback_probe' => 'English only'],
            ['fallback_probe' => 'Must not be translated'],
            'sk',
            ['fallback_probe']
        );

        $this->assertFalse($result['structurally_valid']);
        $this->assertSame(['fallback_probe'], $result['extra']);
    }

    public function test_it_detects_an_extra_key(): void
    {
        $base = ['greeting' => 'Hello'];
        $target = ['greeting' => 'Ahoj', 'unexpected' => 'Navyše'];

        $result = $this->checker()->compare($base, $target, 'sk');

        $this->assertFalse($result['ok']);
        $this->assertSame(['unexpected'], $result['extra']);
        $this->assertSame([], $result['missing']);
    }

    public function test_it_detects_an_empty_value(): void
    {
        $base = ['greeting' => 'Hello'];
        $target = ['greeting' => ''];

        $result = $this->checker()->compare($base, $target, 'sk');

        $this->assertFalse($result['ok']);
        $this->assertSame(['greeting'], $result['empty']);
    }

    public function test_it_detects_a_placeholder_mismatch(): void
    {
        $base = ['welcome' => 'Hi :name, you have :count items'];
        $target = ['welcome' => 'Ahoj :meno, mate :count poloziek'];

        $result = $this->checker()->compare($base, $target, 'sk');

        $this->assertFalse($result['ok']);
        $this->assertArrayHasKey('welcome', $result['placeholder_mismatches']);
        $this->assertSame(['count', 'name'], $result['placeholder_mismatches']['welcome']['expected']);
        $this->assertSame(['count', 'meno'], $result['placeholder_mismatches']['welcome']['actual']);
    }

    public function test_it_detects_a_bad_slovak_plural_shape(): void
    {
        // English bare plural has 2 branches; Slovak needs the 3-branch
        // {1}/[2,4]/[5,*] shape (or a bare 3-way split) — this fixture
        // wrongly copies the English 2-branch bare shape into Slovak.
        $base = ['items' => 'One item|:count items'];
        $target = ['items' => 'Jedna polozka|:count poloziek'];

        $result = $this->checker()->compare($base, $target, 'sk');

        $this->assertFalse($result['ok']);
        $this->assertArrayHasKey('items', $result['plural_defects']);
    }

    public function test_it_uses_laravel_runtime_rules_for_bare_plural_forms(): void
    {
        foreach (['de' => 2, 'sk' => 3, 'ru' => 3, 'pl' => 3] as $locale => $formCount) {
            $target = ['items' => implode('|', array_fill(0, $formCount, ':count item'))];
            $result = $this->checker()->compare(['items' => ':count item|:count items'], $target, $locale);

            $this->assertTrue($result['structurally_valid'], $locale);
            $this->assertTrue($result['complete'], $locale);
        }
    }

    public function test_it_rejects_tagged_plural_with_too_few_runtime_fallback_branches(): void
    {
        $base = ['items' => '{1} :count item|[2,*] :count items'];
        $target = ['items' => '{1} :count položka|:count položiek'];

        $result = $this->checker()->compare($base, $target, 'sk');

        $this->assertFalse($result['structurally_valid']);
        $this->assertArrayHasKey('items', $result['plural_defects']);
    }

    public function test_it_rejects_empty_or_malformed_tagged_plural_branches(): void
    {
        foreach ([
            'empty' => '{1} :count položka|[2,4]   |[5,*] :count položiek',
            'malformed_tag' => '{1} :count položka|[2,4 :count položky|[5,*] :count položiek',
        ] as $case => $targetValue) {
            $result = $this->checker()->compare(
                ['items' => '{1} :count item|[2,*] :count items'],
                ['items' => $targetValue],
                'sk'
            );

            $this->assertFalse($result['structurally_valid'], $case);
            $this->assertArrayHasKey('items', $result['plural_defects'], $case);
        }
    }

    public function test_it_detects_a_placeholder_missing_from_one_plural_branch(): void
    {
        $base = ['items' => '{0} No items|{1} One item for :name|[2,*] :count items for :name'];
        $target = ['items' => '{0} Žiadne|{1} Jedna pre :name|[2,4] položky|[5,*] :count položiek pre :name'];

        $result = $this->checker()->compare($base, $target, 'sk');

        $this->assertFalse($result['structurally_valid']);
        $this->assertArrayHasKey('items', $result['placeholder_mismatches']);
        $this->assertSame(['name'], $result['placeholder_mismatches']['items']['expected']);
        $this->assertSame([], $result['placeholder_mismatches']['items']['actual']);
    }

    public function test_it_rejects_a_placeholder_added_from_another_plural_branch(): void
    {
        $base = ['items' => '{1} :count item for :name|[2,4] :count items|[5,*] :count items'];
        $target = ['items' => '{1} :count položka pre :name|[2,4] :count položky|[5,*] :count položiek pre :name'];

        $result = $this->checker()->compare($base, $target, 'sk');

        $this->assertFalse($result['structurally_valid']);
        $this->assertSame(
            ['expected' => [], 'actual' => ['name']],
            $result['placeholder_mismatches']['items']
        );
    }

    public function test_it_checks_placeholders_in_explicit_ranges_outside_the_standard_sample(): void
    {
        $base = ['items' => '{1} One item|[2,*] :count items for :name'];
        $target = ['items' => '{1} Jedna|[2,299] :count položiek pre :name|[300,400] položiek|[401,*] :count položiek pre :name'];

        $result = $this->checker()->compare($base, $target, 'sk');

        $this->assertFalse($result['structurally_valid']);
        $this->assertArrayHasKey('items', $result['placeholder_mismatches']);
    }

    public function test_it_rejects_decimal_tagged_plural_selectors(): void
    {
        $result = $this->checker()->compare(
            ['items' => '{1} one|[2,*] :count many'],
            ['items' => '{1} jedna|[1.5,2.5] :count niekoľko|[0,*] :count veľa'],
            'sk'
        );

        $this->assertFalse($result['structurally_valid']);
        $this->assertArrayHasKey('items', $result['plural_defects']);
    }

    public function test_it_checks_a_trailing_fallback_just_beyond_a_large_explicit_range(): void
    {
        $result = $this->checker()->compare(
            ['items' => '{1} One|[2,*] :count items for :name'],
            ['items' => '{1} Jedna|[2,1000000] :count položiek pre :name|[0,*] položiek'],
            'sk'
        );

        $this->assertFalse($result['structurally_valid']);
        $this->assertArrayHasKey('items', $result['placeholder_mismatches']);
    }

    public function test_it_rejects_tagged_selectors_outside_php_integer_range(): void
    {
        $outside = (string) PHP_INT_MAX.'0';
        $result = $this->checker()->compare(
            ['items' => '{1} one|[2,*] many'],
            ['items' => "{1} jedna|{{$outside}} nemožné|[2,*] veľa"],
            'sk'
        );

        $this->assertFalse($result['structurally_valid']);
        $this->assertArrayHasKey('items', $result['plural_defects']);
    }

    public function test_it_rejects_an_unreachable_trailing_fallback_branch(): void
    {
        $result = $this->checker()->compare(
            ['items' => '{1} one|[2,*] many'],
            ['items' => '{0} nula|{1} jedna|[2,*] veľa|[0,*] nedosiahnuteľné'],
            'sk'
        );

        $this->assertFalse($result['structurally_valid']);
        $this->assertArrayHasKey('items', $result['plural_defects']);
    }

    public function test_it_rejects_invalid_or_overlapping_tagged_plural_selectors(): void
    {
        foreach ([
            'double_wildcard' => '{1} jedna|[*,*] viac|[2,*] veľa',
            'reversed_range' => '{1} jedna|[5,2] viac|[6,*] veľa',
            'equivalent_selector' => '{1} jedna|[1,1] tiež jedna|[2,*] veľa',
            'overlapping_ranges' => '{1} jedna|[2,5] viac|[5,*] veľa',
        ] as $case => $targetValue) {
            $result = $this->checker()->compare(
                ['items' => '{1} one|[2,*] many'],
                ['items' => $targetValue],
                'sk'
            );

            $this->assertFalse($result['structurally_valid'], $case);
            $this->assertArrayHasKey('items', $result['plural_defects'], $case);
        }
    }

    public function test_it_detects_plural_present_on_only_one_side(): void
    {
        $base = ['items' => 'One item|:count items'];
        $target = ['items' => ':count poloziek'];

        $result = $this->checker()->compare($base, $target, 'sk');

        $this->assertFalse($result['ok']);
        $this->assertArrayHasKey('items', $result['plural_defects']);
    }

    public function test_it_accepts_a_well_formed_tagged_slovak_plural(): void
    {
        $base = ['items' => '{0} No items|{1} One item|[2,*] :count items'];
        $target = ['items' => '{0} Žiadne položky|{1} Jedna položka|[2,4] :count položky|[5,*] :count položiek'];

        $result = $this->checker()->compare($base, $target, 'sk');

        $this->assertTrue($result['ok']);
        $this->assertSame([], $result['plural_defects']);
    }

    public function test_it_flattens_nested_catalogs_and_computes_coverage(): void
    {
        $base = [
            'common' => ['ok' => 'OK', 'cancel' => 'Cancel'],
        ];
        $target = [
            'common' => ['ok' => 'OK-sk'],
        ];

        $result = $this->checker()->compare($base, $target, 'sk');

        $this->assertSame(2, $result['total_keys']);
        $this->assertSame(['common.cancel'], $result['missing']);
        $this->assertSame(50.0, $result['coverage_percent']);
    }

    public function test_fully_covered_catalog_is_100_percent_and_ok(): void
    {
        $base = ['a' => 'A', 'b' => 'B :count'];
        $target = ['a' => 'Á', 'b' => 'B :count'];

        $result = $this->checker()->compare($base, $target, 'sk');

        $this->assertTrue($result['ok']);
        $this->assertSame(100.0, $result['coverage_percent']);
    }

    // -- GREEN cases: the real, current catalogs must pass cleanly. --

    public static function realCatalogProvider(): array
    {
        return [
            ['auth'],
            ['devices'],
            ['identity'],
            ['address_books'],
            ['audit'],
            ['settings'],
            ['notifications'],
            ['ui'],
        ];
    }

    #[DataProvider('realCatalogProvider')]
    public function test_real_catalog_is_fully_covered_with_no_defects(string $catalog): void
    {
        $enFile = base_path("lang/en/{$catalog}.php");
        $skFile = base_path("lang/sk/{$catalog}.php");

        $this->assertFileExists($enFile);
        $this->assertFileExists($skFile);

        $en = require $enFile;
        $sk = require $skFile;

        // ui.fallback_probe is an intentional English-only fallback probe
        // key (see tests/Feature/LocaleFoundationTest.php) — not a
        // translation gap, so it is excluded from coverage here.
        $ignore = $catalog === 'ui' ? ['fallback_probe'] : [];

        $result = $this->checker()->compare($en, $sk, 'sk', $ignore);

        $this->assertSame([], $result['missing'], "Missing keys in sk/{$catalog}.php");
        $this->assertSame([], $result['extra'], "Extra keys in sk/{$catalog}.php");
        $this->assertSame([], $result['empty'], "Empty values in sk/{$catalog}.php");
        $this->assertSame([], $result['placeholder_mismatches'], "Placeholder mismatches in sk/{$catalog}.php");
        $this->assertSame([], $result['plural_defects'], "Plural shape defects in sk/{$catalog}.php");
        $this->assertSame(100.0, $result['coverage_percent']);
        $this->assertTrue($result['ok']);
    }
}
