<?php

namespace App\Support;

class LocaleNormalizer
{
    /** @param array<string, array<string, mixed>>|null $locales */
    public function __construct(private readonly ?array $locales = null) {}

    public function normalize(?string $locale): ?string
    {
        if ($locale === null || trim($locale) === '') {
            return null;
        }

        $candidate = strtolower(str_replace('_', '-', trim($locale)));

        foreach ($this->supported() as $code => $metadata) {
            if ($candidate === strtolower($code)) {
                return $code;
            }

            foreach ($metadata['aliases'] ?? [] as $alias) {
                if ($candidate === strtolower(str_replace('_', '-', $alias))) {
                    return $code;
                }
            }
        }

        $base = explode('-', $candidate, 2)[0];

        return array_key_exists($base, $this->supported()) ? $base : null;
    }

    public function fallback(): string
    {
        return $this->normalize((string) config('app.fallback_locale'))
            ?? array_key_first($this->supported())
            ?? 'en';
    }

    /** @return array<string, mixed> */
    public function metadata(?string $locale = null): array
    {
        $code = $this->normalize($locale ?? app()->getLocale()) ?? $this->fallback();

        return $this->supported()[$code];
    }

    /** @return array<string, array<string, mixed>> */
    private function supported(): array
    {
        return $this->locales ?? config('locales.supported', []);
    }
}
