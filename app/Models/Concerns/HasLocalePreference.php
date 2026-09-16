<?php

namespace App\Models\Concerns;

use App\Support\LocaleNormalizer;

trait HasLocalePreference
{
    public function preferredLocale(): ?string
    {
        return app(LocaleNormalizer::class)->normalize($this->getAttribute('locale'));
    }
}
