/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */
import 'jquery-ui/ui/widgets/sortable';
import { Action, EntityType, Model, Target, FileType } from './interfaces';
import type { CheckableItem, Entity } from './interfaces';
import { DateTime } from 'luxon';
import type { MathJaxObject } from '@mathjax/src/js/components/startup.js';
import tinymce from 'tinymce/tinymce';
import { notify } from './notify';
import TableSorting from './TableSorting.class';
declare const MathJax: MathJaxObject;
import { Malle, InputType } from '@deltablot/malle';
import type { SelectOptions } from '@deltablot/malle';
import $ from 'jquery';
import i18next from './i18n';
import { ApiC } from './api';
import { getEditor } from './Editor.class';
import { storeRecoveryData } from './recovery';
import { readStorage } from './localStorage';
import TomSelect from 'tom-select/base';
import TomSelectCheckboxOptions from 'tom-select/dist/esm/plugins/checkbox_options/plugin.js';
import TomSelectClearButton from 'tom-select/dist/esm/plugins/clear_button/plugin.js';
import TomSelectDropdownHeader from 'tom-select/dist/esm/plugins/dropdown_header/plugin.js';
import TomSelectDropdownInput from 'tom-select/dist/esm/plugins/dropdown_input/plugin.js';
import TomSelectNoActiveItems from 'tom-select/dist/esm/plugins/no_active_items/plugin.js';
import TomSelectRemoveButton from 'tom-select/dist/esm/plugins/remove_button/plugin.js';
import TomSelectNoBackspaceDelete from 'tom-select/dist/esm/plugins/no_backspace_delete/plugin.js';
import { mount, unmount } from 'svelte';
import RorsSv from './components/Rors.svelte';
import RegisterForm from './components/RegisterForm.svelte';

// get html of current page reloaded via get
function fetchCurrentPage(tag = ''): Promise<Document>{
  const url = new URL(window.location.href);
  if (tag) {
    url.searchParams.delete('tags[]');
    url.searchParams.set('tags[]', tag);
  }
  const prevHref = window.location.href;
  const nextHref = url.toString();
  if (nextHref !== prevHref) {
    history.replaceState(history.state, '', nextHref);
  }
  return fetch(nextHref).then(response => {
    return response.text();
  }).then(data => {
    const parser = new DOMParser();
    return parser.parseFromString(data, 'text/html');
  });
}

export const getInput = (id: string): HTMLInputElement => {
  const element = getSafeElementById(id);

  if (!(element instanceof HTMLInputElement)) {
    throw new Error(`Input element not found: ${id}`);
  }

  return element;
};

// DISPLAY TIME RELATIVE TO NOW
// the datetime is taken from the title of the element so mouse hover will show raw datetime
export function relativeMoment(): void {
  const userPrefs = document.getElementById('user-prefs')?.dataset;
  const locale = userPrefs?.jslang ?? 'en';
  const timezone = userPrefs?.timezone ?? 'system';
  document.querySelectorAll('.relative-moment').forEach(el => {
    const span = el as HTMLElement;
    // do nothing if it's already loaded, prevent infinite loop with mutation observer
    if (span.innerText) {
      return;
    }
    span.innerText = toRelative(span.title, locale, timezone);
  });
}

export function toRelative(title: string, locale: string, timezone = document.getElementById('user-prefs')?.dataset.timezone ?? 'system'): string {
  return (
    DateTime
      .fromFormat(title, 'yyyy-MM-dd HH:mm:ss', {'locale': locale, 'zone': timezone})
      .toRelative() ?? ''
  );
}

export const SQL_DATETIME_FORMAT = 'yyyy-MM-dd HH:mm:ss';
/* eslint-disable-next-line quotes */
export const DATETIME_LOCAL_FORMAT = "yyyy-MM-dd'T'HH:mm";

// datetimes are stored without timezone, as wall-clock times of the server timezone
// 'system' (the browser timezone) is only a fallback for when the server timezone is not exposed
export function serverTimezone(): string {
  return document.getElementById('user-prefs')?.dataset.timezone || 'system';
}

// interpret a stored datetime in the server timezone and move it to the browser one
export function serverToLocal(value: string): DateTime {
  const locale = document.getElementById('user-prefs')?.dataset.jslang ?? 'en';
  return DateTime.fromFormat(value, SQL_DATETIME_FORMAT, { locale, zone: serverTimezone() }).toLocal();
}

// interpret a datetime-local input value in the browser timezone and convert it to a storable server datetime
export function localInputToServer(value: string): DateTime {
  // fromISO accepts the value with or without seconds, as browsers may send either
  return DateTime.fromISO(value).setZone(serverTimezone());
}

// DISPLAY DATETIMES IN THE BROWSER TIMEZONE
// the server renders them in its own timezone: elements carry the raw stored value so it can be converted here
export function localizeDatetimes(): void {
  document.querySelectorAll<HTMLElement>('.local-datetime[data-datetime]').forEach(el => {
    const dt = serverToLocal(el.dataset.datetime);
    if (dt.isValid) {
      el.textContent = dt.toLocaleString(DateTime.DATETIME_MED);
    }
  });
  // datetime-local inputs created by malle are prefilled with this attribute, so it must be in the browser timezone too
  document.querySelectorAll<HTMLElement>('[data-local-input-datetime]').forEach(el => {
    const dt = serverToLocal(el.dataset.localInputDatetime);
    if (dt.isValid) {
      el.dataset.maInputValue = dt.toFormat(DATETIME_LOCAL_FORMAT);
    }
  });
}

// Add a listener for all elements triggered by an event
// and POST an update request
// select will be on change, text inputs on blur
async function triggerHandler(event: Event, el: HTMLInputElement): Promise<void> {
  event.preventDefault();
  el.classList.remove('is-invalid');
  const isCheckbox = el.type === 'checkbox';
  // save the real boolean state so we can revert correctly on error
  // use the inverse of the checked state because it's already changed!
  const originalChecked = isCheckbox ? !el.checked : undefined;
  // for a checkbox element, look at the checked attribute, not the value
  let value: string | number = isCheckbox ? (el.checked ? '1' : '0') : el.value;

  const userid = document.getElementById('editUserModal')?.dataset.userid ?? el.dataset.userid;

  // CUSTOM ACTIONS not doing API calls
  // Idea: maybe have a data-dispatch with the custom event name in data-target
  if (el.dataset.customAction === 'show-all-users') {
    document.dispatchEvent(new CustomEvent('dataReload'));
    return;
  }
  // END CUSTOM ACTIONS

  if (el.dataset.transform === 'permissionsToJson') {
    value = permissionsToJson([]);
  }
  if (el.dataset.value) {
    value = el.dataset.value;
  }

  // use a run function to be able to have a single error handler
  const run = async () => {
    if (el.dataset.customAction === 'patch-user2team-is') {
      await ApiC.patch(`${Model.User}/${userid}`, {
        action: Action.PatchUser2Team,
        team: el.dataset.team,
        target: el.dataset.target,
        content: value,
      });
      // success side-effect for this path
      document.dispatchEvent(new CustomEvent('dataReload'));
      return;
    }

    const params: Record<string, unknown> = {};
    params[el.dataset.target as string] = value;

    if (el.dataset.method === 'delete') {
      await ApiC.delete(`${el.dataset.model}`);
    } else {
      await ApiC.patch(`${el.dataset.model}`, params);
    }

    // success side-effect for the generic path
    if (el.dataset.reload) {
      handleReloads(el.dataset.reload);
    }
  };

  try {
    await run();
  } catch (error) {
    // if input is a checkbox we revert the change
    if (isCheckbox && originalChecked !== undefined) {
      el.checked = originalChecked;
    }
    if (el.dataset.target === Target.Customid && error?.message === i18next.t('custom-id-in-use')) {
      el.classList.add('is-invalid');
    }
  }
}

