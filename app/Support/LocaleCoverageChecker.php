<?php

namespace App\Support;

use Illuminate\Translation\MessageSelector;

/**
 * Compares a base ("en") translation catalog against one or more target
 * locale catalogs and reports coverage + quality defects: missing keys,
 * extra keys, empty values, `:placeholder` mismatches, and malformed
 * trans_choice-style plural branches.
 *
 * Works on plain PHP arrays (as returned by `lang/<locale>/<file>.php`) and
 * derives positional plural counts from Laravel's runtime MessageSelector.
 */
class LocaleCoverageChecker
{

    /**
     * @param  array<string, mixed>  $base  Flattened or nested base (English) catalog.
     * @param  array<string, mixed>  $target  Flattened or nested target-locale catalog.
     * @return array{
     *     total_keys: int,
     *     translated_keys: int,
     *     missing: string[],
     *     extra: string[],
     *     empty: string[],
     *     value_defects: array<string, string>,
     *     base_value_defects: array<string, string>,
     *     html_defects: array<string, string>,
     *     placeholder_mismatches: array<string, array{expected: string[], actual: string[]}>,
     *     plural_defects: array<string, string>,
     *     coverage_percent: float,
     *     structurally_valid: bool,
     *     complete: bool,
     *     ok: bool,
     * }
     */
    public function compare(array $base, array $target, string $targetLocale = 'sk', array $ignoreKeys = []): array
    {
        $baseFlat = $this->flatten($base);
        $targetFlat = $this->flatten($target);

        foreach ($ignoreKeys as $ignored) {
            unset($baseFlat[$ignored]);
        }

        $baseKeys = array_keys($baseFlat);
        $targetKeys = array_keys($targetFlat);

        $missing = array_values(array_diff($baseKeys, $targetKeys));
        $extra = array_values(array_diff($targetKeys, $baseKeys));

        $empty = [];
        $valueDefects = [];
        $baseValueDefects = [];
        $htmlDefects = [];
        foreach ($baseFlat as $key => $value) {
            if (! is_string($value)) {
                $baseValueDefects[$key] = 'canonical value must be a string';
            } elseif (trim($value) === '') {
                $baseValueDefects[$key] = 'canonical value must not be empty';
            } elseif ($this->containsTagLikeMarkup($value)) {
                $baseValueDefects[$key] = 'canonical value must not contain HTML or tag-like markup';
            } elseif ($this->isPlural($value) && ($defect = $this->pluralShapeDefect($value, 'en'))) {
                $baseValueDefects[$key] = $defect;
            }
        }
        foreach ($targetFlat as $key => $value) {
            if (! is_string($value)) {
                $valueDefects[$key] = 'translation value must be a string';
            } elseif (trim($value) === '') {
                $empty[] = $key;
            } elseif ($this->containsTagLikeMarkup($value)) {
                $htmlDefects[$key] = 'translation values must not contain HTML or tag-like markup';
            }
        }

        $placeholderMismatches = [];
        $pluralDefects = [];

        foreach ($baseFlat as $key => $baseValue) {
            if (! array_key_exists($key, $targetFlat) || ! is_string($baseValue)) {
                continue;
            }

            $targetValue = $targetFlat[$key];

            if (! is_string($targetValue)) {
                continue;
            }

            $baseIsPlural = $this->isPlural($baseValue);
            $targetIsPlural = $this->isPlural($targetValue);

            if ($baseIsPlural !== $targetIsPlural) {
                $pluralDefects[$key] = $baseIsPlural
                    ? 'base is a plural (trans_choice) string but the target value is not'
                    : 'target is a plural (trans_choice) string but the base value is not';

                continue;
            }

            if ($baseIsPlural && $targetIsPlural) {
                if ($defect = $this->pluralShapeDefect($targetValue, $targetLocale)) {
                    $pluralDefects[$key] = $defect;
                }

                if ($mismatch = $this->pluralPlaceholderMismatch($baseValue, $targetValue, $targetLocale)) {
                    $placeholderMismatches[$key] = $mismatch;
                }

                continue;
            }

            $expected = $this->placeholdersIn($baseValue);
            $actual = $this->placeholdersIn($targetValue);

            if ($expected !== $actual) {
                $placeholderMismatches[$key] = [
                    'expected' => $expected,
                    'actual' => $actual,
                ];
            }
        }

        $total = count($baseKeys);
        $invalidKeys = array_unique(array_merge(
            $empty,
            array_keys($valueDefects),
            array_keys($baseValueDefects),
            array_keys($htmlDefects),
            array_keys($placeholderMismatches),
            array_keys($pluralDefects)
        ));
        $invalidBaseKeys = array_values(array_intersect($baseKeys, $invalidKeys));
        $untranslatedKeys = array_unique(array_merge($missing, $invalidBaseKeys));
        $translated = max(0, $total - count($untranslatedKeys));
        $coverage = $total > 0 ? round(($translated / $total) * 100, 2) : 100.0;

        $structurallyValid = $extra === []
            && $empty === []
            && $valueDefects === []
            && $baseValueDefects === []
            && $htmlDefects === []
            && $placeholderMismatches === []
            && $pluralDefects === [];
        $complete = $missing === [] && $structurallyValid;

        return [
            'total_keys' => $total,
            'translated_keys' => $translated,
            'missing' => $missing,
            'extra' => $extra,
            'empty' => $empty,
            'value_defects' => $valueDefects,
            'base_value_defects' => $baseValueDefects,
            'html_defects' => $htmlDefects,
            'placeholder_mismatches' => $placeholderMismatches,
            'plural_defects' => $pluralDefects,
            'coverage_percent' => $coverage,
            'structurally_valid' => $structurallyValid,
            'complete' => $complete,
            'ok' => $complete,
        ];
    }

