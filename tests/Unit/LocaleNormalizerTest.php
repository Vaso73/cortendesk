<?php

namespace Tests\Unit;

use App\Support\LocaleNormalizer;
use Tests\TestCase;

class LocaleNormalizerTest extends TestCase
{
    public function test_it_normalizes_aliases_and_regions(): void
    {
        $normalizer = app(LocaleNormalizer::class);

        $this->assertSame('en', $normalizer->normalize('EN_us'));
        $this->assertSame('sk', $normalizer->normalize('sk-SK'));
        $this->assertSame('sk', $normalizer->normalize('sk-CZ'));
    }

    public function test_it_rejects_empty_and_unsupported_locales(): void
    {
        $normalizer = app(LocaleNormalizer::class);

        $this->assertNull($normalizer->normalize(null));
        $this->assertNull($normalizer->normalize(''));
        $this->assertNull($normalizer->normalize('ja-JP'));
        $this->assertSame('en', $normalizer->fallback());
    }
}