// data-reload can be "page" for full page, "reloadEntitiesShow" for entities in show mode,
// or a comma separated list of ids of elements to reload
export async function handleReloads(reloadAttributes: string | undefined): Promise<void> {
  if (!reloadAttributes) return;

  if (reloadAttributes === 'page') {
    location.reload();
    return;
  }

  const reloadTargets = reloadAttributes.split(',');
  for (const toReload of reloadTargets) {
    if (toReload === 'reloadEntitiesShow') {
      await reloadEntitiesShow();
    } else {
      await reloadElements([toReload]);
    }
  }
}

type TomSelectOption = {
  value: string;
  text: string;
};

export type TomSelectElement = HTMLSelectElement & { tomselect?: TomSelect };

type RebuildSource =
  | { filter?: (option: HTMLOptionElement) => boolean }
  | { options: TomSelectOption[] };

export function rebuildTomSelectOptions(
  selectEl: HTMLSelectElement & { tomselect?: TomSelect },
  source: RebuildSource = {},
): void {
  const ts = selectEl.tomselect;
  if (!ts) return;

  let nextOptions: TomSelectOption[];

  if ('options' in source) {
    nextOptions = source.options;
  } else {
    const filter = source.filter;
    nextOptions = Array.from(selectEl.options)
      .filter((option) => !filter || filter(option))
      .map((option) => ({
        value: option.value,
        text: option.textContent ?? '',
      }));
  }

  ts.clearOptions();
  ts.addOptions(nextOptions);
  ts.refreshOptions(false);
}

export const resetToDefault = (select: TomSelectElement): void => {
  if (select.tomselect) {
    // Force sync with the hidden native selects (e.g., when default is "Do not apply any value")
    select.tomselect.clear(true);
    return;
  }
  select.value = '';
};

export function listenTrigger(elementId: string = ''): void {
  let elems: NodeList;
  if (elementId) {
    elems = document.getElementById(elementId).querySelectorAll('[data-trigger]');
  } else {
    elems = document.querySelectorAll('[data-trigger]');
  }
  elems.forEach((el: HTMLInputElement) => {
    // remove event first to avoid stacking them
    el.removeEventListener(el.dataset.trigger, event => { triggerHandler(event, el); });
    el.addEventListener(el.dataset.trigger, event => { triggerHandler(event, el); });
  });
}

/**
 * Loop over all the input and select elements of an element and collect their value
 * Returns an object with name => value
 * Add data-ignore='1' to elements that should not be considered
 * Add data-allow-empty='1' to elements that can be empty (e.g. orgid)
 */
export function collectForm(form: HTMLElement): object {
  const inputs = [];
  ['input', 'select', 'textarea'].forEach(inp => {
    form.querySelectorAll(inp).forEach((input: HTMLInputElement) => {
      if (input.type !== 'radio' || input.checked) {
        inputs.push(input);
      }
    });
  });

  let params = {};
  inputs.forEach(input => {
    const el = input;
    // TomSelect search inputs have no name attribute and must not be collected.
    if (!el.name) {
      return;
    }
    el.classList.remove('border-danger');
    if (el.reportValidity() === false) {
      console.error(el);
      el.classList.add('border-danger');
      el.focus();
      el.scrollIntoView({ behavior: 'smooth', block: 'center' });
      throw new Error(i18next.t('invalid-info'));
    }
    let value = el.value;
    if (el.type === 'checkbox') {
      value = el.checked ? 'on' : 'off';
    }
    if (el.dataset.ignore !== '1' && el.disabled === false && (value !== '' || el.dataset.allowEmpty === '1')) {
      params = Object.assign(params, {[el.name]: value});
    }
  });

  return params;
}

export function clearForm(form: HTMLElement): void {
  ['input', 'select', 'textarea'].forEach(inp => {
    form.querySelectorAll(inp).forEach((input: HTMLInputElement) => {
      if (input.dataset.noBlank !== '1' && input.type !== 'radio') {
        input.value = '';
        if (input.type === 'checkbox') {
          input.checked = false;
        }
      }
    });
  });
}

export function getEntityTypeFromPage(loc: Location): EntityType {
  const scriptName = loc.pathname.split('/').pop() || '';
  switch (scriptName) {
  case 'experiments.php':
    return EntityType.Experiment;
  case 'database.php':
    return EntityType.Item;
  case 'templates.php':
    return EntityType.Template;
  case 'resources-templates.php':
    return EntityType.ItemType;
  default:
    return EntityType.Other;
  }
}

// for view or edit mode, get type and id from the page to construct the entity object
// enable usage with parent Window for iframe cases (e.g., with spreadsheet editor)
export function getEntity(useParent: boolean = false): Entity {
  let entityId: number | null = null;
  // pick the right location (parent or self)
  const loc = useParent ? window.parent.location : window.location;
  const entityType: EntityType = getEntityTypeFromPage(loc);
  const params = new URLSearchParams(loc.search);
  if (params.has('id')) {
    entityId = parseInt(params.get('id')!, 10);
  }
  return {
    type: entityType,
    id: entityId,
  };
}

// Listen for malleable columns
export function makeMalleableColumnsGreatAgain() {
  new Malle({
    onEdit: (original, _, input) => {
      if (original.innerText === 'unset') {
        input.value = '';
        original.classList.remove('font-italic');
      }
      if (original.dataset.inputType === 'number') {
        // use setAttribute here because type is readonly property
        input.setAttribute('type', 'number');
      }
      return true;
    },
    cancel : i18next.t('cancel'),
    cancelClasses: ['btn', 'btn-danger', 'mt-2', 'ml-1'],
    inputClasses: ['form-control'],
    fun: (value, original) => {
      const params = {};
      params[original.dataset.target] = value;
      return ApiC.patch(`${original.dataset.endpoint}/${original.dataset.id}`, params)
        .then(res => res.json())
        .then(json => json[original.dataset.target]);
    },
    listenOn: '.malleableColumn',
    returnedValueIsTrustedHtml: false,
    submit : i18next.t('save'),
    submitClasses: ['btn', 'btn-primary', 'mt-2'],
    tooltip: i18next.t('click-to-edit'),
  }).listen();

  // MALLEABLE QTY_UNIT - we need a specific code to add the select options.
  // The unit list has a single source of truth: the PHP Units enum, which is
  // rendered into the #containerQtyUnitSelect dropdown of the add-container
  // modal. We read the options straight from there so the list is never
  // duplicated in the frontend.
  const qtyUnitSelect = document.getElementById('containerQtyUnitSelect') as HTMLSelectElement | null;
  const qtyUnitOptions: SelectOptions[] = qtyUnitSelect
    ? Array.from(qtyUnitSelect.options).map(option => ({selected: false, text: option.text, value: option.value}))
    : [];
  new Malle({
    cancel : i18next.t('cancel'),
    cancelClasses: ['btn', 'btn-danger', 'mt-2', 'ml-1'],
    inputClasses: ['form-control'],
    inputType: InputType.Select,
    selectOptions: qtyUnitOptions,
    fun: (value, original) => {
      return ApiC.patch(`${original.dataset.endpoint}/${original.dataset.id}`, {qty_unit: value})
        .then(res => res.json())
        .then(json => json['qty_unit']);
    },
    listenOn: '.malleableQtyUnit',
    returnedValueIsTrustedHtml: false,
    submit : i18next.t('save'),
    submitClasses: ['btn', 'btn-primary', 'mt-2'],
    tooltip: i18next.t('click-to-edit'),
  }).listen();

}

