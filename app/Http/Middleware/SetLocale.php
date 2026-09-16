<?php

namespace App\Http\Middleware;

use App\Support\LocaleNormalizer;
use App\Support\LocaleResolver;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SetLocale
{
    public function __construct(
        private readonly LocaleResolver $resolver,
        private readonly LocaleNormalizer $normalizer,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        $locale = $this->resolver->resolve($request);
        app()->setLocale($locale);
        config(['app.locale_metadata' => $this->normalizer->metadata($locale)]);

        return $next($request);
    }
}
