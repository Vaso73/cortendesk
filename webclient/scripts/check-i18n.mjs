#!/usr/bin/env node
/**
 * Locale coverage / quality-gate checker for the webclient translation
 * catalogs (lang/webclient/en.json vs lang/webclient/sk.json).
 *
 * Mirrors the PHP `php artisan locale:check` command but for the
 * webclient's flat-key JSON catalogs and its {plural}/{select} message
 * shapes (see webclient/src/i18n.ts). Checks, per catalog pair:
 *   - missing keys   (present in en, absent in sk)
 *   - extra keys     (present in sk, absent in en)
 *   - empty values   (empty string message, or empty branch value)
 *   - placeholder mismatches for `{name}`-style tokens
 *   - plural/select branch-shape parity (same `plural`/`select` field,
 *     same set of branch names, e.g. one/few/other)
 *
 * Exits 0 when both catalogs match with no defects, non-zero otherwise.
 * Prints a coverage percentage summary either way.
 *
 * Usage:
 *   node webclient/scripts/check-i18n.mjs
 *   node webclient/scripts/check-i18n.mjs --base=en --target=sk --path=lang/webclient
 */

import { readFileSync } from 'node:fs';
import { resolve, dirname } from 'node:path';
import { fileURLToPath } from 'node:url';

const __dirname = dirname(fileURLToPath(import.meta.url));
const repoRoot = resolve(__dirname, '../..');

function parseArgs(argv) {
  const opts = { base: 'en', target: 'sk', path: 'lang/webclient', allowPartial: false, all: false };
  for (const arg of argv) {
    if (arg === '--allow-partial') {
      opts.allowPartial = true;
      continue;
    }
    if (arg === '--all') {
      opts.all = true;
      continue;
    }
    const match = /^--([a-z]+)=(.*)$/.exec(arg);
    if (match) opts[match[1]] = match[2];
  }
  return opts;
}

function placeholdersIn(value) {
  const matches = [...value.matchAll(/\{([a-zA-Z][\w.-]*)\}/g)].map((m) => m[1]);
  return [...new Set(matches)].sort();
}

/** @returns {{mode: 'plain'|'plural'|'select'|'unknown', branches?: string[], field?: string}} */
function messageShape(value) {
  if (typeof value === 'string') return { mode: 'plain' };
  if (!isPlainRecord(value)) return { mode: 'unknown' };

  const hasPlural = Object.hasOwn(value, 'plural');
  const hasSelect = Object.hasOwn(value, 'select');
  if (hasPlural === hasSelect) return { mode: 'unknown' };

  const mode = hasPlural ? 'plural' : 'select';
  const field = value[mode];
  if (typeof field !== 'string' || field.trim() === '') return { mode: 'unknown' };

  const branches = Object.keys(value).filter((key) => key !== mode).sort();
  if (branches.length === 0 || !Object.hasOwn(value, 'other')) return { mode: 'unknown' };
  if (branches.some((key) => typeof value[key] !== 'string' || value[key].trim() === '')) {
    return { mode: 'unknown' };
  }

  return { mode, field, branches };
}

function messageIsEmpty(value) {
  if (typeof value === 'string') return value.trim() === '';
  if (value && typeof value === 'object') {
    return Object.entries(value)
      .filter(([k]) => k !== 'plural' && k !== 'select')
      .some(([, v]) => typeof v === 'string' && v.trim() === '');
  }
  return false;
}

function messageContainsHtml(value) {
  const hasHtml = (text) => /<\s*(?:[A-Za-z\/!?])/.test(text);
  if (typeof value === 'string') return hasHtml(value);
  if (isPlainRecord(value)) {
    return Object.entries(value)
      .filter(([key]) => key !== 'plural' && key !== 'select')
      .some(([, branch]) => typeof branch === 'string' && hasHtml(branch));
  }
  return false;
}

