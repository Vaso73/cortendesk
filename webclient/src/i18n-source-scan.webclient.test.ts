import { readFileSync, readdirSync } from 'node:fs';
import { resolve } from 'node:path';
import { describe, expect, it } from 'vitest';
import type { Catalog } from './i18n';

const project = resolve(import.meta.dirname, '../..');
const sourceRoot = resolve(project, 'webclient/src');
const en = JSON.parse(readFileSync(resolve(project, 'lang/webclient/en.json'), 'utf8')) as Catalog;
function collectProduction(dir: string, prefix = ''): string[] {
  return readdirSync(dir, { withFileTypes: true }).flatMap((entry) => {
    const relative = prefix ? `${prefix}/${entry.name}` : entry.name;
    if (entry.isDirectory()) return entry.name === 'gen' ? [] : collectProduction(resolve(dir, entry.name), relative);
    return entry.name.endsWith('.ts') && !entry.name.includes('.test.') && !entry.name.endsWith('.d.ts') ? [relative] : [];
  });
}
const production = collectProduction(sourceRoot).sort();

const intentionalTechnicalLiterals = new Set([
  'CortenDesk', 'Corten', 'Desk', 'Ctrl', 'Alt', 'Del', 'Ctrl+Alt+Del', 'PrintScreen', 'Escape', 'Tab',
  'REC', 'FPS', 'Mbps', 'H.264', 'WebCodecs', 'Media Source', 'RustDesk', 'Chrome', 'Edge',
]);

describe('webclient i18n full source reference scan', () => {
  it('resolves every semantic production reference from canonical English', () => {
    const references = new Set<string>();
    for (const relative of production) {
      const source = readFileSync(resolve(sourceRoot, relative), 'utf8');
      for (const match of source.matchAll(/\b(?:t|h)\(\s*['"]([a-z][\w.-]+)['"]/g)) references.add(match[1]!);
    }
    const missing = [...references].filter((key) => !Object.hasOwn(en, key)).sort();
    expect(missing).toEqual([]);
    expect(references.size).toBeGreaterThan(150);
  });

  it('leaves no untranslated user-visible English in any rendering surface', () => {
    const findings: string[] = [];
    for (const relative of production) {
      const source = readFileSync(resolve(sourceRoot, relative), 'utf8');
      const patterns = [
        /(?:toast|setStatus|setOverlayError|setOverlayStatusText|promptDialog|confirmDialog)\([^\n]*?['"]([A-Z][^'"]{3,})['"]/g,
        /(?:aria-label|title|placeholder)="([A-Z][^"]{2,})"/g,
        /<(?:span|button|p|strong|small|dt|label|div)[^>]*>\s*([A-Z][A-Za-z][^<>{}\n]{2,})\s*</g,
      ];
      for (const pattern of patterns) for (const match of source.matchAll(pattern)) {
        const text = match[1]!.trim();
        if (!intentionalTechnicalLiterals.has(text)) findings.push(`${relative}: ${text}`);
      }
    }
    expect(findings).toEqual([]);
  });

  it('blocks known literal leaks and translated protocol identifiers', () => {
    const source = production
      .map((relative) => readFileSync(resolve(sourceRoot, relative), 'utf8'))
      .join('\n');
    for (const literal of [
      'Start remote audio',
      'Saved password — click to change',
      'This browser has no WebCodecs video decoder',
      'Hide hidden files',
      'Rename "${entry.name}"',
      'above, or drag files onto the remote pane',
      'Privacy mode — ${impl.label}',
      'Send failed for "${job.label}"',
      'not permitted by this device',
    ]) expect(source).not.toContain(literal);
    expect(source).not.toContain("kind === t('dock.clipboard')");
    expect(source).not.toContain("kind === t('dock.keyboard')");
  });
});