// SORTABLE ELEMENTS
export function makeSortableGreatAgain(): void {
  // need an axis and a table via data attribute
  $('.sortable').sortable({
    // limit to horizontal dragging
    axis : $(this).data('axis'),
    helper : 'clone',
    handle : '.sortableHandle',
    // we don't want the Create new pill to be sortable
    cancel: 'nonSortable',
    // do ajax request to update db with new order
    update: function() {
      // by default, use the id attribute (https://api.jqueryui.com/sortable/#method-toArray)
      let attribute = 'id';
      // but for extra fields, use the data-name attribute with the name of the field
      if ($(this).data('table') === 'extra_fields') {
        attribute = 'data-name';
      }
      // send the order as an array
      const params = {table: $(this).data('table'), entity: getEntity(), ordering: $(this).sortable('toArray', {attribute: attribute})};
      fetch('app/controllers/SortableAjaxController.php', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-Requested-With': 'XMLHttpRequest',
          'X-CSRF-Token': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
        },
        body: JSON.stringify(params),
      }).then(resp => resp.json()).then(json => {
        notify.response(json);
      });
    },
  });
}

export function getCheckedBoxes(): Array<CheckableItem> {
  const checkedBoxes = [];
  $('.entity input[type=checkbox]:checked').each(function() {
    checkedBoxes.push({
      id: parseInt($(this).data('id')),
      // the randomid is used to get the parent container and hide it when delete
      randomid: parseInt($(this).data('randomid')),
    });
  });
  return checkedBoxes;
}

// reload the entities in show mode
export async function reloadEntitiesShow(): Promise<void | Response> {
  window.dispatchEvent(new CustomEvent('entity-filters-changed'));
  // listen to data-trigger elements
  listenTrigger();
  // and set relative moments
  relativeMoment();
  // ask mathjax to reparse the page
  await MathJax.typesetPromise();
}

// turn whatever a catch block received into a message we can show to the user
export function errorMessage(error: unknown, fallback?: string): string {
  if (error instanceof Error) {
    return error.message;
  }
  return fallback ?? String(error);
}

export function getSafeElementById(id: string): HTMLElement {
  return document.getElementById(id)
    ?? (() => { throw new Error(`Element could not be found: '${id}'`); })();
}

// make the div holding selected items disappear when empty and vice versa.
function setSelectedItemsDivVisibility(instance) {
  instance.control.style.display = instance.items.length ? 'flex' : 'none';
}

function configTomSelect(select: HTMLSelectElement) {
  const config = {
    plugins: {
      no_backspace_delete: {},
      remove_button: {},
    },
    // display many things or users will be confused what they search is not displayed right away.
    maxOptions: 2222,
    onInitialize() { setSelectedItemsDivVisibility(this); },
    onItemAdd() {
      this.setTextboxValue('');
      setSelectedItemsDivVisibility(this);
    },
    onItemRemove() { setSelectedItemsDivVisibility(this); },
  };
  const wrapper = select.closest('.ts-wrapper');
  config['dropdownParent'] = wrapper;
  config['controlInput'] = wrapper?.querySelector('input');

  return config;
}

// fetch users and return in an id - username (email) format
async function fetchUsers(query: string) {
  const users = await ApiC.getJson(`users/?q=${encodeURIComponent(query)}`);
  return users.map((u) => ({
    value: `user:${u.userid}`,
    text: `${u.fullname} (${u.email})`,
    // the permissions chips show the name only
    fullname: u.fullname,
  }));
}

function addUsersConfig(select: HTMLSelectElement, config): void {
  // for users, we return a formatted response with id - user (email)
  if (select.id.endsWith('_select_users')) {
    config['load'] = (query: string, callback) => {
      if (!query.length) return callback();
      fetchUsers(query).then(callback).catch(() => callback());
    };
  }
}

// selects of the admin mass permissions change and the booking approvers
const PERMISSION_SELECT_IDS = '[id$="_select_teamgroups"], [id$="_select_teams"], [id$="_select_users"]';

function initTomSelect(select: HTMLSelectElement): void {
  const tsSelect = select as HTMLSelectElement & { tomselect?: TomSelect };
  if (tsSelect.tomselect) return;

  const config = configTomSelect(select);
  addUsersConfig(select, config);
  new TomSelect(select, config);
}

export function initPermissionsTomSelects(container: ParentNode = document): void {
  container.querySelectorAll<HTMLSelectElement>(PERMISSION_SELECT_IDS)
    .forEach((select) => initTomSelect(select));
}

// PERMISSIONS SEARCH: one input to add teams, user groups and users to a permission of an entry
// the values are kind:id (team:1, teamgroup:2, user:3), the kind is shown as a tag in front of the name
type PermissionKind = 'team' | 'teamgroup' | 'user';

function permissionKind(value: string): PermissionKind {
  return value.split(':')[0] as PermissionKind;
}

// the translated kinds are given by the template, see permissions-section.html
function permissionKindLabel(select: HTMLSelectElement, kind: PermissionKind): string {
  const labels: Record<PermissionKind, string | undefined> = {
    team: select.dataset.labelTeam,
    teamgroup: select.dataset.labelTeamgroup,
    user: select.dataset.labelUser,
  };
  return labels[kind] ?? kind;
}

function renderPermissionEntry(select: HTMLSelectElement, data: { [key: string]: unknown }, escape: (s: string) => string): string {
  const kind = permissionKind(String(data.value));
  return `<div><span class='badge permissions-kind permissions-kind-${kind} mr-2'>${escape(permissionKindLabel(select, kind))}</span>${escape(String(data.text))}</div>`;
}

