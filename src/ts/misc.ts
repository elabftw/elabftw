/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */
import 'jquery-ui/ui/widgets/sortable';
import { Action, CheckableItem, EntityType, Entity, Model, Target, FileType } from './interfaces';
import { DateTime } from 'luxon';
import { MathJaxObject } from 'mathjax-full/js/components/startup';
import tinymce from 'tinymce/tinymce';
import { notify } from './notify';
import TableSorting from './TableSorting.class';
declare const MathJax: MathJaxObject;
import $ from 'jquery';
import i18next from './i18n';
import { ApiC } from './api';
import { getEditor } from './Editor.class';
import TomSelect from 'tom-select/base';
import TomSelectCheckboxOptions from 'tom-select/dist/esm/plugins/checkbox_options/plugin.js';
import TomSelectClearButton from 'tom-select/dist/esm/plugins/clear_button/plugin.js';
import TomSelectDropdownInput from 'tom-select/dist/esm/plugins/dropdown_input/plugin.js';
import TomSelectNoActiveItems from 'tom-select/dist/esm/plugins/no_active_items/plugin.js';
import TomSelectRemoveButton from 'tom-select/dist/esm/plugins/remove_button/plugin.js';
import TomSelectNoBackspaceDelete from 'tom-select/dist/esm/plugins/no_backspace_delete/plugin.js';

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

// DISPLAY TIME RELATIVE TO NOW
// the datetime is taken from the title of the element so mouse hover will show raw datetime
export function relativeMoment(): void {
  const locale = document.getElementById('user-prefs').dataset.jslang;
  document.querySelectorAll('.relative-moment').forEach(el => {
    const span = el as HTMLElement;
    // do nothing if it's already loaded, prevent infinite loop with mutation observer
    if (span.innerText) {
      return;
    }
    span.innerText = DateTime.fromFormat(span.title, 'yyyy-MM-dd HH:mm:ss', {'locale': locale}).toRelative();
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
    value = permissionsToJson(parseInt(value, 10), []);
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

    await ApiC.patch(`${el.dataset.model}`, params);

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
export function handleReloads(reloadAttributes: string | undefined): void {
  if (!reloadAttributes) return;

  if (reloadAttributes === 'page') {
    location.reload();
    return;
  }

  const reloadTargets = reloadAttributes.split(',');
  reloadTargets.forEach((toReload) => {
    if (toReload === 'reloadEntitiesShow') {
      reloadEntitiesShow();
    } else {
      reloadElements([toReload]).then(() => relativeMoment());
    }
  });
}

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
    el.classList.remove('border-danger');
    if (el.reportValidity() === false) {
      console.error(el);
      el.classList.add('border-danger');
      el.focus();
      el.scrollIntoView({ behavior: 'smooth', block: 'center' });
      throw new Error('Invalid input found! Aborting.');
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
      if (input.dataset.noBlank !== '1') {
        input.value = '';
        if (input.type === 'checkbox') {
          input.checked = false;
        }
      }
    });
  });
}

