<?php

namespace Tests\Unit;

use App\Support\LocaleNormalizer;
use App\Support\LocaleResolver;
use Illuminate\Http\Request;
use Illuminate\Session\ArraySessionHandler;
use Illuminate\Session\Store;
use Tests\TestCase;

class LocaleResolverTest extends TestCase
{
    private function request(array $server = [], array $session = [], ?object $user = null): Request
    {
        $server = array_merge(['HTTP_ACCEPT_LANGUAGE' => ''], $server);
        $request = Request::create('/', 'GET', [], [], [], $server);
        $store = new Store('test', new ArraySessionHandler(120));
        $store->start();
        foreach ($session as $key => $value) {
            $store->put($key, $value);
        }
        $request->setLaravelSession($store);
        $request->setUserResolver(fn () => $user);

        return $request;
    }

    public function test_precedence_is_user_then_session_then_weighted_header_then_app_default(): void
    {
        $resolver = new LocaleResolver(app(LocaleNormalizer::class));
        $user = new class
        {
            public function preferredLocale(): ?string
            {
                return 'sk';
            }
        };

        $request = $this->request(['HTTP_ACCEPT_LANGUAGE' => 'en;q=1, sk;q=.5'], ['locale' => 'en'], $user);
        $this->assertSame('sk', $resolver->resolve($request));

        $request = $this->request(['HTTP_ACCEPT_LANGUAGE' => 'sk;q=1, en;q=.5'], ['locale' => 'en']);
        $this->assertSame('en', $resolver->resolve($request));

        $request = $this->request(['HTTP_ACCEPT_LANGUAGE' => 'en;q=.4, sk-SK;q=.9']);
        $this->assertSame('sk', $resolver->resolve($request));

        config(['app.locale' => 'sk']);
        $this->assertSame('sk', $resolver->resolve($this->request()));
    }

    public function test_zero_quality_supported_language_is_ignored(): void
    {
        config(['app.locale' => 'en']);

        $this->assertSame('en', (new LocaleResolver(app(LocaleNormalizer::class)))->resolve(
            $this->request(['HTTP_ACCEPT_LANGUAGE' => 'ja-JP;q=1, sk;q=0'])
        ));
    }

    public function test_malformed_quality_values_are_ignored(): void
    {
        $resolver = new LocaleResolver(app(LocaleNormalizer::class));

        $this->assertSame('en', $resolver->resolve(
            $this->request(['HTTP_ACCEPT_LANGUAGE' => 'sk;q=bogus, en;q=0.5'])
        ));
        $this->assertSame('en', $resolver->resolve(
            $this->request(['HTTP_ACCEPT_LANGUAGE' => 'sk;q=1.5, en;q=0.5'])
        ));
    }

    public function test_bare_or_duplicate_quality_parameters_invalidate_the_whole_language_range(): void
    {
        $resolver = new LocaleResolver(app(LocaleNormalizer::class));

        foreach ([
            'sk;q,en;q=.5',
            'sk;q=.2;q,en;q=.5',
            'sk;Q=.2; q ,en;q=.5',
        ] as $header) {
            $this->assertSame('en', $resolver->resolve(
                $this->request(['HTTP_ACCEPT_LANGUAGE' => $header])
            ), $header);
        }
    }

    public function test_malformed_whole_language_range_items_are_ignored(): void
    {
        $resolver = new LocaleResolver(app(LocaleNormalizer::class));

        foreach ([
            'sk;q .2,en;q=.5',
            'sk;q=.9;q=.8,en;q=.5',
            'sk;level=1,en;q=.5',
            'sk;,en;q=.5',
            'sk;q=.9;,en;q=.5',
            'sk;;q=.9,en;q=.5',
            '123;q=1,en;q=.5',
            '-sk;q=1,en;q=.5',
            'sk-;q=1,en;q=.5',
            'sk_SK;q=1,en;q=.5',
        ] as $header) {
            $this->assertSame('en', $resolver->resolve(
                $this->request(['HTTP_ACCEPT_LANGUAGE' => $header])
            ), $header);
        }
    }

