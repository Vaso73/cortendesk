export interface CatalogCompareResult {
  totalKeys: number;
  translatedKeys: number;
  missing: string[];
  extra: string[];
  empty: string[];
  baseValueDefects: Record<string, string>;
  baseHtmlDefects: Record<string, string>;
  valueDefects: Record<string, string>;
  htmlDefects: Record<string, string>;
  placeholderMismatches: Record<string, { expected: string[]; actual: string[] }>;
  shapeDefects: Record<string, string>;
  coveragePercent: number;
  structurallyValid: boolean;
  complete: boolean;
  ok: boolean;
}

export interface CatalogPolicyResult {
  defectCount: number;
  passes: boolean;
  status: 'OK' | 'PARTIAL' | 'FAIL';
}

export function compareCatalogs(
  base: Record<string, unknown>,
  target: Record<string, unknown>,
  targetLocale?: string,
  targetPluralBranches?: string[] | null
): CatalogCompareResult;

export function evaluateCatalogPolicy(
  result: CatalogCompareResult,
  allowPartial?: boolean
): CatalogPolicyResult;

export function checkRegisteredCatalogs(
  registry: { supported: Record<string, { community?: boolean; web_plural_categories: string[] }> },
  catalogs: Record<string, Record<string, unknown>>,
  baseLocale?: string
): Record<string, {
  result: CatalogCompareResult;
  allowPartial: boolean;
  policy: CatalogPolicyResult;
}>;