function messagePlaceholders(value) {
  if (typeof value === 'string') return placeholdersIn(value);
  if (value && typeof value === 'object') {
    const all = Object.entries(value)
      .filter(([k]) => k !== 'plural' && k !== 'select')
      .flatMap(([, v]) => (typeof v === 'string' ? placeholdersIn(v) : []));
    return [...new Set(all)].sort();
  }
  return [];
}

function integerPluralSamples() {
  const samples = Array.from({ length: 201 }, (_, count) => count);
  for (let exponent = 3; exponent <= 15; exponent += 1) samples.push(10 ** exponent);
  return samples;
}

function integerPluralBranches(locale) {
  const rules = new Intl.PluralRules(locale);
  const branches = new Set(['other']);
  for (const count of integerPluralSamples()) branches.add(rules.select(count));
  return [...branches].sort();
}

function isPlainRecord(value) {
  if (value === null || typeof value !== 'object' || Array.isArray(value)) return false;
  const prototype = Object.getPrototypeOf(value);
  return prototype === Object.prototype || prototype === null;
}

/**
 * Compare a base and target catalog (flat key -> Message maps).
 * Pure function, easy to exercise directly if this script is imported.
 */
export function compareCatalogs(base, target, targetLocale = 'sk', targetPluralBranches = null) {
  const baseCatalog = isPlainRecord(base) ? base : {};
  const targetCatalog = isPlainRecord(target) ? target : {};
  const baseKeys = Object.keys(baseCatalog);
  const targetKeys = Object.keys(targetCatalog);

  const missing = baseKeys.filter((k) => !Object.hasOwn(targetCatalog, k));
  const extra = targetKeys.filter((k) => !Object.hasOwn(baseCatalog, k));

  const empty = targetKeys.filter((k) => messageIsEmpty(targetCatalog[k]));
  const baseValueDefects = Object.fromEntries(
    baseKeys
      .filter((key) => messageShape(baseCatalog[key]).mode === 'unknown' || messageIsEmpty(baseCatalog[key]))
      .map((key) => [key, 'canonical value must be a valid non-empty message'])
  );
  const baseHtmlDefects = Object.fromEntries(
    baseKeys
      .filter((key) => messageContainsHtml(baseCatalog[key]))
      .map((key) => [key, 'canonical values must not contain HTML or tag-like markup'])
  );
  const valueDefects = Object.fromEntries(
    targetKeys
      .filter((key) => messageShape(targetCatalog[key]).mode === 'unknown')
      .map((key) => [key, 'translation value must be a valid message'])
  );
  const htmlDefects = Object.fromEntries(
    targetKeys
      .filter((key) => messageContainsHtml(targetCatalog[key]))
      .map((key) => [key, 'translation values must not contain HTML tags'])
  );

  const placeholderMismatches = {};
  const shapeDefects = {};
  const defectiveKeys = new Set([
    ...Object.keys(baseValueDefects),
    ...Object.keys(baseHtmlDefects),
    ...Object.keys(valueDefects),
    ...Object.keys(htmlDefects),
  ].filter((key) => Object.hasOwn(baseCatalog, key)));
  if (!isPlainRecord(base)) shapeDefects['<base-catalog>'] = 'base catalog must be a plain object';
  else if (baseKeys.length === 0) shapeDefects['<base-catalog>'] = 'base catalog must be a non-empty plain object';
  if (!isPlainRecord(target)) shapeDefects['<catalog>'] = 'target catalog must be a plain object';
  for (const key of baseKeys) {
    const baseShape = messageShape(baseCatalog[key]);
    if (baseShape.mode === 'unknown') {
      shapeDefects[`<base:${key}>`] = 'base value must be a valid message';
      defectiveKeys.add(key);
      continue;
    }
    if (baseShape.mode === 'plural') {
      const expectedBaseBranches = integerPluralBranches('en');
      const actualBaseBranches = baseShape.branches ?? [];
      const missingBaseBranches = expectedBaseBranches.filter(
        (branch) => !actualBaseBranches.includes(branch)
      );
      if (missingBaseBranches.length > 0) {
        shapeDefects[`<base:${key}>`] = `canonical plural is missing required branches [${missingBaseBranches.join(', ')}]`;
        defectiveKeys.add(key);
      }
    }
  }

  for (const key of baseKeys) {
    if (!Object.hasOwn(targetCatalog, key)) continue;

    const baseShape = messageShape(baseCatalog[key]);
    const targetShape = messageShape(targetCatalog[key]);

    if (baseShape.mode !== targetShape.mode) {
      shapeDefects[key] = `base is "${baseShape.mode}" but target is "${targetShape.mode}"`;
      defectiveKeys.add(key);
      continue;
    }

    if (baseShape.mode === 'plural' || baseShape.mode === 'select') {
      if (baseShape.field !== targetShape.field) {
        shapeDefects[key] = `${baseShape.mode} field mismatch: expected "${baseShape.field}", got "${targetShape.field}"`;
        defectiveKeys.add(key);
        continue;
      }
      const targetBranches = targetShape.branches ?? [];
      const expectedBranches = baseShape.mode === 'plural'
        ? [...(targetPluralBranches ?? integerPluralBranches(targetLocale))].sort()
        : (baseShape.branches ?? []);
      if (expectedBranches.join(',') !== targetBranches.join(',')) {
        shapeDefects[key] = `branch set mismatch for locale "${targetLocale}": expected [${expectedBranches.join(', ')}], got [${targetBranches.join(', ')}]`;
        defectiveKeys.add(key);
        continue;
      }

      const baseMessage = baseCatalog[key];
      const targetMessage = targetCatalog[key];
      if (baseShape.mode === 'plural') {
        const baseRules = new Intl.PluralRules('en');
        const targetRules = new Intl.PluralRules(targetLocale);
        for (const count of integerPluralSamples()) {
          const baseBranchName = baseRules.select(count);
          const targetBranchName = targetRules.select(count);
          const baseBranch = Object.hasOwn(baseMessage, baseBranchName)
            ? baseMessage[baseBranchName]
            : baseMessage.other;
          const expected = placeholdersIn(baseBranch);
          const actual = placeholdersIn(targetMessage[targetBranchName]);
          if (expected.join(',') !== actual.join(',')) {
            const mismatchKey = `${key}.${targetBranchName}`;
            if (!Object.hasOwn(placeholderMismatches, mismatchKey)) {
              placeholderMismatches[mismatchKey] = { expected, actual };
            }
            defectiveKeys.add(key);
          }
        }
      } else {
        for (const branch of targetBranches) {
          const baseBranch = Object.hasOwn(baseMessage, branch) ? baseMessage[branch] : baseMessage.other;
          const expected = placeholdersIn(baseBranch);
          const actual = placeholdersIn(targetMessage[branch]);
          if (expected.join(',') !== actual.join(',')) {
            placeholderMismatches[`${key}.${branch}`] = { expected, actual };
            defectiveKeys.add(key);
          }
        }
      }
      continue;
    }

    const expected = messagePlaceholders(baseCatalog[key]);
    const actual = messagePlaceholders(targetCatalog[key]);
    if (expected.join(',') !== actual.join(',')) {
      placeholderMismatches[key] = { expected, actual };
      defectiveKeys.add(key);
    }
  }

  const total = baseKeys.length;
  const invalidBaseKeys = new Set([
    ...empty.filter((key) => Object.hasOwn(baseCatalog, key)),
    ...defectiveKeys,
  ]);
  const untranslatedKeys = new Set([...missing, ...invalidBaseKeys]);
  const translated = Math.max(0, total - untranslatedKeys.size);
  const coveragePercent = total > 0
    ? Math.round((translated / total) * 10000) / 100
    : 0;

  const structurallyValid =
    extra.length === 0 &&
    empty.length === 0 &&
    Object.keys(baseValueDefects).length === 0 &&
    Object.keys(baseHtmlDefects).length === 0 &&
    Object.keys(valueDefects).length === 0 &&
    Object.keys(htmlDefects).length === 0 &&
    Object.keys(placeholderMismatches).length === 0 &&
    Object.keys(shapeDefects).length === 0;
  const complete = missing.length === 0 && structurallyValid;

  return {
    totalKeys: total, translatedKeys: translated, missing, extra, empty,
    baseValueDefects, baseHtmlDefects, valueDefects, htmlDefects, placeholderMismatches, shapeDefects,
    coveragePercent, structurallyValid, complete, ok: complete,
  };
}

