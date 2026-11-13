/**
 * Temporary Bootstrap 4 tooltip extension to help meet WCAG 2.2AA
 * success criterion 1.4.13: Content on Hover or Focus.
 *
 * After migrating to Bootstrap 6 and verifying tooltip accessibility,
 * remove this file and replace its builder.js entry with:
 * 'bootstrap/js/src/tooltip.js'.
 *
 * See https://getbootstrap.com/docs/6.0/components/tooltip/
 */
import $ from 'jquery';
import 'bootstrap/js/src/tooltip';


// jQuery UI also declares .tooltip(), but its options exclude Bootstrap's trigger and delay
interface BootstrapTooltipOptions {
  trigger: string;
  delay: {
    show: number;
    hide: number;
  };
}

type BootstrapTooltipCollection = JQuery<HTMLElement> & {
  tooltip(options: BootstrapTooltipOptions): JQuery<HTMLElement>;
};

// Use the newer attribute name to simplify migration to Bootstrap 6
const $tooltips = $('[data-bs-toggle="tooltip"]') as BootstrapTooltipCollection;
const namespace = '.tooltipAccessibility';
const hideDelay = 100;
let isDismissing = false;

function getTooltipElement(trigger: HTMLElement): HTMLElement | null {
  const ids = trigger.getAttribute('aria-describedby')?.split(/\s+/) ?? [];

  return ids
    .map(id => document.getElementById(id))
    .find(element => element?.matches('.tooltip')) ?? null;
}

function dismissTooltips($triggers: JQuery<HTMLElement>): void {
  isDismissing = true;
  try {
    $triggers.tooltip('hide');
  } finally {
    isDismissing = false;
  }
}

$tooltips.each((_, trigger) => {
  const $trigger = $(trigger);
  let leaveTimer: number | undefined;

  const cancelLeave = (): void => {
    window.clearTimeout(leaveTimer);
    leaveTimer = undefined;
  };

  $trigger.on(`inserted.bs.tooltip${namespace}`, () => {
    const tip = getTooltipElement(trigger);
    if (!tip) {
      return;
    }

    $(tip)
      .off(namespace)
      .on(`mouseenter${namespace}`, cancelLeave)
      .on(`mouseleave${namespace}`, () => {
        cancelLeave();
        leaveTimer = window.setTimeout(() => {
          if (!trigger.matches(':hover, :focus-within') && !tip.matches(':hover')) {
            $trigger.tooltip('hide');
          }
        }, hideDelay);
      });
  });

  $trigger.on(`hide.bs.tooltip${namespace}`, event => {
    if (!isDismissing && getTooltipElement(trigger)?.matches(':hover')) {
      event.preventDefault();
      return;
    }

    cancelLeave();
  });

  $trigger.on(`hidden.bs.tooltip${namespace}`, cancelLeave);
});

$tooltips.tooltip({
  trigger: 'hover focus',
  delay: {show: 0, hide: hideDelay},
});

// Prevent pressing Escape key from dismissing both the tooltip and its modal in one go
let recievedEscape = false;

document.addEventListener('keydown', event => {
  if (event.key !== 'Escape' || event.isComposing) {
    return;
  }
  if (event.repeat && recievedEscape) {
    event.preventDefault();
    event.stopPropagation();
    return;
  }

  const target = event.target instanceof Element ? event.target : null;
  const modal = target?.closest('.modal') ?? null;
  const $visible = $tooltips.filter((_, trigger) =>
    trigger.closest('.modal') === modal &&
    getTooltipElement(trigger)?.classList.contains('show') === true,
  );

  if ($visible.length === 0) {
    return;
  }

  if (modal) {
    recievedEscape = true;
    event.preventDefault();
    event.stopPropagation();
  }

  dismissTooltips($visible);
}, {capture: true});

document.addEventListener('keyup', event => {
  if (event.key === 'Escape') {
    recievedEscape = false;
  }
}, {capture: true});

window.addEventListener('blur', () => {
  recievedEscape = false;
});

$(document).on(`hide.bs.modal${namespace}`, event => {
  if (event.isDefaultPrevented()) {
    return;
  }

  const $modalTooltips = $tooltips.filter((_, trigger) =>
    trigger.closest('.modal') === event.target,
  );

  dismissTooltips($modalTooltips);
});