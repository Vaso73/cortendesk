import { t } from '../i18n';
import type { UiCommand } from '../core/contracts';
import { ControlKey } from '../gen/message';

export type PrivacyModeMenuImpl = { key: string; label: string };
export type SecurityControlMenuItem = { id: string; label: string; checked: boolean };

export type SecurityControlMenuInput = {
  platform: string;
  permissions: Record<string, boolean>;
  privacyModeSupported: boolean;
  privacyModeImpls: PrivacyModeMenuImpl[];
  privacyModeOn: boolean;
  activePrivacyImplKey?: string;
  blockInputOn: boolean;
  lockAfterSessionEnd: boolean;
  viewOnly?: boolean;
};

export function buildLockScreenKeyCommand(): Extract<UiCommand, { c: 'key' }> {
  return { c: 'key', down: false, press: true, keyKind: 'control', value: ControlKey.LockScreen, modifiers: [] };
}

export function buildSecurityControlMenu(input: SecurityControlMenuInput): SecurityControlMenuItem[] {
  const platform = input.platform.toLowerCase();
  const desktop = platform.includes('windows') || platform.includes('linux') || platform.includes('mac');
  const canLockScreen = !input.viewOnly && input.permissions.Keyboard !== false;
  const lockScreenItem = { id: 'lockScreen', label: t('security.lockScreen'), checked: false };
  if (!desktop) return canLockScreen ? [lockScreenItem] : [];

  const items: SecurityControlMenuItem[] = [];
  if (input.permissions.Restart !== false) {
    items.push({ id: 'restart', label: t('security.restart'), checked: false });
  }
  if (platform.includes('windows') && input.permissions.Keyboard !== false) {
    items.push({ id: 'elevation', label: t('security.elevation'), checked: false });
  }

  if ((input.permissions.PrivacyMode !== false || input.privacyModeOn) && input.privacyModeSupported) {
    const impls = input.privacyModeImpls.length > 0
      ? input.privacyModeImpls
      : [{ key: 'privacy_mode_impl_mag', label: '' }];
    for (const impl of impls) {
      const checked = input.privacyModeOn
        && (!input.activePrivacyImplKey || input.activePrivacyImplKey === impl.key);
      items.push({
        id: `privacy:${impl.key}`,
        label: impl.label ? t('security.privacyImpl', { name: impl.label }) : t('security.privacy'),
        checked,
      });
    }
  }

  if (platform.includes('windows') && (input.permissions.BlockInput !== false || input.blockInputOn)) {
    items.push({
      id: 'blockInput',
      label: t('security.blockInput'),
      checked: input.blockInputOn,
    });
  }
  if (canLockScreen) {
    items.push(lockScreenItem);
  }
  if (input.permissions.Keyboard !== false) {
    items.push({
      id: 'lockAfterSessionEnd',
      label: t('security.lockAfter'),
      checked: input.lockAfterSessionEnd,
    });
  }
  return items;
}
