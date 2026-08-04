/**
 * @author Nicolas CARPi / Deltablot
 * @author Moustapha Camara / Deltablot
 * @copyright 2026 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 *
 * WCAG contrast ratio utilities.
 *
 * Contrast ratio is calculated according to the W3C Web Content Accessibility Guidelines (WCAG)
 * relative luminance algorithm: https://www.w3.org/WAI/WCAG21/Understanding/contrast-minimum.html
 * Formula: https://www.w3.org/TR/WCAG21/#dfn-contrast-ratio
 */
import i18next from './i18n';

export interface ContrastResult {
  ratio: number;
  level: 'AAA' | 'AA' | 'AA Large' | 'Fail';
  description: string;
  className: string;
  icon: string;
}

const luminance = (hex: string): number => {
  const rgb = hex.slice(1).match(/.{2}/g)!.map(v => {
    const c = parseInt(v, 16) / 255;
    return c <= 0.03928
      ? c / 12.92
      : ((c + 0.055) / 1.055) ** 2.4;
  });

  return (
    0.2126 * rgb[0] +
    0.7152 * rgb[1] +
    0.0722 * rgb[2]
  );
};

export const getContrastResult = (
  background: string,
  foreground: string,
): ContrastResult => {
  const l1 = luminance(foreground);
  const l2 = luminance(background);

  const ratio =
    (Math.max(l1, l2) + 0.05) /
    (Math.min(l1, l2) + 0.05);

  if (ratio >= 7) {
    return {
      ratio,
      level: 'AAA',
      description: i18next.t('excellent-accessibility'),
      className: 'text-success',
      icon: '✔',
    };
  }

  if (ratio >= 4.5) {
    return {
      ratio,
      level: 'AA',
      description: i18next.t('good-accessibility'),
      className: 'text-success',
      icon: '✔',
    };
  }

  if (ratio >= 3) {
    return {
      ratio,
      level: 'AA Large',
      description: i18next.t('large-text-only'),
      className: 'text-warning',
      icon: '⚠',
    };
  }

  return {
    ratio,
    level: 'Fail',
    description: i18next.t('insufficent-contrast'),
    className: 'text-danger',
    icon: '✖',
  };
};
