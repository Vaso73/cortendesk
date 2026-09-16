import { readFileSync } from 'node:fs';
import { resolve } from 'node:path';
import { describe, expect, it } from 'vitest';
import { buildSessionConfig } from './ui/common';
import { t, type Catalog } from './i18n';
import { initializeWorkerI18n } from './worker/worker-i18n';

const project = resolve(import.meta.dirname, '../..');
const load = (locale: string): Catalog => JSON.parse(readFileSync(resolve(project, `lang/webclient/${locale}.json`), 'utf8')) as Catalog;

function shape(value: unknown): string {
  if (typeof value === 'string') return [...value.matchAll(/\{([\w.-]+)\}/g)].map((m) => m[1]).sort().join(',');
  const record = value as Record<string, unknown>;
  const mode = typeof record.plural === 'string' ? `plural:${record.plural}` : `select:${record.select}`;
  return `${mode}|${Object.keys(record).filter((key) => key !== 'plural' && key !== 'select').sort().join(',')}|` +
    Object.values(record).filter((entry): entry is string => typeof entry === 'string')
      .flatMap((entry) => [...entry.matchAll(/\{([\w.-]+)\}/g)].map((m) => m[1])).sort().join(',');
}

describe('webclient i18n catalog and bootstrap contract', () => {
  it('keeps complete EN/SK key and message-shape parity', () => {
    const en = load('en');
    const sk = load('sk');
    expect(Object.keys(sk).sort()).toEqual(Object.keys(en).sort());
    for (const key of Object.keys(en)) expect(shape(sk[key])).toBe(shape(en[key]));
  });

  it('passes the injected locale and catalogs explicitly into every session worker config', () => {
    const i18n = { locale: 'sk', catalog: { ready: 'Pripravené' }, fallbackCatalog: { ready: 'Ready' } };
    const config = buildSessionConfig({ peerId: '', serverKeyB64: 'k', wsIdUrl: 'id', wsRelayUrl: 'relay', myId: 'me', myName: 'Me', i18n }, 'peer', 'pw');
    expect(config.i18n).toEqual(i18n);
    const worker = readFileSync(resolve(project, 'webclient/src/worker/session.worker.ts'), 'utf8');
    expect(worker).toMatch(/initializeWorkerI18n\(config\)/);
  });

  it('initializes worker translations from the explicit config without DOM access', () => {
    const fallbackCatalog = load('en');
    const catalog = load('sk');
    initializeWorkerI18n({
      peerId: 'peer', serverKeyB64: 'k', wsIdUrl: 'id', wsRelayUrl: 'relay',
      password: '', myId: 'me', myName: 'Me',
      i18n: { locale: 'sk', catalog, fallbackCatalog },
    });
    expect(t('worker.idLost')).toBe('Spojenie so serverom ID sa prerušilo');
  });

  it('derives the allowlisted catalog bootstrap from the server locale registry', () => {
    const blade = readFileSync(resolve(project, 'resources/views/webclient.blade.php'), 'utf8');
    expect(blade).toContain("config('locales.supported'");
    expect(blade).toContain("lang_path('webclient/'");
    expect(blade).toContain('fallbackCatalog');
    expect(blade).not.toMatch(/\['en',\s*'sk'\]/);
  });
});