// for view or edit mode, get type and id from the page to construct the entity object
// enable usage with parent Window for iframe cases (e.g., with spreadsheet editor)
export function getEntity(useParent: boolean = false): Entity {
  let entityType: EntityType;
  let entityId: number | null = null;
  // pick the right location (parent or self)
  const loc = useParent ? window.parent.location : window.location;
  switch (loc.pathname) {
  case '/experiments.php':
    entityType = EntityType.Experiment;
    break;
  case '/database.php':
    entityType = EntityType.Item;
    break;
  case '/templates.php':
    entityType = EntityType.Template;
    break;
  case '/resources-templates.php':
    entityType = EntityType.ItemType;
    break;
  default:
    return {type: EntityType.Other, id: entityId};
  }
  const params = new URLSearchParams(loc.search);
  if (params.has('id')) {
    entityId = parseInt(params.get('id')!, 10);
  }
  return {
    type: entityType,
    id: entityId,
  };
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
export async function reloadEntitiesShow(tag = ''): Promise<void | Response> {
  // get the html
  const html = await fetchCurrentPage(tag);
  // reload items
  document.getElementById('showModeContent').innerHTML = html.getElementById('showModeContent').innerHTML;
  // ask mathjax to reparse the page
  MathJax.typeset();
  // rebind autocomplete for links input
  addAutocompleteToLinkInputs();
  // tags too
  addAutocompleteToTagInputs();
  // listen to data-trigger elements
  listenTrigger();
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

  const html = await fetchCurrentPage();
  elementIds.forEach(elementId => {
    if (!html.getElementById(elementId)) {
      console.warn(`Could not find element with id ${elementId} anymore, removing it`);
      document.getElementById(elementId).remove();
      return;
    }
    document.getElementById(elementId).innerHTML = html.getElementById(elementId).innerHTML;
    listenTrigger(elementId);
  });
  (new TableSorting()).init();
  makeSortableGreatAgain();
  relativeMoment();
}

/**
 * All elements that have a save-hidden data attribute have their visibility depend on the saved state
 * in localStorage. The localStorage key is the value of the save-hidden data attribute.
 */
export function adjustHiddenState(): void {
  document.querySelectorAll('[data-save-hidden]').forEach((el: HTMLElement) => {
    const localStorageKey = el.dataset.saveHidden + '-isHidden';
    const localStorageValue = localStorage.getItem(localStorageKey);
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

// AUTOCOMPLETE
export function addAutocompleteToLinkInputs(): void {
  const cache = {};
  [{
    selectElid: 'addLinkCatFilter',
    itemType: EntityType.Item,
    filterFamily: 'cat',
    inputElId: 'addLinkItemsInput',
  }, {
    selectElid: 'addLinkOwnerFilter',
    itemType: EntityType.Experiment,
    filterFamily: 'owner',
    inputElId: 'addLinkExpInput',
  }].forEach(object => {
    const filterEl = (document.getElementById(object.selectElid) as HTMLInputElement);
    if (filterEl) {
      cache[object.selectElid] = {};
      // when we change the category filter, reset the cache
      filterEl.addEventListener('change', () => {
        cache[object.selectElid] = {};
      });
      $(`#${object.inputElId}`).autocomplete({
        source: function(request: Record<string, string>, response: (data) => void): void {
          const term = request.term;
          const format = entity => {
            const category = entity.category_title ? `${entity.category_title} - `: '';
            const status = entity.status_title ? `${entity.status_title} - `: '';
            const customid = entity.custom_id ? `${entity.custom_id} - `: '';
            return `${entity.id} - ${category}${status}${customid}${entity.title.substring(0, 60)}`;
          };
          if (term in cache[object.selectElid]) {
            const res = [];
            cache[object.selectElid][term].forEach(entity => {
              res.push(format(entity));
            });
            response(res);
            return;
          }
          ApiC.getJson(`${object.itemType}/?${object.filterFamily}=${filterEl.value}&q=${escapeExtendedQuery(request.term)}&scope=3`).then(json => {
            cache[object.selectElid][term] = json;
            const res = [];
            json.forEach(entity => {
              res.push(format(entity));
            });
            response(res);
          });
        },
        select: function(event: Event, ui): boolean {
          const inputEl = event.target as HTMLInputElement;
          inputEl.value = ui.item.label;
          // don't let autocomplete change value of input element
          return false;
        },
      });
    }
  });
}

export function addAutocompleteToTagInputs(): void {
  $('[data-autocomplete="tags"]').autocomplete({
    source: function(request: Record<string, string>, response: (data) => void): void {
      ApiC.getJson(`${Model.Team}/current/${Model.Tag}?q=${request.term}`).then(json => {
        const res = [];
        json.forEach(tag => {
          res.push(tag.tag);
        });
        response(res);
      });
    },
  });
}

export function addAutocompleteToCompoundsInputs(): void {
  $('[data-autocomplete="compounds"]').autocomplete({
    source: function(request: Record<string, string>, response: (data) => void): void {
      ApiC.getJson(`${Model.Compounds}?q=${request.term}`).then(json => {
        const res = [];
        json.forEach(cpd => {
          res.push(`${cpd.id} - ${cpd.name}`);
        });
        response(res);
      });
    },
  });
}

export function addAutocompleteToExtraFieldsKeyInputs(): void {
  $('[data-autocomplete="extraFieldsKeys"]').autocomplete({
    appendTo: '#autocompleteAnchorDiv_extra_fields_keys',
    source: function(request: Record<string, string>, response: (data) => void): void {
      ApiC.getJson(`${Model.ExtraFieldsKeys}/?q=${request.term}`).then(json => {
        const res = [];
        json.forEach(entry => {
          res.push(entry.extra_fields_key);
        });
        response(res);
      });
    },
  });
}

// update category or status, returns the color as string
export async function updateCatStat(target: string, entity: Entity, value: string): Promise<string> {
  const params = {};
  params[target] = value;
  const newEntity = await ApiC.patch(`${entity.type}/${entity.id}`, params).then(resp => resp.json());
  // return a string separated with | with the id first so we can use it in data-id of new element
  let response = value + '|';
  return response += (target === 'category' ? newEntity.category_color : newEntity.status_color) ?? 'bdbdbd';
}

// used in edit.ts to build search patterns from strings that contain special characters
// taken from https://developer.mozilla.org/en-US/docs/Web/JavaScript/Guide/Regular_Expressions#escaping
export function escapeRegExp(string: string): string {
  // $& means the whole matched string
  return string.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
}

// used in metadata.ts to normalize field names only by trimming out double and simple quotes, causing to SQL issues
export function normalizeFieldName(input: string): string {
  return input.replace(/['"]/g, '').trim().replace(/\s+/g, ' ');
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

export function permissionsToJson(base: number, extra: string[]): string {
  const json = {
    'base': 0,
    'teams': [],
    'teamgroups': [],
    'users': [],
  };

  json.base = base;

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

// go over all the type: url elements and create a link dynamically
export function generateMetadataLink(): void {
  document.querySelectorAll('[data-gen-link="true"]').forEach(el => {
    const link = document.createElement('a');
    link.classList.add('d-block');
    const url = (el as HTMLSpanElement).innerText;
    link.href = url;
    link.text = url;
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

// escape text similar to htmlspecialchars() of php
// https://stackoverflow.com/a/4835406
export function escapeHTML(text: string): string {
  const escapeMap = {
    '&': '&amp;',
    '<': '&lt;',
    '>': '&gt;',
    '"': '&#34;',
    '\'': '&#39;',
  };
  return text.replace(/[&<>'"]/g, char => escapeMap[char]);
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
    // mask error notifications
    ApiC.notifOnError = false;
    // view mode is innerText
    let changedAttribute = 'innerText';
    // edit mode is value because it's an input
    if (el instanceof HTMLInputElement) {
      changedAttribute = 'value';
    }
    ApiC.getJson(`${el.dataset.endpoint}/${el.dataset.id}`).then(json => {
      // view mode for Experiments or Resources
      let value = el.dataset.endpoint === Model.User ? json.fullname : json.title;
      // edit mode
      if (el instanceof HTMLInputElement) {
        value = `${json.id} - ${json.title}`;
        if (el.dataset.endpoint === Model.User) {
          value = `${json.userid} - ${json.fullname}`;
        }
      }
      el[changedAttribute] = value;
    }).catch(() => {
      el[changedAttribute] = i18next.t('resource-not-found');
      el.classList.add('color-warning');
    });
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
export async function updateEntityBody(): Promise<void> {
  const editor = getEditor();
  const entity = getEntity();
  return ApiC.patch(`${entity.type}/${entity.id}`, {body: editor.getContent()}).then(response => response.json()).then(json => {
    if (editor.type === 'tiny') {
      // set the editor as non dirty so we can navigate out without a warning to clear
      tinymce.activeEditor.setDirty(false);
    }
    const lastSavedAt = document.getElementById('lastSavedAt');
    if (lastSavedAt) {
      lastSavedAt.title = json.modified_at;
      reloadElements(['lastSavedAt']).then(() => relativeMoment());
    }
  }).catch(() => {
    // detect if the session timedout (Session expired error is thrown)
    // store the modifications in local storage to prevent any data loss
    localStorage.setItem('body', editor.getContent());
    localStorage.setItem('id', String(entity.id));
    localStorage.setItem('type', entity.type);
    localStorage.setItem('date', new Date().toLocaleString());
    // reload the page so user gets redirected to the login page
    location.reload();
  });
}

// bind used plugins to TomSelect
TomSelect.define('checkbox_options', TomSelectCheckboxOptions);
TomSelect.define('clear_button', TomSelectClearButton);
TomSelect.define('dropdown_input', TomSelectDropdownInput);
TomSelect.define('no_active_items', TomSelectNoActiveItems);
TomSelect.define('remove_button', TomSelectRemoveButton);
TomSelect.define('no_backspace_delete', TomSelectNoBackspaceDelete);
export { TomSelect };

// toggle appearance of button
export function toggleGrayClasses(classList: DOMTokenList): void {
  ['bgnd-gray', 'hl-hover-gray'].forEach(btnClass => classList.toggle(btnClass, !classList.contains(btnClass)));
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
  document.getElementById('editCompoundModalSaveBtn').dataset.compoundId = json['id'];
  (document.getElementById('compoundLink-pubchem') as HTMLLinkElement).href = `https://pubchem.ncbi.nlm.nih.gov/compound/${json['pubchem_cid']}`;
  $('#editCompoundModal').modal('toggle');
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
export async function populateUserModal(user: Record<string, string|number>) {
  const manageTeamsDiv = document.getElementById('manageTeamsDiv');
  if (!manageTeamsDiv) {
    return;
  }
  const requester = await ApiC.getJson('users/me');
  // eslint-disable-next-line @typescript-eslint/no-explicit-any
  const userTeams = user.teams as any;
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
    // prevent deleting association of the team we are currently logged in, allow it for other users
    if (team.id !== requester.team || user.userid !== requester.userid) {
      const removeTeamBtn = document.createElement('span');
      removeTeamBtn.classList.add('hl-hover-gray', 'p-1', 'rounded', 'clickable', 'm-1');
      removeTeamBtn.title = i18next.t('delete');
      removeTeamBtn.dataset.action = 'destroy-user2team';
      removeTeamBtn.dataset.teamid = team.id;
      const removeTeamIcon = document.createElement('i');
      removeTeamIcon.classList.add('fas', 'fa-xmark', 'color-blue');
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

export function ensureTogglableSectionIsOpen(iconId: string, divId: string): void {
  // toggle the arrow icon
  const iconEl = document.getElementById(iconId);
  iconEl.classList.add('fa-caret-down');
  iconEl.classList.remove('fa-caret-right');
  const div = document.getElementById(divId);
  // make sure it's not hidden
  div.removeAttribute('hidden');
  // and scroll page into editor view
  div.scrollIntoView({ behavior: 'smooth' });
}