// same markup as the chips rendered in permissions-section.html
function makePermissionChip(select: HTMLSelectElement, value: string, text: string): HTMLLIElement {
  const kind = permissionKind(value);
  const li = document.createElement('li');
  li.classList.add('permissions-chip');
  li.dataset.value = value;
  li.dataset.text = text;
  // same kind tag as in the search, see renderPermissionEntry
  const kindLabel = document.createElement('span');
  kindLabel.classList.add('badge', 'permissions-kind', `permissions-kind-${kind}`, 'mr-2');
  kindLabel.textContent = permissionKindLabel(select, kind);
  const removeBtn = document.createElement('button');
  removeBtn.type = 'button';
  removeBtn.classList.add('permissions-chip-remove');
  removeBtn.dataset.removePermission = '1';
  removeBtn.title = i18next.t('remove');
  removeBtn.setAttribute('aria-label', `${i18next.t('remove')} ${text}`);
  const removeIcon = document.createElement('i');
  removeIcon.classList.add('fas', 'fa-xmark');
  removeIcon.setAttribute('aria-hidden', 'true');
  removeBtn.append(removeIcon);
  li.append(kindLabel, document.createTextNode(text), removeBtn);
  return li;
}

type PermissionsChange = 'list' | 'base';

// the entries given access in a section of the permissions modal
function getPermissionsChips(section: HTMLElement): string[] {
  return Array.from(section.querySelectorAll<HTMLElement>('.permissions-chip[data-value]')).map(li => li.dataset.value);
}

// the request parameters for what changed in a section of the permissions modal
export function permissionsSectionParams(section: HTMLElement, changes: PermissionsChange[]): Record<string, string> {
  const params: Record<string, string> = {};
  if (changes.includes('list')) {
    params[section.dataset.param] = permissionsToJson(getPermissionsChips(section));
  }
  if (changes.includes('base')) {
    params[section.dataset.baseParam] = section.dataset.baseValue;
  }
  return params;
}

// the saves of each permissions modal, one at a time: see savePermissionsChange
const permissionsSaves = new WeakMap<HTMLElement, Promise<void>>();

// how to show again what is saved, for a modal whose content is not the one the server rendered (compounds)
const permissionsReloaders = new WeakMap<HTMLElement, () => Promise<void>>();

export function setPermissionsReloader(modal: HTMLElement, reload: () => Promise<void>): void {
  permissionsReloaders.set(modal, reload);
}

// the teams, user groups and users of a permission, as PermissionsHelper::translate() gives them
export interface PermissionsCanHuman {
  teams: Array<{ id: number; name: string }>;
  teamgroups: Array<{ id: number; name: string; team_name: string }>;
  users: Array<{ userid: number; fullname: string; email: string }>;
}

export interface PermissionsSectionValue {
  base: number;
  // shown when the base is not offered in the menu, for instance not allowed anymore by the instance
  baseLabel?: string;
  can: PermissionsCanHuman;
}

// select a base in the general access menu of a section: it does not save it
export function setPermissionsSectionBase(section: HTMLElement, base: number, fallbackLabel = ''): void {
  section.dataset.baseValue = String(base);
  let label = fallbackLabel;
  section.querySelectorAll<HTMLElement>('.dropdown-item[data-value]').forEach(item => {
    const isSelected = item.dataset.value === String(base);
    item.classList.toggle('active', isSelected);
    if (isSelected) {
      label = item.textContent.trim();
    }
  });
  const labelEl = section.querySelector('[data-base-label]');
  if (labelEl) {
    labelEl.textContent = label;
  }
}

/**
 * Show the permissions of another target in a permissions modal rendered empty (compounds): the base and the chips of each section,
 * by the name of their column. With readOnly, they are shown without the means to change them.
 */
export function loadPermissionsModal(
  modal: HTMLElement,
  options: { endpoint: string; mode: 'auto' | 'manual'; readOnly?: boolean; values: Record<string, PermissionsSectionValue> },
): void {
  modal.dataset.permissionsEndpoint = options.endpoint;
  modal.dataset.permissionsMode = options.mode;
  modal.classList.toggle('permissions-readonly', options.readOnly ?? false);
  modal.querySelectorAll<HTMLElement>('.permissions-section').forEach(section => {
    const value = options.values[section.dataset.param];
    if (!value) return;
    delete section.dataset.listChanged;
    delete section.dataset.baseChanged;
    setPermissionsSectionBase(section, value.base, value.baseLabel);
    section.querySelectorAll<HTMLButtonElement>('.dropdown-toggle').forEach(button => button.disabled = options.readOnly ?? false);
    const select = section.querySelector<TomSelectElement>('select[data-permissions-search]');
    const list = document.getElementById(select.dataset.list);
    // the previous chips can be found again in the search
    list.querySelectorAll<HTMLElement>('.permissions-chip[data-value]').forEach(li => {
      select.tomselect?.addOption({ value: li.dataset.value, text: li.dataset.text });
      li.remove();
    });
    const chips: Array<[string, string]> = [
      ...value.can.teams.map((team): [string, string] => [`team:${team.id}`, team.name]),
      ...value.can.teamgroups.map((group): [string, string] => [`teamgroup:${group.id}`, `${group.team_name} - ${group.name}`]),
      ...value.can.users.map((user): [string, string] => [`user:${user.userid}`, user.fullname]),
    ];
    chips.forEach(([chipValue, text]) => {
      select.tomselect?.removeOption(chipValue);
      list.append(makePermissionChip(select, chipValue, text));
    });
  });
}

// entity and user defaults are saved right away, for the selected entries we only remember what changed: see apply-permissions-selected
export function savePermissionsChange(section: HTMLElement, change: PermissionsChange): void {
  const modal = section.closest<HTMLElement>('[data-permissions-endpoint]');
  if (!modal || modal.classList.contains('permissions-readonly')) return;
  if (modal.dataset.permissionsMode === 'manual') {
    section.dataset[change === 'list' ? 'listChanged' : 'baseChanged'] = '1';
    return;
  }
  // Each save sends the whole list: concurrent requests could finish in any order and an older list would win.
  // The params are read when the request is sent, so a queued save always sends what the modal shows then.
  const previous = permissionsSaves.get(modal) ?? Promise.resolve();
  const save = previous.then(async () => {
    // a failed save reloaded the modal: what this section showed is gone, and so is its change
    if (!section.isConnected) return;
    try {
      await ApiC.patch(modal.dataset.permissionsEndpoint, permissionsSectionParams(section, [change]));
    } catch {
      // the api already reported the error: show the permissions as they are saved, not as they were edited
      const reload = permissionsReloaders.get(modal) ?? (() => reloadElements([modal.querySelector('.modal-body').id]));
      await reload().catch(() => {});
    }
  });
  permissionsSaves.set(modal, save);
}

