/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */
import 'jquery-ui/ui/widgets/sortable';
import { Action, CheckableItem, ResponseMsg, EntityType, Entity, Model, Target } from './interfaces';
import { DateTime } from 'luxon';
import { MathJaxObject } from 'mathjax-full/js/components/startup';
import tinymce from 'tinymce/tinymce';
import TableSorting from './TableSorting.class';
declare const MathJax: MathJaxObject;
import $ from 'jquery';
import i18next from 'i18next';
import { Api } from './Apiv2.class';
import { getEditor } from './Editor.class';
import TomSelect from 'tom-select/dist/esm/tom-select';
import TomSelectCheckboxOptions from 'tom-select/dist/esm/plugins/checkbox_options/plugin';
import TomSelectClearButton from 'tom-select/dist/esm/plugins/clear_button/plugin';
import TomSelectDropdownInput from 'tom-select/dist/esm/plugins/dropdown_input/plugin';
import TomSelectNoActiveItems from 'tom-select/dist/esm/plugins/no_active_items/plugin';
import TomSelectRemoveButton from 'tom-select/dist/esm/plugins/remove_button/plugin';

// get html of current page reloaded via get
function fetchCurrentPage(tag = ''): Promise<Document>{
  const url = new URL(window.location.href);
  if (tag) {
    url.searchParams.delete('tags[]');
    url.searchParams.set('tags[]', tag);
  }
  return fetch(url.toString()).then(response => {
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
function triggerHandler(event: Event, el: HTMLInputElement): void {
  const ApiC = new Api();
  event.preventDefault();
  el.classList.remove('is-invalid');
  // for a checkbox element, look at the checked attribute, not the value
  let value = el.type === 'checkbox' ? el.checked ? '1' : '0' : el.value;
  if (el.dataset.customAction === 'patch-user2team-is-owner') {
    // currently only for modifying is_owner of a user in a given team
    const team = parseInt(el.dataset.team, 10);
    const userid = parseInt(el.dataset.userid, 10);
    ApiC.patch(`${Model.User}/${userid}`, {action: Action.PatchUser2Team, userid: userid, team: team, target: 'is_owner', content: value});
    return;
  }

  if (el.dataset.transform === 'permissionsToJson') {
    value = permissionsToJson(parseInt(value, 10), []);
  }
  if (el.dataset.value) {
    value = el.dataset.value;
  }
  const params = {};
  params[el.dataset.target] = value;
  ApiC.patch(`${el.dataset.model}`, params).then(() => {
    // data-reload can be "page" to reload the page, "reloadEntitiesShow" to reload properly entities in show mode,
    // or a comma separated list of ids of elements to reload
    if (el.dataset.reload) {
      if (el.dataset.reload === 'page') {
        location.reload();
      } else {
        el.dataset.reload.split(',').forEach(toreload => {
          if (toreload === 'reloadEntitiesShow') {
            reloadEntitiesShow();
          } else {
            reloadElements([toreload]).then(() => relativeMoment());
          }
        });
      }
    }
  }).catch(error => {
    if (el.dataset.target === Target.Customid && error.message === i18next.t('custom-id-in-use')) {
      el.classList.add('is-invalid');
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
 */
export function collectForm(form: HTMLElement): object {
  const inputs = [];
  ['input', 'select', 'textarea'].forEach(inp => {
    form.querySelectorAll(inp).forEach((input: HTMLInputElement) => {
      inputs.push(input);
    });
  });

  let params = {};
  inputs.forEach(input => {
    const el = input;
    if (el.reportValidity() === false) {
      throw new Error('Invalid input found! Aborting.');
    }
    let value = el.value;
    if (el.type === 'checkbox') {
      value = el.checked ? 'on' : 'off';
    }
    if (el.dataset.ignore !== '1' && el.disabled === false) {
      params = Object.assign(params, {[input.name]: value});
    }
  });

  return removeEmpty(params);
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
export function getEntity(): Entity {
  if (!document.getElementById('info')) {
    return;
  }
  // holds info about the page through data attributes
  const about = document.getElementById('info').dataset;
  let entityType: EntityType;
  if (about.type === 'experiments') {
    entityType = EntityType.Experiment;
  }
  if (about.type === 'items') {
    entityType = EntityType.Item;
  }
  if (about.type === 'experiments_templates') {
    entityType = EntityType.Template;
  }
  if (about.type === 'items_types') {
    entityType = EntityType.ItemType;
  }
  let entityId = null;
  if (about.id) {
    entityId = parseInt(about.id);
  }
  return {
    type: entityType,
    id: entityId,
  };
}
export function notifError(e): void {
  return notif({'res': false, 'msg': e.name + ': ' + e.message});
}

export function notifSaved(): void {
  return notif({'res': true, 'msg': i18next.t('saved')});
}

// PUT A NOTIFICATION IN TOP LEFT WINDOW CORNER
export function notif(info: ResponseMsg): void {
  // clear an existing one
  if (document.getElementById('overlay')) {
    document.getElementById('overlay').remove();
  }

  const p = document.createElement('p');
  // "status" role: see WCAG2.1 4.1.3
  p.role = 'status';
  p.innerText = info.msg;
  const overlay = document.createElement('div');
  overlay.id = 'overlay';
  overlay.classList.add('overlay');
  overlay.classList.add(`overlay-${info.res ? 'ok' : 'ko'}`);
  // show the overlay
  document.body.appendChild(overlay);
  // add text inside
  document.getElementById('overlay').appendChild(p);
  // error message takes longer to disappear
  const fadeOutDelay = info.res ? 2733 : 4871;
  // wait a bit and make it disappear
  window.setTimeout(function() {
    $('#overlay').fadeOut(763, function() {
      $(this).remove();
    });
  }, fadeOutDelay);
}

// insert a get param in the url and reload the page
export function insertParamAndReload(key: string, value: string): void {
  const params = new URLSearchParams(document.location.search.slice(1));
  params.set(key, value);
  // reload the page
  document.location.search = params.toString();
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
        notif(json);
      });
    },
  });
}

export function notifNothingSelected(): void {
  notif({
    msg: i18next.t('nothing-selected'),
    res: false,
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
  // also reload any pinned entities present
  if (document.getElementById('pinned-entities')) {
    document.getElementById('pinned-entities').innerHTML = html.getElementById('pinned-entities').innerHTML;
  }
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
  const ApiC = new Api();
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
          ApiC.getJson(`${object.itemType}/?${object.filterFamily}=${filterEl.value}&q=${escapeExtendedQuery(request.term)}`).then(json => {
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
  const ApiC = new Api();
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
  const ApiC = new Api();
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
  const ApiC = new Api();
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
  const newEntity = await (new Api()).patch(`${entity.type}/${entity.id}`, params).then(resp => resp.json());
  return (target === 'category' ? newEntity.category_color : newEntity.status_color) ?? 'bdbdbd';
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

function removeEmpty(params: object): object {
  for (const [key, value] of Object.entries(params)) {
    if (value === '') {
      delete params[key];
    }
  }
  return params;
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
    const ApiC = new Api();
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
  const ApiC = new Api();
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