    public function test_control_characters_invalidate_language_range_items(): void
    {
        $resolver = new LocaleResolver(app(LocaleNormalizer::class));

        foreach ([
            "sk\0;q=.9,en;q=.5",
            "sk\v;q=.9,en;q=.5",
            "sk\r;q=.9,en;q=.5",
            "sk\n;q=.9,en;q=.5",
        ] as $header) {
            $this->assertSame('en', $resolver->resolve(
                $this->request(['HTTP_ACCEPT_LANGUAGE' => $header])
            ), bin2hex($header));
        }
    }

    public function test_control_characters_invalidate_quality_parameters(): void
    {
        $resolver = new LocaleResolver(app(LocaleNormalizer::class));

        foreach (["\0", "\v", "\r", "\n"] as $control) {
            foreach ([
                "sk;{$control}q=.9,en;q=.5",
                "sk;q{$control}=.9,en;q=.5",
                "sk;q={$control}.9,en;q=.5",
                "sk;q=.9{$control},en;q=.5",
            ] as $header) {
                $this->assertSame('en', $resolver->resolve(
                    $this->request(['HTTP_ACCEPT_LANGUAGE' => $header])
                ), bin2hex($header));
            }
        }
    }

    public function test_whole_language_range_parser_accepts_case_and_optional_whitespace(): void
    {
        $resolver = new LocaleResolver(app(LocaleNormalizer::class));

        foreach ([
            ' SK ; Q = .9 , en;q=.8',
            "\tSk-SK\t;\tq\t=\t1.000\t, en;q=.9",
            'sK;q=1.,en;q=.9',
            'sk-Latn-SK;Q=1,en;q=.9',
        ] as $header) {
            $this->assertSame('sk', $resolver->resolve(
                $this->request(['HTTP_ACCEPT_LANGUAGE' => $header])
            ), $header);
        }
    }

    public function test_scientific_notation_quality_is_ignored(): void
    {
        $resolver = new LocaleResolver(app(LocaleNormalizer::class));

        $this->assertSame('en', $resolver->resolve(
            $this->request(['HTTP_ACCEPT_LANGUAGE' => 'sk;q=1e-1,en;q=.05'])
        ));
    }

    public function test_signed_quality_is_ignored(): void
    {
        $resolver = new LocaleResolver(app(LocaleNormalizer::class));

        $this->assertSame('en', $resolver->resolve(
            $this->request(['HTTP_ACCEPT_LANGUAGE' => 'sk;q=+0.5,en;q=.4'])
        ));
    }

    public function test_other_invalid_http_quality_forms_are_ignored(): void
    {
        $resolver = new LocaleResolver(app(LocaleNormalizer::class));

        foreach ([
            'sk;q=,en;q=.4',
            'sk;q=0.5000,en;q=.4',
            'sk;q=0,5,en;q=.4',
            'sk;q=1.001,en;q=.4',
            'sk;q=-0.5,en;q=.4',
            'sk;q=0.1;q=0.9,en;q=.4',
        ] as $header) {
            $this->assertSame('en', $resolver->resolve(
                $this->request(['HTTP_ACCEPT_LANGUAGE' => $header])
            ), $header);
        }
    }