// the select is only a search: what is given access is the list of chips under it
function initPermissionsSearchSelect(select: TomSelectElement): void {
  if (select.tomselect) return;
  const section = select.closest<HTMLElement>('.permissions-section');
  const list = document.getElementById(select.dataset.list);
  if (!section || !list) return;
  const wrapper = select.closest<HTMLElement>('.ts-wrapper');
  // the typings of tom-select only take a selector for the dropdown parent
  if (wrapper && !wrapper.id) {
    wrapper.id = `${select.id}-wrapper`;
  }

  const ts = new TomSelect(select, {
    dropdownParent: wrapper ? `#${wrapper.id}` : null,
    controlInput: wrapper?.querySelector<HTMLInputElement>('input'),
    // display many things or users will be confused what they search is not displayed right away.
    maxOptions: 2222,
    // teams and user groups are in the options already, users are fetched while typing
    load: (query: string, callback) => {
      if (!query.length) return callback();
      fetchUsers(query)
        .then(users => callback(users.filter(user => !getPermissionsChips(section).includes(user.value))))
        .catch(() => callback());
    },
    render: {
      option: (data, escape) => renderPermissionEntry(select, data, escape),
    },
    onItemAdd: (value: string) => {
      // users are shown by their name only, without the email
      const text = String(ts.options[value]?.fullname ?? ts.options[value]?.text ?? value);
      // nothing stays selected in the search itself, and an entry already given access is not suggested again
      ts.clear(true);
      ts.removeOption(value);
      ts.setTextboxValue('');
      ts.close();
      if (getPermissionsChips(section).includes(value)) return;
      list.append(makePermissionChip(select, value, text));
      savePermissionsChange(section, 'list');
    },
  });
  ts.control.style.display = 'none';

  list.addEventListener('click', (event: MouseEvent) => {
    const btn = (event.target as HTMLElement).closest<HTMLElement>('[data-remove-permission]');
    if (!btn) return;
    const li = btn.closest<HTMLElement>('[data-value]');
    // make it findable again
    ts.addOption({ value: li.dataset.value, text: li.dataset.text });
    li.remove();
    savePermissionsChange(section, 'list');
  });
}

export function initPermissionsSearch(container: ParentNode = document): void {
  container.querySelectorAll<HTMLSelectElement>('select[data-permissions-search="1"]')
    .forEach(select => initPermissionsSearchSelect(select));
}

function reloadTomSelects(ts: Element): void {
  if (ts instanceof HTMLSelectElement) {
    initTomSelect(ts);
  }
}

export async function reloadElements(elementIds: string[]): Promise<void> {
  elementIds = elementIds.filter((elementId: string): boolean => {
    if (!document.getElementById(elementId)) {
      console.warn(`Could not find element with id ${elementId} to reload!`);
      return false;
    }
    return true;
  });

  if (elementIds.length === 0) {
    return;
  }

  let html: Document;
  try {
    html = await fetchCurrentPage();
  } catch (error) {
    // Ignore fetch failures caused by navigating away while reloading page fragments.
    if (error instanceof TypeError && error.message.includes('NetworkError')) {
      return;
    }
    throw error;
  }
  elementIds.forEach(elementId => {
    const currentElement = document.getElementById(elementId);
    const newElement = html.getElementById(elementId);
    if (!newElement) {
      console.warn(`Could not find element with id ${elementId} anymore, removing it`);
      currentElement.remove();
      return;
    }
    currentElement.innerHTML = newElement.innerHTML;
    currentElement.querySelectorAll('[data-tom-select="1"]').forEach(ts => reloadTomSelects(ts));
    initPermissionsSearch(currentElement);
    listenTrigger(elementId);
  });
  (new TableSorting()).init();
  makeSortableGreatAgain();
  makeMalleableColumnsGreatAgain();
  relativeMoment();
  localizeDatetimes();
}

/**
 * All elements that have a save-hidden data attribute have their visibility depend on the saved state
 * in localStorage. The localStorage key is the value of the save-hidden data attribute.
 */
export function adjustHiddenState(): void {
  document.querySelectorAll('[data-save-hidden]').forEach((el: HTMLElement) => {
    const localStorageKey = el.dataset.saveHidden + '-isHidden';
    const localStorageValue = readStorage(localStorageKey);
    const button = document.querySelector(`[data-toggle-target="${el.dataset.saveHidden}"]`) || el.previousElementSibling;
    if (!button) {
      return;
    }
    if (localStorageValue === '1') {
      el.hidden = true;
      button.setAttribute('aria-expanded', 'false');
    // make sure to explicitly check for the value, because the key might not exist!
    } else if (localStorageValue === '0') {
      el.removeAttribute('hidden');
      button.setAttribute('aria-expanded', 'true');
    }
    // now make the button icon match the new state
    const isHidden = el.hasAttribute('hidden');
    toggleIcon((button as HTMLElement), isHidden);
  });
}

// the uploads are rendered again by the server: restore the collapsed state of their groups
export function reloadUploads(): Promise<void> {
  return reloadElements(['uploadsDiv']).then(() => adjustHiddenState());
}

// update category or status, returns the color as string
export async function updateCatStat(target: string, entity: Entity, value: string): Promise<string> {
  const params = {};
  params[target] = value;
  const newEntity = await ApiC.patch(`${entity.type}/${entity.id}`, params).then(resp => resp.json());
  // return a string separated with | with the id first so we can use it in data-id of new element
  if (target === 'category') {
    return `${value}|${newEntity.category_color ?? 'bdbdbd'}|${newEntity.category_color_fg ?? 'ffffff'}`;
  }
  return `${value}|${newEntity.status_color ?? 'bdbdbd'}`;
}

// used in edit.ts to build search patterns from strings that contain special characters
// taken from https://developer.mozilla.org/en-US/docs/Web/JavaScript/Guide/Regular_Expressions#escaping
export function escapeRegExp(string: string): string {
  // $& means the whole matched string
  return string.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
}

