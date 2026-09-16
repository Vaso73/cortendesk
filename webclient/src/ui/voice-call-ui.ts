import { t } from '../i18n';
import type { SessionState } from '../core/contracts';

export type VoiceCallUiState = 'idle' | 'preparing' | 'waiting' | 'connected';

export type VoiceCallUiModel = {
  disabled: boolean;
  label: string;
  ariaLabel: string;
  status: string;
};

/**
 * Voice is independent of keyboard/mouse control, so View only is deliberately
 * absent from this model. RustDesk permits calls in view-only remote sessions.
 */
export function voiceCallUiModel(
  sessionState: SessionState,
  audioAllowed: boolean,
  callState: VoiceCallUiState,
  browserSupported = true,
): VoiceCallUiModel {
  const active = callState === 'waiting' || callState === 'connected';
  if (!browserSupported && !active) {
    return {
      disabled: true,
      label: t('chat.voiceCall'),
      ariaLabel: t('chat.startCallAria'),
      status: t('chat.needSecure'),
    };
  }
  if (callState === 'preparing') {
    return {
      disabled: true,
      label: t('chat.voiceCall'),
      ariaLabel: t('chat.startCallAria'),
      status: t('chat.requestMic'),
    };
  }
  const unavailable = sessionState !== 'streaming' || !audioAllowed;
  const status = callState === 'waiting'
    ? t('chat.waiting')
    : callState === 'connected'
      ? t('chat.connected')
      : !audioAllowed
        ? t('chat.notPermitted')
        : sessionState !== 'streaming'
          ? t('chat.requireSession')
          : t('chat.off');

  return {
    disabled: unavailable && !active,
    label: active ? t('chat.endCall') : t('chat.voiceCall'),
    ariaLabel: active ? t('chat.endCallAria') : t('chat.startCallAria'),
    status,
  };
}
