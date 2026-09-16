import { describe, expect, it } from 'vitest';
import { createI18n, normalizeLocale, type Catalog } from './i18n';

const en: Catalog = {
  'greeting': 'Hello, {name}!',
  'files.count': { plural: 'count', one: '{count} file', other: '{count} files' },
  'session.state': { select: 'state', connected: 'Connected', closed: 'Closed', other: 'Unknown' },
  'fallback.only': 'English fallback',
};
const sk: Catalog = {
  'greeting': 'Ahoj, {name}!',
  'files.count': { plural: 'count', one: '{count} súbor', few: '{count} súbory', other: '{count} súborov' },
  'session.state': { select: 'state', connected: 'Pripojené', closed: 'Odpojené', other: 'Neznámy stav' },
};

describe('webclient i18n runtime', () => {
  it('normalizes only locales present in the injected allowlist', () => {
    expect(normalizeLocale('sk-SK', ['en', 'sk'])).toBe('sk');
    expect(normalizeLocale('EN_us', ['en', 'sk'])).toBe('en');
    expect(normalizeLocale('../../sk', ['en', 'sk'])).toBe('en');
    expect(normalizeLocale('de', ['en', 'sk'])).toBe('en');
  });

  it('interpolates and falls back eagerly to canonical English', () => {
    const i18n = createI18n({ locale: 'sk', catalog: sk, fallbackCatalog: en });
    expect(i18n.t('greeting', { name: '<Mária>' })).toBe('Ahoj, <Mária>!');
    expect(i18n.t('fallback.only')).toBe('English fallback');
    expect(i18n.t('missing.key')).toBe('missing.key');
  });

  it('prefers a present community key and falls back per missing key', () => {
    const i18n = createI18n({
      locale: 'es',
      catalog: { greeting: '¡Hola, {name}!' },
      fallbackCatalog: en,
    });

    expect(i18n.t('greeting', { name: 'Mária' })).toBe('¡Hola, Mária!');
    expect(i18n.t('fallback.only')).toBe('English fallback');
  });

  it('uses Intl plural categories and select branches with English branch fallback', () => {
    const i18n = createI18n({ locale: 'sk', catalog: sk, fallbackCatalog: en });
    expect(i18n.t('files.count', { count: 1 })).toBe('1 súbor');
    expect(i18n.t('files.count', { count: 3 })).toBe('3 súbory');
    expect(i18n.t('files.count', { count: 8 })).toBe('8 súborov');
    expect(i18n.t('session.state', { state: 'closed' })).toBe('Odpojené');
  });

  it('falls back to the matching English plural branch when a local branch is incomplete', () => {
    const i18n = createI18n({
      locale: 'sk',
      catalog: { partial: { plural: 'count', one: 'lokálne one', other: 'lokálne other' } },
      fallbackCatalog: { partial: { plural: 'count', one: 'English one', other: 'English other' } },
    });
    expect(i18n.t('partial', { count: 3 })).toBe('English other');
  });

  it('uses English plural rules for a missing community message', () => {
    const fallbackCatalog: Catalog = {
      count: { plural: 'count', one: '{count} item', other: '{count} items' },
    };

    expect(createI18n({ locale: 'fr', catalog: {}, fallbackCatalog }).t('count', { count: 0 })).toBe('0 items');
    expect(createI18n({ locale: 'ru', catalog: {}, fallbackCatalog }).t('count', { count: 21 })).toBe('21 items');
  });

  it('ignores inherited catalog messages, branches, and parameters', () => {
    const inheritedCatalog = Object.create({ inherited: 'polluted' }) as Catalog;
    const inheritedParams = Object.create({ name: 'polluted' }) as Record<string, string>;
    const inheritedBranch = Object.assign(Object.create({ few: 'polluted' }), {
      plural: 'count', one: 'local one', other: 'local other',
    }) as Catalog[string];
    const i18n = createI18n({
      locale: 'sk',
      catalog: Object.assign(inheritedCatalog, { greeting: 'Ahoj, {name}!', count: inheritedBranch }),
      fallbackCatalog: { count: { plural: 'count', one: 'English one', other: 'English other' } },
    });

    expect(i18n.t('inherited')).toBe('inherited');
    expect(i18n.t('greeting', inheritedParams)).toBe('Ahoj, {name}!');
    expect(i18n.t('count', { count: 3 })).toBe('English other');
  });

  it('does not treat catalog text as HTML', () => {
    const i18n = createI18n({ locale: 'en', catalog: { unsafe: '<img onerror=alert(1)>' }, fallbackCatalog: en });
    const el = { textContent: '' };
    i18n.setText(el, 'unsafe');
    expect(el.textContent).toBe('<img onerror=alert(1)>');
  });
});