export function evaluateCatalogPolicy(result, allowPartial = false) {
  const defectCount =
    result.extra.length + result.empty.length + Object.keys(result.baseValueDefects).length +
    Object.keys(result.baseHtmlDefects).length + Object.keys(result.valueDefects).length +
    Object.keys(result.htmlDefects).length + Object.keys(result.placeholderMismatches).length + Object.keys(result.shapeDefects).length;
  const passes = result.structurallyValid && (allowPartial || result.complete);
  const status = result.complete ? 'OK' : (allowPartial && result.structurallyValid ? 'PARTIAL' : 'FAIL');
  return { passes, status, defectCount };
}

export function checkRegisteredCatalogs(registry, catalogs, baseLocale = 'en') {
  if (baseLocale !== 'en') {
    throw new TypeError('only canonical base locale "en" is supported');
  }
  if (!isPlainRecord(registry) || !isPlainRecord(registry.supported)) {
    throw new TypeError('locale registry must contain a supported object');
  }
  if (!Object.hasOwn(registry.supported, baseLocale)) {
    throw new TypeError('locale registry must contain canonical locale "en"');
  }

  const reports = {};
  const base = isPlainRecord(catalogs) && Object.hasOwn(catalogs, baseLocale)
    ? catalogs[baseLocale]
    : undefined;
  if (!isPlainRecord(base) || Object.keys(base).length === 0) {
    throw new TypeError('canonical locale "en" catalog must be a non-empty plain object');
  }
  for (const [locale, metadata] of Object.entries(registry.supported)) {
    if (!isPlainRecord(metadata) || !Array.isArray(metadata.web_plural_categories)) {
      throw new TypeError(`locale "${locale}" must declare web_plural_categories`);
    }
    const declaredCategories = [...metadata.web_plural_categories].sort();
    const runtimeCategories = integerPluralBranches(locale);
    if (declaredCategories.join(',') !== runtimeCategories.join(',')) {
      throw new TypeError(`locale "${locale}" web_plural_categories must match integer Intl.PluralRules categories [${runtimeCategories.join(', ')}]`);
    }
    if (locale === baseLocale) continue;
    const allowPartial = metadata.community === true;
    const result = compareCatalogs(base, catalogs?.[locale], locale, metadata.web_plural_categories);
    reports[locale] = {
      result,
      allowPartial,
      policy: evaluateCatalogPolicy(result, allowPartial),
    };
  }

  return reports;
}

