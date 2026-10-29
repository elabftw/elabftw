/**
 * @author Nicolas CARPi / Deltablot
 * @author Moustapha Camara / Deltablot
 * @copyright 2026 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */
 
/**
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

/**
 * Accessible tooltip behaviour.
 * Supports WCAG 2.2 "Content on Hover or Focus" criterion 
 */

// ESC dissmissal
document.addEventListener('keydown', event => {
  if (event.key === 'Escape') {
    document
      .querySelectorAll<HTMLElement>('[role="tooltip"]')
      .forEach(tooltip => {
        tooltip.classList.remove('tooltip-visible');
      });
  }
});

// Show tooltip: default to the right, switch to left if no space left
function showTooltip(trigger: Element, tooltip: HTMLElement): void {
  tooltip.classList.remove('tooltip-left');
  tooltip.classList.add('tooltip-visible');
  const triggerRect = trigger.getBoundingClientRect();
  const tooltipWidth = tooltip.offsetWidth;
  const spaceRight = window.innerWidth - triggerRect.right;
  if (spaceRight < tooltipWidth + 10) {
    tooltip.classList.add('tooltip-left');
  }
}

// Hover and focus behaviour
document.querySelectorAll('.tooltip-trigger').forEach(parent => {
  const tooltip = parent.querySelector<HTMLElement>('[role="tooltip"]');
  if (!tooltip) {
    return;
  }
  parent.addEventListener('mouseenter', () => {
    showTooltip(parent, tooltip);
  });
  parent.addEventListener('mouseleave', () => {
    tooltip.classList.remove('tooltip-visible');
  });
  parent.addEventListener('focusin', () => {
    showTooltip(parent, tooltip);
  });
  parent.addEventListener('focusout', () => {
    tooltip.classList.remove('tooltip-visible');
  });
});