    /**
     * Flatten a nested associative array into dot-notation leaf keys.
     *
     * @param  array<string, mixed>  $array
     * @return array<string, mixed>
     */
    public function flatten(array $array, string $prefix = ''): array
    {
        $result = [];

        foreach ($array as $key => $value) {
            $dotKey = $prefix === '' ? (string) $key : "{$prefix}.{$key}";

            if (is_array($value) && $value !== [] && array_is_list($value) === false) {
                $result += $this->flatten($value, $dotKey);
            } else {
                $result[$dotKey] = $value;
            }
        }

        return $result;
    }

    private function isPlural(string $value): bool
    {
        return str_contains($value, '|');
    }

    private function containsTagLikeMarkup(string $value): bool
    {
        return preg_match('/<\s*(?:[A-Za-z\/!?])/', $value) === 1;
    }

    /**
     * @return string[] sorted, de-duplicated `:placeholder` names found in $value.
     */
    private function placeholdersIn(string $value): array
    {
        preg_match_all('/:([a-zA-Z_][a-zA-Z0-9_]*)/', $value, $matches);

        $placeholders = array_unique($matches[1]);
        sort($placeholders);

        return $placeholders;
    }

    /**
     * Validate the structural shape of a trans_choice string for $locale.
     * Returns a human-readable defect description, or null when the shape
     * is acceptable.
     */
    private function pluralShapeDefect(string $value, string $locale): ?string
    {
        $segments = explode('|', $value);

        if (count($segments) < 2) {
            return 'plural string has fewer than 2 branches';
        }

        $tagPattern = '/^\s*(\{-?\d+\}|\[(?:-?\d+|\*),(?:-?\d+|\*)\])\s*(.*)$/s';

        $tagged = 0;
        $taggedSegmentIndexes = [];
        $seenTags = [];
        $selectorIntervals = [];
        foreach ($segments as $segmentIndex => $segment) {
            if (trim($segment) === '') {
                return 'plural string contains an empty branch';
            }

            if (preg_match($tagPattern, $segment, $matches) === 1) {
                if (trim($matches[2]) === '') {
                    return sprintf('plural branch "%s" has no message text', $matches[1]);
                }
                if (in_array($matches[1], $seenTags, true)) {
                    return sprintf('plural selector "%s" is duplicated', $matches[1]);
                }
                $interval = $this->selectorInterval($matches[1]);
                if ($interval === null) {
                    return sprintf('plural selector "%s" is invalid', $matches[1]);
                }
                $isTrailingNonNegativeFallback = $matches[1] === '[0,*]'
                    && $segmentIndex === array_key_last($segments);
                foreach ($selectorIntervals as $seenTag => $seenInterval) {
                    if (! $isTrailingNonNegativeFallback
                        && $this->selectorIntervalsOverlap($interval, $seenInterval)) {
                        return sprintf('plural selectors "%s" and "%s" overlap', $seenTag, $matches[1]);
                    }
                }
                $selectorIntervals[$matches[1]] = $interval;
                $taggedSegmentIndexes[] = $segmentIndex;
                $seenTags[] = $matches[1];
                $tagged++;
            } elseif (preg_match('/^\s*[\{\[]/', $segment) === 1) {
                return 'plural branch starts with a malformed selector';
            }
        }

        if ($tagged > 0) {
            $minimum = $this->expectedBarePluralForms($locale);
            if (count($segments) < $minimum) {
                return sprintf(
                    'tagged plural string has %d branch(es), but Laravel may select positional index %d for locale "%s"',
                    count($segments),
                    $minimum - 1,
                    $locale
                );
            }

            $instrumented = [];
            foreach ($segments as $segmentIndex => $segment) {
                if (preg_match($tagPattern, $segment, $matches) === 1) {
                    $instrumented[] = $matches[1].' __branch_'.$segmentIndex.'__';
                } else {
                    $instrumented[] = '__branch_'.$segmentIndex.'__';
                }
            }

            $selector = new MessageSelector;
            $selectedIndexes = [];
            foreach ($this->pluralValidationCounts($value) as $count) {
                $selected = (string) $selector->choose(implode('|', $instrumented), $count, $locale);
                if (preg_match('/__branch_(\d+)__/', $selected, $match) === 1) {
                    $selectedIndexes[] = (int) $match[1];
                }
            }
            foreach ($taggedSegmentIndexes as $segmentIndex) {
                if (! in_array($segmentIndex, $selectedIndexes, true)) {
                    return sprintf('tagged plural branch at position %d is unreachable', $segmentIndex + 1);
                }
            }

            return null;
        }

        // Fully bare (positional) branches: segment count must match the
        // locale's expected plural-form count.
        $expected = $this->expectedBarePluralForms($locale);

        if (count($segments) !== $expected) {
            return sprintf(
                'bare plural string has %d branch(es), expected %d for locale "%s"',
                count($segments),
                $expected,
                $locale
            );
        }

        return null;
    }