// used in metadata.ts to normalize field names by removing double quotes and normalizing whitespace
export function normalizeFieldName(input: string): string {
  return input.replace(/"/g, '').trim().replace(/\s+/g, ' ');
}

export function askFileName(extension: FileType): string | undefined {
  const realName = prompt(i18next.t('request-filename'));
  // user hits cancel: exit silently
  if (realName === null) return;
  if (realName.trim() === '') {
    throw new Error(i18next.t('error-no-filename'));
  }
  const ext = `.${extension.toLowerCase()}`;
  if (realName.toLowerCase().endsWith(ext)) {
    return realName;
  }
  return realName + ext;
}

export function permissionsToJson(extra: string[]): string {
  const json = {
    'teams': [],
    'teamgroups': [],
    'users': [],
  };

  extra.forEach(val => {
    if (val.startsWith('team:')) {
      json.teams.push(parseInt(val.split(':')[1], 10));
    }
    if (val.startsWith('teamgroup:')) {
      json.teamgroups.push(parseInt(val.split(':')[1], 10));
    }
    if (val.startsWith('user:')) {
      json.users.push(parseInt(val.split(':')[1], 10));
    }
  });

  return JSON.stringify(json);
}

// only these schemes become clickable: anything else (javascript:, data:, vbscript:...) could run code on click
const SAFE_LINK_PROTOCOLS = ['http:', 'https:', 'ftp:', 'mailto:'];

// return a normalized href if the user-provided value is a safe absolute URL, or null otherwise
export function getSafeHref(value: string): string | null {
  let parsed: URL;
  try {
    parsed = new URL(value.trim());
  } catch {
    return null;
  }
  return SAFE_LINK_PROTOCOLS.includes(parsed.protocol) ? parsed.href : null;
}

// go over all the type: url elements and create a link dynamically
export function generateMetadataLink(): void {
  document.querySelectorAll('[data-gen-link="true"]').forEach(el => {
    const text = (el as HTMLSpanElement).textContent ?? '';
    const href = getSafeHref(text);
    // leave the element as plain text if the value isn't a safe URL
    if (href === null) {
      return;
    }
    const link = document.createElement('a');
    link.classList.add('d-block');
    link.href = href;
    link.textContent = text;
    el.replaceWith(link);
  });
}

export function toggleIcon(el: HTMLElement, isHidden: boolean): void
{
  const iconEl = el.querySelector('i');
  // we assume that if element has closed-icon, it also has opened-icon
  if (!iconEl || !el.dataset.closedIcon) {
    return;
  }
  if (isHidden) {
    iconEl.classList.add(el.dataset.closedIcon);
    iconEl.classList.remove(el.dataset.openedIcon);
  } else {
    iconEl.classList.remove(el.dataset.closedIcon);
    iconEl.classList.add(el.dataset.openedIcon);
  }
}

export function escapeExtendedQuery(searchTerm: string): string {
  // the order of the replacement is important
  // 1) escape extended search query wildcards
  ['_', '%'].forEach(wildcard => {
    searchTerm = searchTerm.replaceAll(wildcard, `\\${wildcard}`);
  });

  // 2) mask special characters of extended search query by single character wildcard
  ['!', '|', '&', '(', ')', '"', '\'', ':'].forEach(specialChar => {
    searchTerm = searchTerm.replaceAll(specialChar, '_');
  });

  // 3) mask word operators 'not', 'and', 'or' of extended search query
  ['not', 'or', 'and'].forEach(word => {
    const re = new RegExp(`\\b${word}\\b`, 'g');
    searchTerm = searchTerm.replaceAll(re, ` '${word}' `);
  });

  return searchTerm.trim();
}

export function replaceWithTitle(): void {
  document.querySelectorAll('[data-replace-with-title="true"]').forEach((el: HTMLElement) => {
    // view mode is innerText
    let changedAttribute = 'innerText';
    // edit mode is value because it's an input
    if (el instanceof HTMLInputElement) {
      changedAttribute = 'value';
    }
    const notFound = (): void => {
      const message = i18next.t('resource-not-found');
      el[changedAttribute] = el instanceof HTMLInputElement ? `${el.dataset.id} - ${message}` : message;
      el.classList.add('color-warning');
    };
    // 0 is an entry that could not be found during an import, and the api would read it as "no id"
    if (el.dataset.id === '0') {
      notFound();
      return;
    }
    ApiC.getJson(`${el.dataset.endpoint}/${el.dataset.id}`, { notifOnError: 0 }).then(json => {
      // VIEW MODE (non-input): default = 'entity title'
      let value;
      const casNumber = json.cas_number ? ` - CAS: (${json.cas_number})` : '';
      const userDisplayName =
        String(json.fullname ?? '').trim() ||
        `${json.firstname ?? ''} ${json.lastname ?? ''}`.trim() ||
        String(json.email ?? '').trim() ||
        String(json.userid ?? '');
      if (!(el instanceof HTMLInputElement)) {
        switch (el.dataset.endpoint) {
        case Model.User:
          value = userDisplayName;
          break;
        case Model.Compounds:
          value = json.name + casNumber;
          break;
        default:
          value = json.title ?? json.name;
        }
      } else {
        // EDIT MODE (input): default = 'id - entity title'
        switch (el.dataset.endpoint) {
        case Model.User:
          value = `${el.dataset.id} - ${userDisplayName}`;
          break;
        case Model.Compounds:
          value = `${json.id} - ${json.name}` + casNumber;
          break;
        default:
          value = `${json.id} - ${json.title ?? json.name}`;
        }
      }
      el[changedAttribute] = value;
    }).catch(notFound);
  });
}

export function getPageName(): string {
  return (new URL(window.location.href)).pathname.split('/').pop();
}

export async function saveStringAsFile(filename: string, content: string|Promise<string>, contentType: string = 'text/plain;charset=utf-8'): Promise<void> {
  const blob = new Blob([await content], {type: contentType});
  const url = URL.createObjectURL(blob);
  // we create a link and click it
  const link = document.createElement('a');
  link.href = url;
  link.download = filename;
  document.body.appendChild(link);
  link.click();
  // cleanup by revoking the URL object
  URL.revokeObjectURL(url);
  link.remove();
}

// Shared function to UPDATE ENTITY BODY via save shortcut and/or save button, or autosave
export async function updateEntityBody(redirect = true): Promise<void> {
  const editor = getEditor();
  const entity = getEntity();

  return ApiC.patch(`${entity.type}/${entity.id}`, {body: editor.getContent(), notifOnSaved: redirect ? 0 : 1}).then(response => response.json()).then(json => {
    if (editor.type === 'tiny') {
      // set the editor as non dirty so we can navigate out without a warning to clear
      tinymce.activeEditor.setDirty(false);
    }
    if (!redirect) {
      const lastSavedAt = document.getElementById('lastSavedAt');
      if (lastSavedAt) {
        lastSavedAt.title = json.modified_at;
        reloadElements(['lastSavedAt']);
      }
    }
  }).catch(() => {
    // detect if the session timedout (Session expired error is thrown)
    // store the modifications in local storage to prevent any data loss
    storeRecoveryData(entity.type, entity.id, editor.getContent());
    // reload the page so user gets redirected to the login page
    location.reload();
  });
}

// bind used plugins to TomSelect
TomSelect.define('checkbox_options', TomSelectCheckboxOptions);
TomSelect.define('clear_button', TomSelectClearButton);
TomSelect.define('dropdown_header', TomSelectDropdownHeader);
TomSelect.define('dropdown_input', TomSelectDropdownInput);
TomSelect.define('no_active_items', TomSelectNoActiveItems);
TomSelect.define('remove_button', TomSelectRemoveButton);
TomSelect.define('no_backspace_delete', TomSelectNoBackspaceDelete);
export { TomSelect };

// toggle appearance of button
export function toggleGrayClasses(classList: DOMTokenList): void {
  ['btn-secondary', 'btn-ghost'].forEach(btnClass => classList.toggle(btnClass, !classList.contains(btnClass)));
}

export function getNewIdFromPostRequest(response: Response): number {
  const location = response.headers.get('location').split('/');
  return parseInt(location[location.length -1], 10);
}

export function sizeToMb(size: string): number {
  const units: { [key: string]: number } = {
    'B': 1 / (1024 ** 2),
    'K': 1 / 1024,
    'M': 1,
    'G': 1024,
    'T': 1024 ** 2,
    'P': 1024 ** 3,
    'E': 1024 ** 4,
  };

  const regex = /^(\d+(?:\.\d+)?)([BKMGTPE]?)$/i;
  const match = size.toString().match(regex);

  if (!match) {
    throw new Error('Invalid size format');
  }

  const value = parseFloat(match[1]);
  const unit = match[2].toUpperCase();

  if (!units[unit]) {
    throw new Error('Invalid unit');
  }

  return value * units[unit];
}

export function toggleEditCompound(json: object): void {
  const textParams = [
    'id',
    'name',
    'smiles',
    'inchi',
    'inchi_key',
    'iupac_name',
    'molecular_formula',
    'molecular_weight',
    'pubchem_cid',
    'userid_human',
    'team_name',
    'cas_number',
    'ec_number',
  ];
  textParams.forEach(param => {
    (document.getElementById(`compoundInput-${param}`) as HTMLInputElement).value = json[param];
  });

  const binaryParams = [
    'is_corrosive',
    'is_explosive',
    'is_flammable',
    'is_gas_under_pressure',
    'is_hazardous2env',
    'is_hazardous2health',
    'is_serious_health_hazard',
    'is_oxidising',
    'is_toxic',
    'is_radioactive',
    'is_controlled',
    'is_antibiotic',
    'is_antibiotic_precursor',
    'is_explosive_precursor',
    'is_drug',
    'is_drug_precursor',
    'is_cmr',
    'is_nano',
    'is_ed2health',
    'is_ed2env',
    'is_pbt',
    'is_pmt',
    'is_vpvb',
    'is_vpvm',
  ];
  binaryParams.forEach(param => {
    const input = (document.getElementById(`addCompound${param}`) as HTMLInputElement);
    input.checked = json[param] === 1;
  });
  const saveBtn = document.getElementById('editCompoundModalSaveBtn') as HTMLButtonElement;
  saveBtn.dataset.compoundId = json['id'];
  // the compound can be readable only, see Compounds::readOne()
  if ('has_write_access' in json) {
    saveBtn.disabled = json['has_write_access'] !== 1;
  }
  // don't show the molecule of a previously opened compound
  const moleculeDiv = document.getElementById('compoundMoleculeDiv');
  if (moleculeDiv) {
    moleculeDiv.querySelectorAll('img').forEach(img => {
      if (img.src.startsWith('blob:')) {
        URL.revokeObjectURL(img.src);
      }
      img.remove();
    });
    moleculeDiv.hidden = true;
  }
  (document.getElementById('compoundLink-pubchem') as HTMLLinkElement).href = `https://pubchem.ncbi.nlm.nih.gov/compound/${json['pubchem_cid']}`;
  const signalWordSelect = document.getElementById('compoundInput-ghs_signal_word') as HTMLSelectElement | null;
  if (signalWordSelect) {
    signalWordSelect.value = json['ghs_signal_word'] ?? '';
  }
  renderCompoundGhsStatements(json['ghs_statements'] ?? [], json['has_write_access'] === 1);
  $('#editCompoundModal').modal('toggle');
}

export interface GhsStatement {
  id: number;
  code: string;
  kind: 'H' | 'EUH' | 'P';
  statement: string;
  is_deprecated: number;
}

// the statements of the compound in the edit modal: hazard (H and EUH) and precautionary (P)
export function renderCompoundGhsStatements(statements: GhsStatement[], canWrite: boolean): void {
  const div = document.getElementById('compoundGhsDiv');
  if (!div) {
    return;
  }
  const hazardList = document.getElementById('compoundGhsList-hazard');
  const precautionaryList = document.getElementById('compoundGhsList-precautionary');
  hazardList.replaceChildren();
  precautionaryList.replaceChildren();
  statements.forEach(statement => {
    const li = document.createElement('li');
    li.classList.add('list-group-item', 'd-flex', 'justify-content-between', 'align-items-center');
    const text = document.createElement('span');
    const code = document.createElement('strong');
    code.textContent = statement.code;
    text.append(code, ` ${statement.statement}`);
    li.appendChild(text);
    if (canWrite) {
      const btn = document.createElement('button');
      btn.type = 'button';
      btn.classList.add('btn', 'btn-danger-ghost', 'btn-sm');
      btn.dataset.action = 'remove-compound-ghs-statement';
      btn.dataset.id = String(statement.id);
      btn.title = div.dataset.removeLabel;
      btn.setAttribute('aria-label', `${div.dataset.removeLabel} ${statement.code}`);
      const icon = document.createElement('i');
      icon.classList.add('fas', 'fa-xmark');
      btn.appendChild(icon);
      li.appendChild(btn);
    }
    (statement.kind === 'P' ? precautionaryList : hazardList).appendChild(li);
  });
  [hazardList, precautionaryList].forEach(list => {
    if (list.childElementCount === 0) {
      const li = document.createElement('li');
      li.classList.add('list-group-item', 'smallgray');
      li.textContent = div.dataset.emptyLabel;
      list.appendChild(li);
    }
  });

  const input = document.getElementById('compoundGhsInput') as HTMLInputElement;
  const addBtn = div.querySelector<HTMLButtonElement>('[data-action="add-compound-ghs-statement"]');
  input.disabled = !canWrite;
  addBtn.disabled = !canWrite;
  // Enter would submit the compound form
  if (input.dataset.enterBound !== '1') {
    input.addEventListener('keydown', (event: KeyboardEvent) => {
      if (event.key === 'Enter') {
        event.preventDefault();
        addBtn.click();
      }
    });
    input.dataset.enterBound = '1';
  }
  loadGhsStatementsDatalist();
}

// the known statements for the autocomplete: the list is small and does not change, so it is loaded once
function loadGhsStatementsDatalist(): void {
  const datalist = document.getElementById('ghsStatementsList');
  if (!datalist || datalist.dataset.loaded === '1') {
    return;
  }
  datalist.dataset.loaded = '1';
  ApiC.getJson<GhsStatement[]>(Model.GhsStatements).then(statements => {
    statements.filter(statement => statement.is_deprecated !== 1).forEach(statement => {
      const option = document.createElement('option');
      option.value = statement.code;
      option.textContent = statement.statement;
      datalist.appendChild(option);
    });
  }).catch(() => {
    // try again next time
    delete datalist.dataset.loaded;
  });
}

export function mkSpin(el: HTMLElement): string {
  // we want the button to keep the same size, so store width/height as style attribute
  const { width, height } = el.getBoundingClientRect();
  el.style.width = `${width}px`;
  el.style.height = `${height}px`;
  // keep the old html around so we can restore it
  const elOldHTML = el.innerHTML;
  el.setAttribute('disabled', 'disabled');
  el.innerHTML = '<span class="spinner"></span>';
  return elOldHTML;
}

export function mkSpinStop(el: HTMLElement, oldHTML: string): void {
  el.innerHTML = oldHTML;
  el.removeAttribute('disabled');
}

// store the active timeout for each confirmation button without preventing
// the button element from being garbage-collected when removed from DOM
const confirmationTimeouts = new WeakMap<HTMLButtonElement, number>();

export function delayConfirmation(button: HTMLButtonElement, delay = 2000): void {
  const timeoutId = confirmationTimeouts.get(button);
  if (timeoutId !== undefined) {
    window.clearTimeout(timeoutId);
  }
  button.disabled = true;
  confirmationTimeouts.set(button, window.setTimeout(() => {
    button.disabled = false;
    confirmationTimeouts.delete(button);
  }, delay));
}

export async function populateUserModal(user: Record<string, string|number>) {
  const manageTeamsDiv = document.getElementById('manageTeamsDiv');
  if (!manageTeamsDiv) {
    return;
  }
  const requester = await ApiC.getJson('users/me');
  // eslint-disable-next-line @typescript-eslint/no-explicit-any
  const userTeams = user.teams as any;
  const canRemoveFromTeam = Boolean(requester.is_sysadmin) || Boolean(requester.can_manage_users2teams);
  // set a dataset.userid on the modal, that's where all js code will fetch current user, instead of having to set it on every elementel.dataset.
  document.getElementById('editUserModal').dataset.userid = String(user.userid);
  // manage teams block
  // remove previous content
  manageTeamsDiv.innerHTML = '';
  userTeams.forEach(team => {
    const teamBadge = document.createElement('div');
    teamBadge.classList.add('user-badge', 'm-1');
    teamBadge.innerText = team.name;
    // REMOVE TEAM BUTTON
    // only Sysadmins and users who can manage users/teams association can remove a user from a team
    // users must keep one team; requester cannot remove themselves from their current team
    if (canRemoveFromTeam && userTeams.length > 1 && (team.id !== requester.team || user.userid !== requester.userid)) {
      const removeTeamBtn = document.createElement('button');
      removeTeamBtn.type = 'button';
      removeTeamBtn.classList.add('btn', 'btn-danger-ghost', 'btn-sm', 'ml-2');
      removeTeamBtn.title = i18next.t('delete');
      removeTeamBtn.setAttribute('aria-label', i18next.t('delete'));
      removeTeamBtn.dataset.action = 'open-destroy-user2team-modal';
      removeTeamBtn.dataset.teamid = String(team.id);
      removeTeamBtn.dataset.teamname = String(team.name);
      const removeTeamIcon = document.createElement('i');
      removeTeamIcon.classList.add('fas', 'fa-xmark');
      removeTeamIcon.setAttribute('aria-hidden', 'true');
      removeTeamBtn.appendChild(removeTeamIcon);
      teamBadge.appendChild(removeTeamBtn);
    }

    teamBadge.appendChild(generateIsSomethingElement('owner', team, Boolean(requester.is_sysadmin)));
    teamBadge.appendChild(generateIsSomethingElement('admin', team));
    teamBadge.appendChild(generateIsSomethingElement('archived', team));

    manageTeamsDiv.appendChild(teamBadge);
  });
  listenTrigger(manageTeamsDiv.id);
  // add team section
  // we need to generate the teams that are addable for this user
  const teams = await ApiC.getJson('teams');
  const userTeamIds = new Set(userTeams.map(t => t.id));
  const addTeamSelect = document.getElementById('addTeamSelect');
  addTeamSelect.innerHTML = '';
  const available = teams.filter((team: Record<string, string|number>) => !userTeamIds.has(team.id));
  available
    .forEach(({ id, name }) => {
      const opt = document.createElement('option');
      opt.value = id;
      opt.textContent = name;
      addTeamSelect.appendChild(opt);
    });
  // don't show it if empty
  const addTeamDiv = document.getElementById('addTeamDiv');
  addTeamDiv.hidden = available.length === 0;

  // actions
  const disable2faBtn = document.getElementById('disable2faBtn');
  if (disable2faBtn) {
    disable2faBtn.removeAttribute('disabled');
    if (user.has_mfa_enabled === 0) {
      disable2faBtn.setAttribute('disabled', 'disabled');
    }
  }
  const validateUserBtn = document.getElementById('validateUserBtn');
  validateUserBtn.removeAttribute('disabled');
  if (user.validated !== 0) {
    validateUserBtn.setAttribute('disabled', 'disabled');
  }
  // RORS
  const rorsDiv = document.getElementById('rorsDiv') as HTMLElement | null;
  if (!rorsDiv) {
    throw new Error('Missing #rorsDiv');
  }
  rorsDiv.dataset.svelteComponent = 'rors';
  rorsDiv.dataset.endpoint = `users/${user.userid}/rors`;
  mountRors({ force: true });
}

// generate the slider element to toggle isAdmin and isOwner for a given user in a given team
function generateIsSomethingElement(what: string, team: Record<string, string|number>, isSysadmin: boolean = false) {
  const isSomething = document.createElement('div');
  isSomething.classList.add('d-flex', 'justify-content-between');
  const isSomethingLabel = document.createElement('label');
  isSomethingLabel.htmlFor = `is${what}Team_${team.id}`;
  isSomethingLabel.classList.add('col-form-label');
  isSomethingLabel.innerText = i18next.t(`is-${what}`);
  const isSomethingSwitch = document.createElement('label');
  isSomethingSwitch.classList.add('switch', 'ucp-align');
  isSomethingSwitch.id = `is${what}TeamSwitch_${team.id}`;
  const isSomethingInput = document.createElement('input');
  isSomethingInput.type = 'checkbox';
  isSomethingInput.autocomplete = 'off';
  // the is_owner checkbox is disabled if we are not sysadmin
  if (what === 'owner' && !isSysadmin) {
    isSomethingInput.disabled = true;
    isSomethingSwitch.classList.add('disabled');
    isSomethingSwitch.title = i18next.t('only-a-sysadmin');
  }
  isSomethingInput.dataset.trigger = 'change';
  isSomethingInput.dataset.customAction = 'patch-user2team-is';
  isSomethingInput.dataset.target= `is_${what}`;
  isSomethingInput.dataset.team = String(team.id);
  isSomethingInput.checked = team[`is_${what}`] === 1;
  isSomethingInput.id = `is${what}Team_${team.id}`;
  const slider = document.createElement('span');
  slider.classList.add('slider');
  isSomethingSwitch.appendChild(isSomethingInput);
  isSomethingSwitch.appendChild(slider);
  isSomething.appendChild(isSomethingLabel);
  isSomething.appendChild(isSomethingSwitch);
  return isSomething;
}

// from https://www.paulirish.com/2009/random-hex-color-code-snippets/
export function getRandomColor(): string {
  return `#${Math.floor(Math.random()*16777215).toString(16)}`;
}

type MountedRors = {
  component: ReturnType<typeof mount>;
  endpoint: string;
};

const mountedRors = new WeakMap<HTMLElement, MountedRors>();

export function mountRors(options: { force?: boolean } = {}): void {
  const { force = false } = options;

  document.querySelectorAll<HTMLElement>('[data-svelte-component="rors"]').forEach(target => {
    const endpoint = target.dataset.endpoint;

    if (!endpoint) {
      throw new Error('Missing data-endpoint for rors component');
    }

    const mounted = mountedRors.get(target);

    if (mounted) {
      if (!force && mounted.endpoint === endpoint) {
        return;
      }

      unmount(mounted.component);
      mountedRors.delete(target);
    }

    const component = mount(RorsSv, {
      target,
      props: {
        endpoint,
      },
    });

    mountedRors.set(target, {
      component,
      endpoint,
    });
  });
}

// default pagination size for ag grid components
export const DEFAULT_AG_GRID_PAGINATION = {
  pagination: true,
  paginationPageSize: 100,
  paginationPageSizeSelector: [100, 250, 500],
};

const registerTarget = document.getElementById('register-form-component');

if (registerTarget) {
  // read the data passed by Twig
  const options = JSON.parse(registerTarget.dataset.options ?? '{}');

  // replace loading spinner with the form
  registerTarget.replaceChildren();
  mount(RegisterForm, { target: registerTarget, props: { options },
  });
}
