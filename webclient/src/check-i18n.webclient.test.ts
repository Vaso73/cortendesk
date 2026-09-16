import { describe, expect, it } from 'vitest';
import { checkRegisteredCatalogs, compareCatalogs, evaluateCatalogPolicy } from '../scripts/check-i18n.mjs';

describe('webclient locale coverage checker (compareCatalogs)', () => {
  it('rejects an empty canonical catalog instead of reporting 100 percent', () => {
    const result = compareCatalogs({}, {});

    expect(result.ok).toBe(false);
    expect(result.structurallyValid).toBe(false);
    expect(result.coveragePercent).toBe(0);
    expect(result.shapeDefects['<base-catalog>']).toMatch(/non-empty/);
  });

  it('rejects own extra keys even when their names exist on Object.prototype', () => {
    for (const key of ['constructor', 'toString', '__proto__']) {
      const target = JSON.parse(`{"a":"A","${key}":"extra"}`);
      const result = compareCatalogs({ a: 'A' }, target);

      expect(result.structurallyValid, key).toBe(false);
      expect(result.extra, key).toContain(key);
    }
  });

  it('requires an other branch even when base and target are equally malformed', () => {
    const malformed = { choice: { select: 'kind', yes: 'Yes' } };
    const result = compareCatalogs(malformed, malformed);

    expect(result.structurallyValid).toBe(false);
    expect(result.valueDefects.choice).toBeDefined();
    expect(result.shapeDefects['<base:choice>']).toBeDefined();
  });

  it('detects a missing key', () => {
    const result = compareCatalogs({ a: 'A', b: 'B' }, { a: 'Á' });
    expect(result.structurallyValid).toBe(true);
    expect(result.complete).toBe(false);
    expect(result.ok).toBe(false);
    expect(result.missing).toEqual(['b']);
    expect(result.coveragePercent).toBe(50);
  });

  it('accepts missing keys only under the explicit partial policy', () => {
    const result = compareCatalogs({ a: 'A', b: 'B' }, { a: 'Á' });

    expect(evaluateCatalogPolicy(result, false)).toEqual({ passes: false, status: 'FAIL', defectCount: 0 });
    expect(evaluateCatalogPolicy(result, true)).toEqual({ passes: true, status: 'PARTIAL', defectCount: 0 });
  });

  it('rejects a top-level JSON array as a malformed catalog', () => {
    const result = compareCatalogs(
      { a: 'A' },
      [] as unknown as Record<string, unknown>
    );

    expect(result.structurallyValid).toBe(false);
    expect(result.shapeDefects['<catalog>']).toMatch(/plain object/);
    expect(result.coveragePercent).toBe(0);
    expect(evaluateCatalogPolicy(result, true).passes).toBe(false);
  });

  it('reports zero coverage for a missing or malformed base catalog', () => {
    for (const base of [null, []]) {
      const result = compareCatalogs(
        base as unknown as Record<string, unknown>,
        {} as Record<string, unknown>
      );

      expect(result.structurallyValid).toBe(false);
      expect(result.coveragePercent).toBe(0);
      expect(result.shapeDefects['<base-catalog>']).toMatch(/plain object/);
    }
  });

  it('counts an invalid canonical key missing from target only once', () => {
    const result = compareCatalogs(
      { bad: 42 } as unknown as Record<string, unknown>,
      {}
    );

    expect(result.totalKeys).toBe(1);
    expect(result.translatedKeys).toBe(0);
    expect(result.coveragePercent).toBe(0);
    expect(result.structurallyValid).toBe(false);
  });

  it('rejects empty, HTML-like, and incomplete plural messages in the canonical catalog', () => {
    for (const fragment of ['', '<!-- comment -->', '<!DOCTYPE html>', '<?xml version="1.0"?>']) {
      const result = compareCatalogs({ a: fragment }, { a: 'Safe' });
      expect(result.structurallyValid, fragment).toBe(false);
      expect(result.coveragePercent, fragment).toBe(0);
    }

    const plural = compareCatalogs(
      { n: { plural: 'count', other: '{count} items' } },
      { n: { plural: 'count', one: '{count} item', other: '{count} items' } },
      'en'
    );
    expect(plural.structurallyValid).toBe(false);
    expect(plural.shapeDefects['<base:n>']).toBeDefined();
    expect(plural.coveragePercent).toBe(0);
  });

  it('rejects HTML-like declarations in target values', () => {
    for (const fragment of ['<!-- comment -->', '<!DOCTYPE html>', '<?xml version="1.0"?>']) {
      const result = compareCatalogs({ a: 'Safe' }, { a: fragment });
      expect(result.structurallyValid, fragment).toBe(false);
      expect(result.htmlDefects.a, fragment).toBeDefined();
    }
  });

  it('rejects a non-string target even when the base has the same malformed value', () => {
    const result = compareCatalogs(
      { a: 42 } as unknown as Record<string, unknown>,
      { a: 42 } as unknown as Record<string, unknown>
    );

    expect(result.structurallyValid).toBe(false);
    expect(result.valueDefects.a).toMatch(/valid message/);
    expect(result.coveragePercent).toBe(0);
  });

  it('rejects HTML in a translation value', () => {
    const result = compareCatalogs(
      { a: 'Safe text' },
      { a: '<img src=x onerror=alert(1' }
    );

    expect(result.structurallyValid).toBe(false);
    expect(result.htmlDefects.a).toMatch(/HTML/);
    expect(result.coveragePercent).toBe(0);
    expect(evaluateCatalogPolicy(result, true).passes).toBe(false);
  });

  it('detects an extra key', () => {
    const result = compareCatalogs({ a: 'A' }, { a: 'Á', extra: 'Navyše' });
    expect(result.ok).toBe(false);
    expect(result.extra).toEqual(['extra']);
  });

  it('detects an empty value', () => {
    const result = compareCatalogs({ a: 'A' }, { a: '' });
    expect(result.ok).toBe(false);
    expect(result.empty).toEqual(['a']);
  });

  it('detects a placeholder mismatch', () => {
    const result = compareCatalogs({ a: 'Hi {name}, you have {count} items' }, { a: 'Ahoj {meno}, mate {count} poloziek' });
    expect(result.ok).toBe(false);
    expect(result.placeholderMismatches.a.expected).toEqual(['count', 'name']);
    expect(result.placeholderMismatches.a.actual).toEqual(['count', 'meno']);
  });

  it('detects a missing integer plural branch for the target locale', () => {
    const base = { n: { plural: 'count', one: '{count} item', other: '{count} items' } };
    const target = { n: { plural: 'count', one: '{count} položka', other: '{count} položiek' } };
    const result = compareCatalogs(base, target, 'sk');
    expect(result.ok).toBe(false);
    expect(result.shapeDefects.n).toMatch(/expected.*few.*one.*other/);
  });

  it('includes plural categories used only by large integer values', () => {
    const base = { n: { plural: 'count', one: '{count} item', other: '{count} items' } };
    const incompleteEs = { n: { plural: 'count', one: '{count} elemento', other: '{count} elementos' } };

    const result = compareCatalogs(base, incompleteEs, 'es');

    expect(new Intl.PluralRules('es').select(1_000_000)).toBe('many');
    expect(result.structurallyValid).toBe(false);
    expect(result.shapeDefects.n).toMatch(/many/);
  });

  it('accepts locale-specific integer plural branches for Slovak and Russian', () => {
    const base = { n: { plural: 'count', one: '{count} item', other: '{count} items' } };
    const sk = { n: { plural: 'count', one: '{count} položka', few: '{count} položky', other: '{count} položiek' } };
    const ru = { n: { plural: 'count', one: '{count} файл', few: '{count} файла', many: '{count} файлов', other: '{count} файла' } };

    expect(compareCatalogs(base, sk, 'sk').shapeDefects).toEqual({});
    expect(compareCatalogs(base, ru, 'ru').shapeDefects).toEqual({});
  });

  it('rejects a non-string plural branch and excludes it from coverage', () => {
    const base = { n: { plural: 'count', one: '{count} item', other: '{count} items' } };
    const malformed = { n: { plural: 'count', one: 123, other: '{count} elementos' } };

    const result = compareCatalogs(base, malformed, 'en');

    expect(result.structurallyValid).toBe(false);
    expect(result.shapeDefects.n).toBeDefined();
    expect(result.coveragePercent).toBe(0);
    expect(evaluateCatalogPolicy(result, true).passes).toBe(false);
  });

  it('detects a placeholder missing from one plural branch', () => {
    const base = { n: { plural: 'count', one: '{count} item', other: '{count} items' } };
    const target = { n: { plural: 'count', one: 'elemento', other: '{count} elementos' } };

    const result = compareCatalogs(base, target, 'en');

    expect(result.structurallyValid).toBe(false);
    expect(result.placeholderMismatches['n.one']).toEqual({ expected: ['count'], actual: [] });
    expect(result.coveragePercent).toBe(0);
  });

  it('rejects a placeholder added from another plural branch', () => {
    const base = {
      n: { plural: 'count', one: '{count} item for {name}', other: '{count} items' }
    };
    const target = {
      n: { plural: 'count', one: '{count} položka pre {name}', other: '{count} položiek pre {name}' }
    };

    const result = compareCatalogs(base, target, 'en');

    expect(result.structurallyValid).toBe(false);
    expect(result.placeholderMismatches['n.other']).toEqual({
      expected: ['count'], actual: ['count', 'name']
    });
  });

  it('compares placeholders through the branches selected for the same runtime count', () => {
    const base = {
      n: { plural: 'count', one: '{count} item for {name}', other: '{count} items' }
    };
    const fr = {
      n: {
        plural: 'count',
        one: '{count} élément pour {name}',
        many: '{count} millions d’éléments',
        other: '{count} éléments'
      }
    };

    expect(new Intl.PluralRules('en').select(0)).toBe('other');
    expect(new Intl.PluralRules('fr').select(0)).toBe('one');
    const result = compareCatalogs(base, fr, 'fr');

    expect(result.structurallyValid).toBe(false);
    expect(result.placeholderMismatches['n.one']).toEqual({
      expected: ['count'], actual: ['count', 'name']
    });
  });

  it('detects a plural field mismatch', () => {
    const base = { n: { plural: 'count', one: 'x', other: 'y' } };
    const target = { n: { plural: 'total', one: 'x', other: 'y' } };
    const result = compareCatalogs(base, target);
    expect(result.ok).toBe(false);
    expect(result.shapeDefects.n).toMatch(/field mismatch/);
  });

  it('accepts a well-formed matching plural shape', () => {
    const base = { n: { plural: 'count', one: '{count} item', other: '{count} items' } };
    const target = { n: { plural: 'count', one: '{count} položka', few: '{count} položky', other: '{count} položiek' } };
    // one/other on base vs one/few/other on target is a real shape mismatch
    // (covered above); a matching shape is the same branch set both sides:
    const target2 = { n: { plural: 'count', one: '{count} položka', other: '{count} položiek' } };
    const result = compareCatalogs(base, target2, 'en');
    expect(result.ok).toBe(true);
  });

  it('reports 100% coverage and ok:true for a fully matching catalog', () => {
    const base = { a: 'A', b: 'B {x}' };
    const target = { a: 'Á', b: 'B {x}' };
    const result = compareCatalogs(base, target);
    expect(result.ok).toBe(true);
    expect(result.coveragePercent).toBe(100);
  });

  it('uses natural Slovak copy for the visible client shell and statuses', async () => {
    const { readFileSync } = await import('node:fs');
    const { resolve } = await import('node:path');
    const project = resolve(import.meta.dirname, '../..');
    const sk = JSON.parse(readFileSync(resolve(project, 'lang/webclient/sk.json'), 'utf8'));
    const appSource = readFileSync(resolve(project, 'webclient/src/ui/app.ts'), 'utf8');
    const filePanelSource = readFileSync(resolve(project, 'webclient/src/ui/file-panel.ts'), 'utf8');

    expect(sk['app.tagline']).toBe('Webový klient');
    expect(sk['overlay.savePasswordDevice']).toBe('Uložiť heslo na tomto zariadení');
    expect(sk['status.online']).toBe('Pripojené');
    expect(sk['status.offline']).toBe('Odpojené');
    expect(appSource).not.toContain('Web-Based Client');
    expect(appSource).not.toContain(" · offline");
    expect(filePanelSource).not.toContain('`Failed: ${');
    expect(filePanelSource).toContain("t('file.failed'");
  });

  it('rejects a registry-wide canonical base other than en', async () => {
    const { readFileSync } = await import('node:fs');
    const { resolve } = await import('node:path');
    const registry = JSON.parse(readFileSync(resolve(__dirname, '../../config/locales.json'), 'utf8'));
    const catalogs = {
      en: JSON.parse(readFileSync(resolve(__dirname, '../../lang/webclient/en.json'), 'utf8')),
      sk: JSON.parse(readFileSync(resolve(__dirname, '../../lang/webclient/sk.json'), 'utf8')),
    };

    expect(() => checkRegisteredCatalogs(registry, catalogs, 'sk')).toThrow(/canonical base locale "en"/);
  });

  it('rejects an empty registry and a registry without canonical en', () => {
    expect(() => checkRegisteredCatalogs({ supported: {} }, {}, 'en')).toThrow(/canonical locale "en"/);
    expect(() => checkRegisteredCatalogs(
      { supported: { sk: { web_plural_categories: ['few', 'one', 'other'] } } },
      { sk: { a: 'A' } },
      'en'
    )).toThrow(/canonical locale "en"/);
  });

  it('keeps registered web plural categories equal to the integer runtime', async () => {
    const { readFileSync } = await import('node:fs');
    const { resolve } = await import('node:path');
    const project = resolve(import.meta.dirname, '../..');
    const registry = JSON.parse(readFileSync(resolve(project, 'config/locales.json'), 'utf8'));
    const samples = Array.from({ length: 201 }, (_, count) => count);
    for (let exponent = 3; exponent <= 15; exponent += 1) samples.push(10 ** exponent);

    for (const [locale, metadata] of Object.entries(registry.supported) as Array<[
      string,
      { web_plural_categories: string[] }
    ]>) {
      const rules = new Intl.PluralRules(locale);
      const runtimeCategories = [...new Set(['other', ...samples.map((count) => rules.select(count))])].sort();
      expect(metadata.web_plural_categories.slice().sort(), locale).toEqual(runtimeCategories);
      expect(metadata.web_plural_categories, locale).toContain('other');
    }
  });

  it('checks every registered webclient catalog with its declared policy', async () => {
    const { readFileSync } = await import('node:fs');
    const { resolve } = await import('node:path');
    const project = resolve(import.meta.dirname, '../..');
    const registry = JSON.parse(readFileSync(resolve(project, 'config/locales.json'), 'utf8'));
    const locales = Object.keys(registry.supported);
    const catalogs = Object.fromEntries(locales.map((locale) => [
      locale,
      JSON.parse(readFileSync(resolve(project, `lang/webclient/${locale}.json`), 'utf8')),
    ]));

    const reports = checkRegisteredCatalogs(registry, catalogs, 'en');

    expect(Object.keys(reports)).toEqual(locales.filter((locale) => locale !== 'en'));
    for (const [locale, report] of Object.entries(reports)) {
      expect(report.policy.passes, `${locale}: ${JSON.stringify(report.result)}`).toBe(true);
      expect(report.allowPartial).toBe(registry.supported[locale].community === true);
    }
  });

  it('checks every registered catalog through the --all CLI gate', async () => {
    const { execFileSync } = await import('node:child_process');
    const { readFileSync } = await import('node:fs');
    const { resolve } = await import('node:path');
    const project = resolve(import.meta.dirname, '../..');
    const registry = JSON.parse(readFileSync(resolve(project, 'config/locales.json'), 'utf8'));
    const output = execFileSync(
      process.execPath,
      [resolve(project, 'webclient/scripts/check-i18n.mjs'), '--all'],
      { cwd: project, encoding: 'utf8' }
    );

    for (const locale of Object.keys(registry.supported).filter((locale) => locale !== 'en')) {
      expect(output).toContain(`webclient/${locale}.json`);
    }
    expect(output).toContain('All registered webclient catalogs PASSED.');
  });

  it('passes for the real current lang/webclient/en.json vs sk.json catalogs', async () => {
    const { readFileSync } = await import('node:fs');
    const { resolve } = await import('node:path');
    const project = resolve(import.meta.dirname, '../..');
    const en = JSON.parse(readFileSync(resolve(project, 'lang/webclient/en.json'), 'utf8'));
    const sk = JSON.parse(readFileSync(resolve(project, 'lang/webclient/sk.json'), 'utf8'));

    const result = compareCatalogs(en, sk);

    expect(result.missing).toEqual([]);
    expect(result.extra).toEqual([]);
    expect(result.empty).toEqual([]);
    expect(result.placeholderMismatches).toEqual({});
    expect(result.shapeDefects).toEqual({});
    expect(result.coveragePercent).toBe(100);
    expect(result.ok).toBe(true);
  });
});
