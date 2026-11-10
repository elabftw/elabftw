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
 * Sections of the edit page that group several editors under tabs, like the main text and chemical editor,
 * or the spreadsheet, image and JSON editors. Only one pane is shown, and the selected tab and collapsed state
 * are remembered so every entry opens on the same view.
 *
 * Markup, see edit.html:
 * - heading: data-action='toggle-tabbed-section', aria-controls=<id of the section content>, data-storage-key
 * - tablist: role='tablist', data-storage-key
 * - tab: role='tab', data-action='switch-tab-pane', data-tab=<name, unique on the page>, aria-controls=<id of the pane>
 * - optional: data-shown-with-tab=<name> on an element that only makes sense next to that tab
 *
 * Inactive panes and collapsed sections get a class instead of the hidden attribute: tinymce's sticky toolbar measures
 * its container on scroll, and docks itself with a zero width when the container is display: none.
 */
const inactiveClass = 'entity-body-pane-inactive';

function store(key: string | undefined, value: string): void {
  if (!key) {
    return;
  }
  try {
    localStorage.setItem(key, value);
  } catch {
    // storage can be unavailable (private browsing), the display still changes
  }
}

function read(key: string | undefined): string | null {
  if (!key) {
    return null;
  }
  try {
    return localStorage.getItem(key);
  } catch {
    return null;
  }
}

// what measured itself while hidden can refresh now, see edit.ts
function dispatchShown(tab: string): void {
  document.dispatchEvent(new CustomEvent<string>('tab-shown', { detail: tab }));
}

function getTab(name: string): HTMLElement | null {
  return document.querySelector<HTMLElement>(`[role="tab"][data-action="switch-tab-pane"][data-tab="${CSS.escape(name)}"]`);
}

export function setSectionCollapsed(toggle: HTMLElement, collapsed: boolean): void {
  const section = document.getElementById(toggle.getAttribute('aria-controls'));
  if (!section) {
    return;
  }
  section.classList.toggle(inactiveClass, collapsed);
  toggle.setAttribute('aria-expanded', String(!collapsed));
  toggleIcon(toggle, collapsed);
  store(toggle.dataset.storageKey, collapsed ? '1' : '0');
  if (!collapsed) {
    section.querySelectorAll<HTMLElement>('[role="tab"][aria-selected="true"]').forEach(tab => dispatchShown(tab.dataset.tab));
  }
}

// select a tab by its name, with scroll the section is also expanded and brought into view: use it after loading something in a pane
export function showTab(name: string, options: { scroll?: boolean } = {}): void {
  const menu = getTab(name)?.closest<HTMLElement>('[role="tablist"]');
  if (!menu) {
    return;
  }
  const tabs = Array.from(menu.querySelectorAll<HTMLElement>('[role="tab"]'));
  tabs.forEach(tab => {
    const isSelected = tab.dataset.tab === name;
    tab.classList.toggle('selected', isSelected);
    tab.setAttribute('aria-selected', String(isSelected));
    tab.tabIndex = isSelected ? 0 : -1;
    document.getElementById(tab.getAttribute('aria-controls'))?.classList.toggle(inactiveClass, !isSelected);
  });
  const names = tabs.map(tab => tab.dataset.tab);
  document.querySelectorAll<HTMLElement>('[data-shown-with-tab]').forEach(el => {
    if (names.includes(el.dataset.shownWithTab)) {
      el.hidden = el.dataset.shownWithTab !== name;
    }
  });
  store(menu.dataset.storageKey, name);
  dispatchShown(name);
  if (options.scroll) {
    const toggle = Array.from(document.querySelectorAll<HTMLElement>('[data-action="toggle-tabbed-section"]'))
      .find(el => document.getElementById(el.getAttribute('aria-controls'))?.contains(menu));
    if (toggle?.getAttribute('aria-expanded') === 'false') {
      setSectionCollapsed(toggle, false);
    }
    menu.scrollIntoView({ behavior: 'smooth' });
  }
}

on('switch-tab-pane', (el: HTMLElement) => {
  showTab(el.dataset.tab);
});

on('toggle-tabbed-section', (el: HTMLElement) => {
  setSectionCollapsed(el, el.getAttribute('aria-expanded') === 'true');
});

document.addEventListener('keydown', (event: KeyboardEvent) => {
  const target = event.target as HTMLElement;
  // the heading is a role=button, so it needs the keyboard activation a real button has
  if (target.matches?.('[data-action="toggle-tabbed-section"]') && (event.key === ' ' || event.key === 'Enter')) {
    event.preventDefault();
    setSectionCollapsed(target, target.getAttribute('aria-expanded') === 'true');
    return;
  }
  // arrow keys move between tabs, as expected for a tablist
  if (target.matches?.('[role="tab"][data-action="switch-tab-pane"]') && (event.key === 'ArrowLeft' || event.key === 'ArrowRight')) {
    const tabs = Array.from(target.closest('[role="tablist"]').querySelectorAll<HTMLElement>('[role="tab"]'));
    const next = tabs[(tabs.indexOf(target) + (event.key === 'ArrowRight' ? 1 : tabs.length - 1)) % tabs.length];
    showTab(next.dataset.tab);
    next.focus();
  }
});

// restore the remembered state, the server renders the defaults
document.querySelectorAll<HTMLElement>('[role="tablist"][data-storage-key]').forEach(menu => {
  const stored = read(menu.dataset.storageKey);
  if (stored && menu.contains(getTab(stored))) {
    showTab(stored);
  }
});
document.querySelectorAll<HTMLElement>('[data-action="toggle-tabbed-section"][data-storage-key]').forEach(toggle => {
  const stored = read(toggle.dataset.storageKey);
  if (stored === '1' || stored === '0') {
    setSectionCollapsed(toggle, stored === '1');
  }
});