    /**
     * Compare placeholders in the branches Laravel really selects for a
     * representative integer domain, rather than comparing one union across
     * the whole plural string (which can hide a defect in one branch).
     *
     * @return array{expected: string[], actual: string[]}|null
     */
    private function pluralPlaceholderMismatch(string $base, string $target, string $targetLocale): ?array
    {
        $selector = new MessageSelector;
        $counts = $this->pluralValidationCounts($base, $target);

        foreach ($counts as $count) {
            // Laravel's trans_choice always injects the numeric count into
            // replacements, even when the canonical branch does not display
            // it. Compare caller-supplied placeholders exactly and treat
            // :count as the implicit plural runtime variable.
            $expected = array_values(array_diff(
                $this->placeholdersIn((string) $selector->choose($base, $count, 'en')),
                ['count']
            ));
            $actual = array_values(array_diff(
                $this->placeholdersIn((string) $selector->choose($target, $count, $targetLocale)),
                ['count']
            ));

            if ($expected !== $actual) {
                return ['expected' => $expected, 'actual' => $actual];
            }
        }

        return null;
    }

    /**
     * Return integer witnesses for every explicit selector plus the standard
     * Laravel plural domain. This prevents a tagged branch outside 0..200
     * from escaping branch-specific placeholder validation.
     *
     * @return int[]
     */
    private function pluralValidationCounts(string ...$messages): array
    {
        $counts = array_merge(range(0, 200), [1000, 1000000]);
        $tagPattern = '/\{-?\d+\}|\[(?:-?\d+|\*),(?:-?\d+|\*)\]/';

        foreach ($messages as $message) {
            preg_match_all($tagPattern, $message, $matches);
            foreach ($matches[0] as $tag) {
                $interval = $this->selectorInterval($tag);
                if ($interval === null) {
                    continue;
                }
                [$lower, $upper] = $interval;
                $witnesses = [];
                if ($lower !== null) {
                    if ($lower > PHP_INT_MIN) {
                        $witnesses[] = $lower - 1;
                    }
                    $witnesses[] = $lower;
                    if ($lower < PHP_INT_MAX) {
                        $witnesses[] = $lower + 1;
                    }
                }
                if ($upper !== null) {
                    if ($upper > PHP_INT_MIN) {
                        $witnesses[] = $upper - 1;
                    }
                    $witnesses[] = $upper;
                    if ($upper < PHP_INT_MAX) {
                        $witnesses[] = $upper + 1;
                    }
                }
                foreach ($witnesses as $witness) {
                    $counts[] = $witness;
                }
            }
        }

        $counts = array_values(array_unique($counts));
        sort($counts);

        return $counts;
    }