    public function test_http_quality_grammar_accepts_supported_forms(): void
    {
        $resolver = new LocaleResolver(app(LocaleNormalizer::class));

        foreach ([
            'sk;q=0.001,en;q=0',
            'sk;q=.001,en;q=0',
            'sk;q=0.999,en;q=.9',
            'sk;q=.999,en;q=.9',
            'sk;q=1,en;q=.9',
            'sk;q=1.,en;q=.9',
            'sk;q=1.000,en;q=.9',
            'sk; Q = .9, en; q = .8',
            'sk,en;q=.9',
        ] as $header) {
            $this->assertSame('sk', $resolver->resolve(
                $this->request(['HTTP_ACCEPT_LANGUAGE' => $header])
            ), $header);
        }

        foreach (['0', '0.', '0.0', '0.00', '0.000'] as $quality) {
            $this->assertSame('en', $resolver->resolve(
                $this->request(['HTTP_ACCEPT_LANGUAGE' => "sk;q={$quality},en;q=.001"])
            ), $quality);
        }
    }

    public function test_explicit_locale_overrides_higher_quality_wildcard_for_that_locale(): void
    {
        config(['app.locale' => 'sk']);

        $this->assertSame('en', (new LocaleResolver(app(LocaleNormalizer::class)))->resolve(
            $this->request(['HTTP_ACCEPT_LANGUAGE' => '*;q=1, sk;q=0.9'])
        ));
    }

    public function test_zero_quality_explicit_locale_excludes_it_despite_wildcard(): void
    {
        config(['app.locale' => 'en']);

        $this->assertSame('sk', (new LocaleResolver(app(LocaleNormalizer::class)))->resolve(
            $this->request(['HTTP_ACCEPT_LANGUAGE' => '*;q=1, en;q=0, sk;q=0.5'])
        ));
    }

    public function test_higher_quality_explicit_supported_locale_wins_over_wildcard(): void
    {
        config(['app.locale' => 'en']);

        $this->assertSame('sk', (new LocaleResolver(app(LocaleNormalizer::class)))->resolve(
            $this->request(['HTTP_ACCEPT_LANGUAGE' => 'sk;q=1, *;q=0.9'])
        ));
    }

    public function test_equal_quality_preferences_resolve_in_header_order(): void
    {
        config(['app.locale' => 'en']);
        $resolver = new LocaleResolver(app(LocaleNormalizer::class));

        $this->assertSame('sk', $resolver->resolve(
            $this->request(['HTTP_ACCEPT_LANGUAGE' => 'sk;q=0.8, en;q=0.8'])
        ));
        $this->assertSame('en', $resolver->resolve(
            $this->request(['HTTP_ACCEPT_LANGUAGE' => 'en;q=0.8, sk;q=0.8'])
        ));
        $this->assertSame('en', $resolver->resolve(
            $this->request(['HTTP_ACCEPT_LANGUAGE' => '*;q=0.8, sk;q=0.8'])
        ));
        $this->assertSame('sk', $resolver->resolve(
            $this->request(['HTTP_ACCEPT_LANGUAGE' => 'sk;q=0.8, *;q=0.8'])
        ));
    }

    public function test_unsupported_and_wildcard_only_preferences_use_app_locale_then_fallback(): void
    {
        $resolver = new LocaleResolver(app(LocaleNormalizer::class));
        config(['app.locale' => 'sk', 'app.fallback_locale' => 'en']);

        $this->assertSame('sk', $resolver->resolve(
            $this->request(['HTTP_ACCEPT_LANGUAGE' => 'ja-JP;q=1, *;q=0.5'])
        ));

        config(['app.locale' => 'unsupported']);
        $this->assertSame('en', $resolver->resolve(
            $this->request(['HTTP_ACCEPT_LANGUAGE' => 'ja-JP;q=1, *;q=0.5'])
        ));
    }

    public function test_invalid_preferences_fall_through_to_english_fallback(): void
    {
        config(['app.locale' => 'xx', 'app.fallback_locale' => 'en']);
        $user = new class
        {
            public function preferredLocale(): ?string
            {
                return null;
            }
        };

        $this->assertSame('en', (new LocaleResolver(app(LocaleNormalizer::class)))->resolve(
            $this->request(['HTTP_ACCEPT_LANGUAGE' => 'ja-JP'], ['locale' => 'invalid'], $user)
        ));
    }
}
