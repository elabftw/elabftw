/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2026 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */
import i18next from './i18n';
import { ApiC } from './api';

/** One row of GET storage_units?hierarchy=true */
export interface StorageUnitRow {
  id: number;
  name: string;
  parent_id: number | null;
  capacity: number | null;
  occupancy: number;
  children_count: number;
}

const ROOT_KEY = 'root';

/** Keep the server order (by name) within each parent, as the twig tree does. */
function groupByParent(rows: StorageUnitRow[]): Map<string, StorageUnitRow[]> {
  const grouped = new Map<string, StorageUnitRow[]>();
  rows.forEach(row => {
    const key = row.parent_id === null ? ROOT_KEY : String(row.parent_id);
    if (!grouped.has(key)) {
      grouped.set(key, []);
    }
    grouped.get(key).push(row);
  });
  return grouped;
}

const isFull = (unit: StorageUnitRow): boolean =>
  unit.capacity !== null && unit.occupancy >= unit.capacity;

function el<K extends keyof HTMLElementTagNameMap>(tag: K, className = '', text = ''): HTMLElementTagNameMap[K] {
  const node = document.createElement(tag);
  if (className) {
    node.className = className;
  }
  if (text) {
    node.textContent = text;
  }
  return node;
}

const icon = (classes: string): HTMLElement => el('i', `fas ${classes}`);

// names land in attributes through setAttribute, so html escaping would only show up as entities
const tWithName = (key: string, name: string): string =>
  i18next.t(key, { name: name, interpolation: { escapeValue: false } });

/** How full this location is, against its limit when it declares one. */
function renderOccupancy(unit: StorageUnitRow): HTMLElement | null {
  if (unit.capacity === null && unit.occupancy === 0) {
    return null;
  }
  const badge = el('span', 'storage-occupancy ml-1');
  if (unit.capacity !== null) {
    badge.classList.toggle('storage-full', isFull(unit));
    badge.title = i18next.t('storage-occupancy-capacity-title');
    badge.append(icon('fa-box mr-1'), `${unit.occupancy} / ${unit.capacity}`);
  } else {
    badge.title = i18next.t('storage-occupancy-title');
    badge.append(icon('fa-box mr-1'), String(unit.occupancy));
  }
  return badge;
}

function stepperButton(action: string, iconClass: string, labelKey: string, unit: StorageUnitRow): HTMLButtonElement {
  const btn = el('button', 'btn btn-secondary');
  btn.type = 'button';
  btn.dataset.action = action;
  btn.dataset.storageId = String(unit.id);
  btn.setAttribute('aria-label', tWithName(labelKey, unit.name));
  btn.append(icon(iconClass));
  return btn;
}

/** The -/number/+ stepper used to pick how many containers go in this location. */
function renderStepper(unit: StorageUnitRow): HTMLElement[] {
  if (isFull(unit)) {
    // no input at all: anything typed here would be refused on submit
    const full = el('span', 'smallgray');
    full.append(icon('fa-ban mr-1'), i18next.t(unit.capacity === 0 ? 'storage-holds-no-containers' : 'storage-full'));
    return [full];
  }
  const group = el('div', 'input-group input-group-sm mt-1');
  group.style.maxWidth = '160px';
  group.dataset.storageId = String(unit.id);

  const prepend = el('div', 'input-group-prepend');
  prepend.append(stepperButton('container-qty-minus', 'fa-minus', 'storage-qty-minus', unit));

  const input = el('input', 'form-control text-center');
  input.type = 'number';
  input.min = '0';
  input.step = '1';
  input.value = '0';
  input.inputMode = 'numeric';
  input.autocomplete = 'off';
  input.dataset.action = 'container-qty-input';
  input.dataset.storageId = String(unit.id);
  input.setAttribute('aria-label', tWithName('storage-qty-input', unit.name));
  // max is the room left here, so the browser's own spinner and the js clamp alike. When the
  // same distribution is applied to several entries at once, max is narrowed to what one entry
  // may claim; data-slots-left keeps the unnarrowed count to compute that from
  if (unit.capacity !== null) {
    const slotsLeft = String(unit.capacity - unit.occupancy);
    input.max = slotsLeft;
    input.dataset.slotsLeft = slotsLeft;
  }

  const append = el('div', 'input-group-append');
  append.append(stepperButton('container-qty-plus', 'fa-plus', 'storage-qty-plus', unit));
  group.append(prepend, input, append);

  // revealed when the room left cannot be split across every selected entry:
  // the occupancy badge still shows free slots, so the dead stepper needs a reason
  const notice = el('span', 'smallgray');
  notice.hidden = true;
  notice.dataset.batchFullNotice = '1';
  notice.dataset.storageId = String(unit.id);
  notice.append(icon('fa-ban mr-1'), i18next.t('storage-batch-full-notice'));

  return [group, notice];
}

function renderItems(units: StorageUnitRow[], grouped: Map<string, StorageUnitRow[]>): HTMLUListElement {
  const list = el('ul', 'list-group');
  units.forEach(unit => {
    const li = el('li', 'list-group-item py-1');
    // this data-id is used to open it up, see revealStorageUnit
    const details = el('details');
    details.dataset.id = String(unit.id);
    const summary = el('summary', '', unit.children_count > 0 ? `${unit.name} (${unit.children_count}) ` : `${unit.name} `);
    const occupancy = renderOccupancy(unit);
    if (occupancy) {
      summary.append(occupancy);
    }
    const actions = el('div', 'mb-1');
    actions.append(...renderStepper(unit));
    details.append(summary, actions);
    const children = grouped.get(String(unit.id));
    if (children) {
      details.append(renderItems(children, grouped));
    }
    li.append(details);
    list.append(li);
  });
  return list;
}

function renderRoot(root: StorageUnitRow, grouped: Map<string, StorageUnitRow[]>): HTMLElement {
  const box = el('div', 'box');
  box.dataset.rootId = String(root.id);
  const line = el('div', 'd-flex');
  const nameDiv = el('div');
  nameDiv.append(icon('fa-location-dot mr-2'), el('span', 'p-1 rounded font-weight-bold', root.name));
  const occupancy = renderOccupancy(root);
  if (occupancy) {
    nameDiv.append(occupancy);
  }
  const stepperDiv = el('div', 'ml-auto');
  stepperDiv.append(...renderStepper(root));
  line.append(nameDiv, stepperDiv);
  box.append(el('p', 'smallgray', i18next.t('storage-inventory-location')), line, el('hr'));
  const children = grouped.get(String(root.id));
  if (children) {
    box.append(renderItems(children, grouped));
  }
  return box;
}

/** Fill a tree wrapper with one stepper per storage location. */
export function renderStepperTree(target: HTMLElement, rows: StorageUnitRow[]): void {
  const grouped = groupByParent(rows);
  target.replaceChildren(...(grouped.get(ROOT_KEY) ?? []).map(root => renderRoot(root, grouped)));
}

/** The whole hierarchy, with occupancy. One query, so only made when a tree is about to be shown. */
export function fetchStorageHierarchy(): Promise<StorageUnitRow[]> {
  return ApiC.getJson<StorageUnitRow[]>('storage_units?hierarchy=true');
}