    /** @return array{int|null, int|null}|null */
    private function selectorInterval(string $tag): ?array
    {
        if (preg_match('/^\{(-?\d+)\}$/', $tag, $match) === 1) {
            $value = $this->parseSelectorInteger($match[1]);

            return $value === false ? null : [$value, $value];
        }

        if (preg_match('/^\[((?:-?\d+)|\*),((?:-?\d+)|\*)\]$/', $tag, $match) !== 1) {
            return null;
        }
        if ($match[1] === '*' && $match[2] === '*') {
            return null;
        }

        $lower = $match[1] === '*' ? null : $this->parseSelectorInteger($match[1]);
        $upper = $match[2] === '*' ? null : $this->parseSelectorInteger($match[2]);
        if ($lower === false || $upper === false) {
            return null;
        }
        if ($lower !== null && $upper !== null && $lower > $upper) {
            return null;
        }

        return [$lower, $upper];
    }

    private function parseSelectorInteger(string $raw): int|false
    {
        $negative = str_starts_with($raw, '-');
        $digits = ltrim($negative ? substr($raw, 1) : $raw, '0');
        $digits = $digits === '' ? '0' : $digits;
        $limit = $negative ? substr((string) PHP_INT_MIN, 1) : (string) PHP_INT_MAX;

        if (strlen($digits) > strlen($limit)
            || (strlen($digits) === strlen($limit) && strcmp($digits, $limit) > 0)) {
            return false;
        }

        return (int) $raw;
    }

    /** @param array{int|null, int|null} $left @param array{int|null, int|null} $right */
    private function selectorIntervalsOverlap(array $left, array $right): bool
    {
        $leftLower = $left[0] ?? PHP_INT_MIN;
        $leftUpper = $left[1] ?? PHP_INT_MAX;
        $rightLower = $right[0] ?? PHP_INT_MIN;
        $rightUpper = $right[1] ?? PHP_INT_MAX;

        return max($leftLower, $rightLower) <= min($leftUpper, $rightUpper);
    }

    private function expectedBarePluralForms(string $locale): int
    {
        $selector = new MessageSelector;
        $maxIndex = 0;

        // Laravel's rules are integer/modulo based; 0..200 covers every
        // branch used by the bundled selector, including modulo-100 cases.
        for ($count = 0; $count <= 200; $count++) {
            $maxIndex = max($maxIndex, $selector->getPluralIndex($locale, $count));
        }

        return $maxIndex + 1;
    }
}