function loadCatalog(path) {
  return JSON.parse(readFileSync(path, 'utf8'));
}

function main() {
  const opts = parseArgs(process.argv.slice(2));
  const baseDir = resolve(repoRoot, opts.path);

  if (opts.base !== 'en') {
    console.error('Only the canonical base locale "en" is supported.');
    process.exit(1);
  }

  if (opts.all) {
    let registry;
    try {
      registry = loadCatalog(resolve(repoRoot, 'config/locales.json'));
    } catch (err) {
      console.error(`Locale registry not found or invalid: ${err.message}`);
      process.exit(1);
    }

    const catalogs = {};
    for (const locale of Object.keys(registry.supported ?? {})) {
      const file = resolve(baseDir, `${locale}.json`);
      try {
        catalogs[locale] = loadCatalog(file);
      } catch (err) {
        console.error(`Catalog not found or invalid: ${file}\n${err.message}`);
        catalogs[locale] = null;
      }
    }

    let reports;
    try {
      reports = checkRegisteredCatalogs(registry, catalogs, opts.base);
    } catch (err) {
      console.error(`Registered catalog check could not start: ${err.message}`);
      process.exit(1);
    }

    console.log('Catalog                | Keys | Missing | Defects | Coverage | Status');
    console.log('------------------------|------|---------|---------|----------|-------');
    const failed = [];
    for (const [locale, report] of Object.entries(reports)) {
      const { result, policy } = report;
      const catalogName = `webclient/${locale}.json`;
      console.log(
        `${catalogName.padEnd(24)}| ${String(result.totalKeys).padEnd(5)}| ${String(result.missing.length).padEnd(8)}| ${String(policy.defectCount).padEnd(8)}| ${`${result.coveragePercent.toFixed(2)}%`.padEnd(9)}| ${policy.status}`
      );
      if (!policy.passes) failed.push(locale);
    }

    if (failed.length > 0) {
      console.error(`\nRegistered webclient locale check FAILED for: ${failed.join(', ')}`);
      process.exit(1);
    }

    console.log('\nAll registered webclient catalogs PASSED.');
    process.exit(0);
  }

  const baseFile = resolve(baseDir, `${opts.base}.json`);
  const targetFile = resolve(baseDir, `${opts.target}.json`);

  let base;
  let target;
  try {
    base = loadCatalog(baseFile);
  } catch (err) {
    console.error(`Base catalog not found or invalid: ${baseFile}\n${err.message}`);
    process.exit(1);
  }
  try {
    target = loadCatalog(targetFile);
  } catch (err) {
    console.error(`Target catalog not found or invalid: ${targetFile}\n${err.message}`);
    process.exit(1);
  }

  const result = compareCatalogs(base, target, opts.target);
  const policy = evaluateCatalogPolicy(result, opts.allowPartial);

  if (!policy.passes) {
    console.log(`Defects in [webclient ${opts.base} -> ${opts.target}]:`);
    if (!opts.allowPartial) {
      for (const key of result.missing) console.log(`  MISSING     ${key}`);
    }
    for (const key of result.extra) console.log(`  EXTRA       ${key}`);
    for (const key of result.empty) console.log(`  EMPTY       ${key}`);
    for (const [key, reason] of Object.entries(result.baseValueDefects)) {
      console.log(`  BASE VALUE  ${key} — ${reason}`);
    }
    for (const [key, reason] of Object.entries(result.baseHtmlDefects)) {
      console.log(`  BASE HTML   ${key} — ${reason}`);
    }
    for (const [key, reason] of Object.entries(result.valueDefects)) {
      console.log(`  VALUE       ${key} — ${reason}`);
    }
    for (const [key, reason] of Object.entries(result.htmlDefects)) {
      console.log(`  HTML        ${key} — ${reason}`);
    }
    for (const [key, diff] of Object.entries(result.placeholderMismatches)) {
      console.log(`  PLACEHOLDER ${key} — expected [${diff.expected.join(', ') || '(none)'}], got [${diff.actual.join(', ') || '(none)'}]`);
    }
    for (const [key, reason] of Object.entries(result.shapeDefects)) {
      console.log(`  SHAPE       ${key} — ${reason}`);
    }
    console.log('');
  }

  const catalogName = `webclient/${opts.target}.json`;
  console.log('Catalog                | Keys | Missing | Defects | Coverage | Status');
  console.log('------------------------|------|---------|---------|----------|-------');
  console.log(
    `${catalogName.padEnd(24)}| ${String(result.totalKeys).padEnd(5)}| ${String(result.missing.length).padEnd(8)}| ${String(policy.defectCount).padEnd(8)}| ${`${result.coveragePercent.toFixed(2)}%`.padEnd(9)}| ${policy.status}`
  );

  if (!policy.passes) {
    console.error(`\nlocale check FAILED for webclient target locale '${opts.target}'.`);
    process.exit(1);
  }

  if (opts.allowPartial && !result.complete) {
    console.log(`\nlocale check PASSED for partial webclient locale '${opts.target}': missing keys use '${opts.base}' fallback.`);
  } else {
    console.log(`\nlocale check PASSED for webclient target locale '${opts.target}': fully covered.`);
  }
  process.exit(0);
}

// Only run as a CLI when executed directly (not when imported by tests).
if (import.meta.url === `file://${process.argv[1]}`) {
  main();
}
