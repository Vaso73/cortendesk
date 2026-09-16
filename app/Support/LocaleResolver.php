<?php

namespace App\Support;

use Illuminate\Http\Request;

class LocaleResolver
{
    public function __construct(private readonly LocaleNormalizer $normalizer) {}

    public function resolve(Request $request): string
    {
        $user = $request->user();
        if ($user && method_exists($user, 'preferredLocale')) {
            $locale = $this->normalizer->normalize($user->preferredLocale());
            if ($locale !== null) {
                return $locale;
            }
        }

        if ($request->hasSession()) {
            $locale = $this->normalizer->normalize($request->session()->get('locale'));
            if ($locale !== null) {
                return $locale;
            }
        }

        if ($request->headers->has('Accept-Language')) {
            $preferences = $this->preferences($request->headers->get('Accept-Language', ''));
            $wildcard = null;
            $explicit = [];

            foreach ($preferences as $preference) {
                if ($preference['range'] === '*') {
                    $wildcard ??= $preference;

                    continue;
                }

                $locale = $this->normalizer->normalize($preference['range']);
                if ($locale === null) {
                    continue;
                }

                $specificity = substr_count(str_replace('_', '-', $preference['range']), '-') + 1;
                if (! isset($explicit[$locale]) || $specificity > $explicit[$locale]['specificity']) {
                    $explicit[$locale] = $preference + ['specificity' => $specificity];
                }
            }

            $candidates = [];
            foreach (array_keys(config('locales.supported', [])) as $order => $supported) {
                $locale = $this->normalizer->normalize((string) $supported);
                $preference = $locale === null ? null : ($explicit[$locale] ?? $wildcard);
                if ($locale === null || $preference === null || $preference['quality'] <= 0) {
                    continue;
                }

                $candidates[] = [
                    'locale' => $locale,
                    'quality' => $preference['quality'],
                    'index' => $preference['index'],
                    'order' => $order,
                ];
            }

            if ($candidates !== []) {
                $default = $this->normalizer->normalize((string) config('app.locale'))
                    ?? $this->normalizer->fallback();

                usort($candidates, static fn (array $left, array $right): int => $right['quality'] <=> $left['quality']
                    ?: $left['index'] <=> $right['index']
                    ?: ($right['locale'] === $default) <=> ($left['locale'] === $default)
                    ?: $left['order'] <=> $right['order']);

                return $candidates[0]['locale'];
            }
        }

        return $this->normalizer->normalize((string) config('app.locale'))
            ?? $this->normalizer->fallback();
    }

    /** @return list<array{range: string, quality: float, index: int}> */
    private function preferences(string $header): array
    {
        $preferences = [];

        foreach (explode(',', $header) as $index => $value) {
            $segments = explode(';', trim($value, " \t"));
            $range = trim((string) array_shift($segments), " \t");

            if (preg_match('/\A(?:\*|[a-z]+(?:-[a-z0-9]+)*)\z/i', $range) !== 1) {
                continue;
            }

            $quality = 1.0;
            if ($segments !== []) {
                if (count($segments) !== 1
                    || preg_match('/\A[ \t]*q[ \t]*=[ \t]*((?:0(?:\.\d{0,3})?|\.\d{1,3}|1(?:\.0{0,3})?))[ \t]*\z/i', $segments[0], $match) !== 1) {
                    continue;
                }

                $quality = (float) $match[1];
            }

            $preferences[] = [
                'range' => $range,
                'quality' => $quality,
                'index' => $index,
            ];
        }

        return $preferences;
    }
}
