# Contributing translations

CortenDesk ships fully translated in English (`en`, canonical) and Slovak
(`sk`). Spanish (`es`), German (`de`), Russian (`ru`), French (`fr`), and
Italian (`it`) are registered community starters: translated keys are used
when present and every missing key falls back to English. Adding any further
language means declaring it in one registry file and providing the catalogs
it points to — there is no application code to write.

This guide covers:

- how translations are organized (console + web client)
- the registry file, `config/locales.json`
- how to add a new language
- English fallback behavior
- placeholder and plural syntax
- the coverage/quality-gate checkers and how to run them locally

## Where translations live

There are two independent translation surfaces:

1. **The console (Laravel)** — PHP catalogs under `lang/<locale>/`, one file
   per domain: `auth.php`, `devices.php`, `identity.php`,
   `address_books.php`, `audit.php`, `settings.php`, `notifications.php`,
   `ui.php`. Each file returns a (possibly nested) associative array of
   `'key' => 'Message'` pairs, used the normal Laravel way
   (`__('auth.login.title')`, `trans_choice('devices.count.device', $n)`,
   etc.).

2. **The web client** — a single flat JSON catalog per locale at
   `lang/webclient/<locale>.json`. Keys are dot-separated strings
   (`"action.connect"`, `"worker.timeout"`) mapping to either a plain string
   or a `{plural: ...}` / `{select: ...}` object (see
   [Plurals in the web client](#plurals-in-the-web-client) below). It is
   consumed by `webclient/src/i18n.ts`.

English and Slovak are catalog-complete. Community starter catalogs may omit
keys deliberately; omissions are measured as untranslated coverage and use
the English fallback. Extra keys, empty values, malformed message shapes,
and placeholder or plural defects are never allowed, even in a partial
catalog.

## The locale registry: `config/locales.json`

Every supported language is declared once in the shared, machine-readable
`config/locales.json`. Laravel's `config/locales.php` only loads this JSON;
the Node checker reads the same file directly:

```json
{
  "php_intentional_fallbacks": ["ui:fallback_probe"],
  "supported": {
    "en": {
      "aliases": ["en-US", "en-GB"],
      "native_name": "English",
      "html_lang": "en",
      "dir": "ltr",
      "web_plural_categories": ["one", "other"]
    },
    "de": {
      "aliases": ["de-DE", "de-AT", "de-CH"],
      "native_name": "Deutsch",
      "html_lang": "de",
      "dir": "ltr",
      "web_plural_categories": ["one", "other"],
      "community": true
    }
  }
}
```

- **`php_intentional_fallbacks`** — narrow `catalog:key` allowlist for canonical
  PHP messages that deliberately exist only in English to keep the runtime
  fallback path covered. Both `locale:check` and `locale:check --all` apply it
  automatically; a listed key appearing in any target catalog is an extra-key
  defect.
- **`aliases`** — additional locale tags (browser `Accept-Language` values,
  regional variants) that should resolve to this language. `App\Support\LocaleNormalizer`
  matches the exact code first, then aliases, then falls back to the
  bare language subtag (e.g. an unlisted `sk-CZ` still resolves to `sk`
  via the `sk-` prefix rule) before finally falling back to
  `app.fallback_locale`.
- **`native_name`** — shown in the language switcher.
- **`html_lang`** — written to `<html lang="...">`.
- **`dir`** — `ltr` or `rtl`, for the `<html dir="...">` attribute.
- **`web_plural_categories`** — complete integer plural branch set required
  in each translated webclient plural message. Always include `other` as the
  runtime fallback branch.
- **`community`** — set to `true` only for an intentionally partial community
  locale. The language picker marks it as a community translation; the flag
  does not claim any fixed completion percentage.

The registry is the single source of truth: Laravel's language switcher,
webclient catalog bootstrap, negotiation, PHP `locale:check --all`, Node
`check-i18n.mjs --all`, and their tests all derive their locale list and
completion policy from it.

## Adding a new supported language

To add, say, German (`de`):

1. **Register it** in `config/locales.json`:

   ```json
   "de": {
     "aliases": ["de-DE", "de-AT", "de-CH"],
     "native_name": "Deutsch",
     "html_lang": "de",
     "dir": "ltr",
     "web_plural_categories": ["one", "other"],
     "community": true
   }
   ```

2. **Create the console catalog skeletons**: create every file present under
   `lang/en/` in the new `lang/de/` directory. A starter file may simply
   `return [];`. Add only keys that have actually been translated; do not copy
   English values and call them translated, and never use an empty string as a
   placeholder. All eight domain files (`auth.php`, `devices.php`,
   `identity.php`, `address_books.php`, `audit.php`, `settings.php`,
   `notifications.php`, `ui.php`) must exist.

3. **Create the web client catalog** at `lang/webclient/de.json`. Start with
   `{}` and add only genuinely translated top-level keys. Any translated
   `{plural: ...}` / `{select: ...}` message must preserve its selector field,
   placeholders, and the complete branch shape required by the target locale.

4. **Run the partial checkers** (see [Running the checkers](#running-the-checkers)):

   ```bash
   php artisan locale:check --target=de --allow-partial
   node webclient/scripts/check-i18n.mjs --target=de --allow-partial
   ```

   Both commands print the real coverage percentage. Missing keys are accepted
   and fall back to English; structural defects still fail.

5. Open a pull request with the keys you have translated. A community locale
   may improve incrementally and does not need to reach 100% in one change.

## English fallback

`en` is the canonical, always-complete locale — every other language's
catalogs are validated against it. Untranslated console UI falls back to
`app.fallback_locale` (`en` by default, see `config/app.php`); the web client
falls back the same way via the `fallbackCatalog` it receives at bootstrap
(see `webclient/src/i18n.ts`).

For complete locales such as Slovak, missing keys fail the strict checker. For
community locales, run the checker with `--allow-partial`: missing keys are
reported as untranslated coverage and use English at runtime, while extra
keys, empty values, malformed messages, and placeholder/plural mismatches
still fail. Omit an untranslated key entirely — never add an empty value.

The one intentional complete-locale exception is `ui.fallback_probe`, a
diagnostic key that exists only in `lang/en/ui.php` specifically to prove the
fallback mechanism itself works (see `tests/Feature/LocaleFoundationTest.php`);
the strict Slovak checker excludes it explicitly and it should not be
translated.

## Placeholder syntax

**Console (Laravel):** placeholders use Laravel's `:name` syntax and are
substituted via the second argument to `__()` / `trans()`:

```php
// lang/en/auth.php
'too_many_attempts' => 'Too many attempts. Try again in :seconds seconds.',

// lang/sk/auth.php
'too_many_attempts' => 'Príliš veľa pokusov. Skúste to znova o :seconds sekúnd.',
```

Every `:placeholder` token required by the selected English branch must appear,
verbatim, in the corresponding runtime branch of the translation. A placeholder
that appears only in another branch cannot hide the omission. Non-string
values, empty branches, malformed selectors, and unknown extra placeholders
fail the checker.

**Web client:** placeholders use curly-brace syntax, `{name}`:

```json
// lang/webclient/en.json
"worker.connectionLost": "{connection} connection lost",

// lang/webclient/sk.json
"worker.connectionLost": "Spojenie {connection} bolo prerušené",
```

Same rule: every `{placeholder}` required by an English plural/select branch
must be present in the corresponding translated branch. Each branch value must
be a non-empty string.

## Plurals

### Console (Laravel `trans_choice`)

Laravel's `trans_choice` splits a message into `|`-separated branches. The
simplest form is positional — `'singular|plural'` — but branches can also be
explicitly tagged with an exact value (`{n}`) or an inclusive range
(`[n,m]`, with `*` meaning "and beyond"):

```php
// lang/en/auth.php — two positional branches (1 vs. everything else)
'codes_remaining' => '{0} You have no recovery codes remaining.'
    .'|{1} You have one recovery code remaining.'
    .'|[2,*] You have :count recovery codes remaining.',
```

English only needs a singular/plural distinction. **Slovak (and Czech) need
three plural forms** — Laravel's `MessageSelector` picks case `0` for
`:count == 1`, case `1` for `2 <= :count <= 4`, and case `2` otherwise. A
correct Slovak plural therefore needs three branches when written
positionally, or explicit `[2,4]` / `[5,*]` ranges when tagged:

```php
// lang/sk/auth.php
'codes_remaining' => '{0} Nemáte žiadne záchranné kódy.'
    .'|{1} Zostáva vám jeden záchranný kód.'
    .'|[2,4] Zostávajú vám :count záchranné kódy.'
    .'|[5,*] Zostáva vám :count záchranných kódov.',
```

The checker derives positional branch counts from Laravel's actual
`MessageSelector`: English, German, Spanish, French, and Italian use two bare
branches; Slovak and Russian use three. Tagged strings must still contain
enough branches for Laravel's positional fallback, every selector must be
well-formed, integer-only, within the PHP integer range, non-overlapping, and
unique, and every branch must contain message text and be reachable by at least
one integer count. Decimal or out-of-range tagged selectors fail closed. The
checker samples
both sides of every finite selector boundary so a far-away or trailing fallback
branch cannot escape validation. Placeholder parity is tested against branches
selected by Laravel for representative counts, not as one union across the
entire message. Laravel `trans_choice` always injects `:count`; it is therefore
the implicit plural variable, while every other caller-supplied placeholder must
match the canonical branch selected for the same runtime count.

### Plurals in the web client

The web client's `Message` type (`webclient/src/i18n.ts`) uses an object
shape instead of pipe-delimited strings:

```json
"file.selected": {
  "plural": "count",
  "one": "{selected} of {count} selected",
  "few": "{selected} of {count} selected",
  "other": "{selected} of {count} selected"
}
```

`"plural"` names the parameter used to select a branch; the branch is picked
at runtime with `Intl.PluralRules(locale).select(count)`, which returns
CLDR categories (`zero`, `one`, `two`, `few`, `many`, `other`) appropriate to
the locale. Slovak's `Intl.PluralRules('sk')` returns `one` for 1, `few` for
2–4, and `other` for everything else (including 0) — so a Slovak plural
message needs (at least) `one`, `few`, and `other` branches:

```json
"file.selected": {
  "plural": "count",
  "one": "Vybrané: {selected} z {count}",
  "few": "Vybrané: {selected} z {count}",
  "other": "Vybrané: {selected} z {count}"
}
```

A non-plural, categorical message uses `"select"` instead of `"plural"`,
naming a string-valued parameter whose value selects the branch directly
(plus a required `"other"` fallback branch). The checker validates `select`
branches against the canonical message. For plural messages it enforces the
integer categories declared in the shared registry and compares placeholders
through the canonical and target branches selected for the same runtime count
(the category names can differ between locales); the checker also derives
categories from `Intl.PluralRules` across small and large integers when used
for a single manual comparison. For example, Slovak requires
`one`/`few`/`other`, Russian adds `many`, and Spanish/French/Italian need
`many` for large values such as `1,000,000`. The canonical English message must
contain every category reachable for English integer counts (`one` and `other`),
though it may include compatible extra branches used by target catalogs. Omit a
plural key until all of its required target branches have been translated.

## Running the checkers

### Console catalogs

```bash
php artisan locale:check
```

This compares every `lang/en/<catalog>.php` against its `lang/sk/<catalog>.php`
counterpart (or another target with `--target=<code>`) and prints a
coverage table:

```
+---------------+------+---------+---------+----------+--------+
| Catalog       | Keys | Missing | Defects | Coverage | Status |
+---------------+------+---------+---------+----------+--------+
| address_books | 102  | 0       | 0       | 100.00%  | OK     |
| devices       | 294  | 0       | 0       | 100.00%  | OK     |
| ...           | ...  | ...     | ...     | ...      | ...    |
+---------------+------+---------+---------+----------+--------+
Overall coverage: 1476/1476 keys (100.00%)
Locale check PASSED for target locale 'sk': all catalogs fully covered.
```

Useful options:

- `--target=<code>` — check one locale against `en` (default `sk`).
- `--all` — check every registered non-base locale. Complete locales stay
  strict; locales declared with `"community": true` automatically use the
  partial policy.
- `--allow-partial` — accept missing keys as English fallback while still
  failing all structural defects; the table reports `Missing`, `Defects`,
  per-catalog coverage, and exact overall translated/total coverage.
- `--base=en` — explicit canonical-base selector; any other value fails closed.
- `--path=<dir>` — check catalogs somewhere other than `lang/` (used by the
  command's own test suite against fixture catalogs).
- `--ignore=<catalog>:<key.path>` — declare one canonical key as an
  intentional base-only fallback (repeatable). It is removed from the coverage
  denominator but must remain absent from every target catalog. The registry
  already supplies the `ui.fallback_probe` fallback-demo key automatically.

Both checkers accept only `en` as the canonical base; another `--base` value
fails closed rather than applying English-specific fallback/plural rules to a
different catalog. They validate canonical values as well as targets. HTML and
tag-like openings are forbidden, including unterminated tags, comments,
declarations, and processing instructions.

Without `--allow-partial`, every missing key is a failure. With it, missing
keys are coverage gaps; extra keys, empty or non-string values, HTML/tag-like
fragments, placeholder mismatches, malformed plural branches/selectors, and
missing catalog files still exit non-zero. Structurally invalid keys do not
count as translated in coverage.

### Web client catalog

```bash
node webclient/scripts/check-i18n.mjs
node webclient/scripts/check-i18n.mjs --all
```

The first command compares `lang/webclient/en.json` against
`lang/webclient/sk.json` (or another target via `--target=` and `--path=`).
`--all` reads `config/locales.json` and checks every registered
non-base catalog with its declared strict/community policy. Both modes report
coverage and validate catalog object shape, non-empty string branches,
`{placeholder}` parity per branch, and locale-aware `plural`/`select` shape.
Use `--allow-partial` only for a manual single-locale check; missing keys then
fall back to English, but every structural defect still exits non-zero.

The webclient Vitest suite (`npm test`, run from `webclient/`) executes the
same registry-driven gate automatically. It also keeps strict EN/SK key
parity and verifies that locale/catalog/fallback bootstrap data reaches every
worker explicitly.
