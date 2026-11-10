/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2026 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */
import { on } from './handlers';
import { toggleIcon } from './misc';

/**
 * In edit mode, the main text and the chemical editor/reactions share the same spot and only one is shown.
 * The last selected tab is remembered so every entry opens on the same view.
 */
export type BodyTab = 'main' | 'chem';

const storageKey = 'persistent_editBodyTab';
const collapsedKey = 'entityBodyDiv-isHidden';

function store(key: string, value: string): void {
  try {
    localStorage.setItem(key, value);
  } catch {
    // storage can be unavailable (private browsing), the display still changes
  }
}

function read(key: string): string | null {
  try {
    return localStorage.getItem(key);
  } catch {
    return null;
  }
}

// collapse with the inactive class rather than hidden, for the same tinymce reason as the tab panes below
export function setBodySectionCollapsed(collapsed: boolean): void {
  const toggle = document.getElementById('entityBodyToggle');
  const section = document.getElementById('entityBodyDiv');
  if (!toggle || !section) {
    return;
  }
  section.classList.toggle('entity-body-pane-inactive', collapsed);
  toggle.setAttribute('aria-expanded', String(!collapsed));
  toggleIcon(toggle, collapsed);
  store(collapsedKey, collapsed ? '1' : '0');
  if (!collapsed) {
    const selected = document.querySelector<HTMLElement>('#entityBodyTabs [aria-selected="true"]')?.dataset.tab;
    if (isBodyTab(selected)) {
      document.dispatchEvent(new CustomEvent<BodyTab>('body-tab-shown', { detail: selected }));
    }
  }
}

function isBodyTab(value: string | null): value is BodyTab {
  return value === 'main' || value === 'chem';
}

export function showBodyTab(tab: BodyTab, options: { scroll?: boolean } = {}): void {
  const menu = document.getElementById('entityBodyTabs');
  if (!menu) {
    return;
  }
  menu.querySelectorAll<HTMLButtonElement>('[role="tab"]').forEach(button => {
    const isSelected = button.dataset.tab === tab;
    button.classList.toggle('selected', isSelected);
    button.setAttribute('aria-selected', String(isSelected));
    button.tabIndex = isSelected ? 0 : -1;
    // not the hidden attribute: tinymce's sticky toolbar measures its container on scroll, and docks itself with a zero width
    // when the container is display: none. The inactive class keeps the width and only removes the pane from view.
    document.getElementById(button.getAttribute('aria-controls'))?.classList.toggle('entity-body-pane-inactive', !isSelected);
  });
  // the main text options only make sense next to the main text
  document.getElementById('mainTextEllipsis')?.toggleAttribute('hidden', tab !== 'main');
  store(storageKey, tab);
  // no tinymce import here: this module is also bundled with the chemical editor, see edit.ts
  document.dispatchEvent(new CustomEvent<BodyTab>('body-tab-shown', { detail: tab }));
  if (options.scroll) {
    // something was loaded in this tab on purpose, so it must not stay collapsed
    if (document.getElementById('entityBodyDiv')?.classList.contains('entity-body-pane-inactive')) {
      setBodySectionCollapsed(false);
    }
    menu.scrollIntoView({ behavior: 'smooth' });
  }
}

on('switch-body-tab', (el: HTMLElement) => {
  if (isBodyTab(el.dataset.tab)) {
    showBodyTab(el.dataset.tab);
  }
});

on('toggle-body-section', (el: HTMLElement) => {
  setBodySectionCollapsed(el.getAttribute('aria-expanded') === 'true');
});

// the heading is a role=button, so it needs the keyboard activation a real button has
document.getElementById('entityBodyToggle')?.addEventListener('keydown', (event: KeyboardEvent) => {
  if (event.key === ' ' || event.key === 'Enter') {
    event.preventDefault();
    setBodySectionCollapsed((event.currentTarget as HTMLElement).getAttribute('aria-expanded') === 'true');
  }
});

// arrow keys move between tabs, as expected for a tablist
document.getElementById('entityBodyTabs')?.addEventListener('keydown', (event: KeyboardEvent) => {
  if (event.key !== 'ArrowLeft' && event.key !== 'ArrowRight') {
    return;
  }
  const tabs = Array.from(document.querySelectorAll<HTMLButtonElement>('#entityBodyTabs [role="tab"]'));
  const current = tabs.findIndex(tab => tab.getAttribute('aria-selected') === 'true');
  const next = tabs[(current + (event.key === 'ArrowRight' ? 1 : tabs.length - 1)) % tabs.length];
  if (isBodyTab(next.dataset.tab)) {
    showBodyTab(next.dataset.tab);
    next.focus();
  }
});

const stored = read(storageKey);
if (isBodyTab(stored)) {
  showBodyTab(stored);
}
if (read(collapsedKey) === '1') {
  setBodySectionCollapsed(true);
}
