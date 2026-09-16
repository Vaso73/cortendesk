import { configureI18n } from '../i18n';
import type { SessionConfig } from '../core/contracts';

/** Workers cannot read DOM globals; initialize only from the explicit command payload. */
export function initializeWorkerI18n(config: SessionConfig): void {
  configureI18n(config.i18n);
}
