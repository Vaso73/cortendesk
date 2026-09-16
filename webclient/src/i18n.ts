export type MessageParams = Readonly<Record<string, string | number>>;
export type PlainMessage = string;
export type PluralMessage = { plural: string; zero?: string; one?: string; two?: string; few?: string; many?: string; other: string };
export type SelectMessage = { select: string; other: string; [branch: string]: string };
export type Message = PlainMessage | PluralMessage | SelectMessage;
export type Catalog = Readonly<Record<string, Message>>;

export interface I18nBootstrap {
  locale: string;
  catalog: Catalog;
  fallbackCatalog: Catalog;
}

export interface I18n {
  readonly locale: string;
  t(key: string, params?: MessageParams): string;
  setText(target: { textContent: string | null }, key: string, params?: MessageParams): void;
}

const SAFE_LOCALE = /^[a-z]{2,3}(?:[-_][a-z0-9]{2,8})*$/i;

export function normalizeLocale(requested: unknown, available: readonly string[], fallback = 'en'): string {
  const canonicalAvailable = new Map(available.map((locale) => [locale.toLowerCase().replaceAll('_', '-'), locale]));
  const fallbackLocale = canonicalAvailable.get(fallback.toLowerCase()) ?? available[0] ?? fallback;
  if (typeof requested !== 'string' || !SAFE_LOCALE.test(requested)) return fallbackLocale;
  const normalized = requested.toLowerCase().replaceAll('_', '-');
  return canonicalAvailable.get(normalized) ?? canonicalAvailable.get(normalized.split('-')[0]!) ?? fallbackLocale;
}

function interpolate(template: string, params: MessageParams): string {
  return template.replace(/\{([a-zA-Z][\w.-]*)\}/g, (token, name: string) => {
    const value = Object.hasOwn(params, name) ? params[name] : undefined;
    return value === undefined ? token : String(value);
  });
}

function selector(message: Message, locale: string, params: MessageParams): string | null {
  if (typeof message === 'string') return null;
  if (Object.hasOwn(message, 'plural')) {
    const plural = (message as PluralMessage).plural;
    const raw = Object.hasOwn(params, plural) ? params[plural] : undefined;
    const count = typeof raw === 'number' ? raw : Number(raw);
    return Number.isFinite(count) ? new Intl.PluralRules(locale).select(count) : 'other';
  }
  const select = (message as SelectMessage).select;
  const selected = Object.hasOwn(params, select) ? params[select] : undefined;
  return String(selected ?? 'other');
}

function renderMessage(local: Message | undefined, fallback: Message | undefined, locale: string, params: MessageParams): string | undefined {
  if (typeof local === 'string') return local;
  if (local !== undefined) {
    const selected = selector(local, locale, params) ?? 'other';
    const exact = Object.hasOwn(local, selected) ? local[selected as keyof typeof local] : undefined;
    if (typeof exact === 'string') return exact;
    if (fallback !== undefined && typeof fallback !== 'string') {
      const fallbackSelected = selector(fallback, 'en', params) ?? 'other';
      const fallbackExact = Object.hasOwn(fallback, fallbackSelected)
        ? fallback[fallbackSelected as keyof typeof fallback]
        : undefined;
      if (typeof fallbackExact === 'string') return fallbackExact;
    }
    return Object.hasOwn(local, 'other') ? local.other : undefined;
  }
  if (fallback === undefined) return undefined;
  if (typeof fallback === 'string') return fallback;
  const selected = selector(fallback, 'en', params) ?? 'other';
  const exact = Object.hasOwn(fallback, selected) ? fallback[selected as keyof typeof fallback] : undefined;
  return typeof exact === 'string' ? exact : (Object.hasOwn(fallback, 'other') ? fallback.other : undefined);
}

export function createI18n(input: I18nBootstrap): I18n {
  const locale = normalizeLocale(input.locale, [input.locale, 'en']);
  const t = (key: string, params: MessageParams = {}): string => {
    const local = Object.hasOwn(input.catalog, key) ? input.catalog[key] : undefined;
    const fallback = Object.hasOwn(input.fallbackCatalog, key) ? input.fallbackCatalog[key] : undefined;
    const rendered = renderMessage(local, fallback, locale, params);
    return interpolate(rendered ?? key, params);
  };
  return {
    locale,
    t,
    setText(target, key, params) { target.textContent = t(key, params); },
  };
}

const englishOnly: I18nBootstrap = { locale: 'en', catalog: {}, fallbackCatalog: {} };
let current = createI18n(englishOnly);

export function configureI18n(input: I18nBootstrap): I18n {
  current = createI18n(input);
  return current;
}

export function t(key: string, params?: MessageParams): string {
  return current.t(key, params);
}

export function setTranslatedText(target: { textContent: string | null }, key: string, params?: MessageParams): void {
  current.setText(target, key, params);
}
