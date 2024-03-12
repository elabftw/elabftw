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
import TableSorting from './TableSorting.class';
declare const MathJax: MathJaxObject;
import $ from 'jquery';
import i18next from 'i18next';
import { Api } from './Apiv2.class';
import { ChemDoodle } from '@deltablot/chemdoodle-web-mini/dist/chemdoodle.min.js';

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
            reloadElement(toreload);
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
 */
export function collectForm(form: HTMLElement): object {
  let params = {};
  ['input', 'select', 'textarea'].forEach(inp => {
    form.querySelectorAll(inp).forEach((input: HTMLInputElement) => {
      const el = input;
      if (el.reportValidity() === false) {
        throw new Error('Invalid input found! Aborting.');
      }
      if (el.dataset.ignore !== '1' && el.disabled === false) {
        params = Object.assign(params, {[input.name]: input.value});
      }
      if (el.name === 'password') {
        // clear a password field once collected
        el.value = '';
      }
    });
  });
  // don't send an empty password
  if (params['password'] === '') {
    delete params['password'];
  }
  return params;
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
  const result = info.res ? 'ok' : 'ko';
  const overlay = document.createElement('div');
  overlay.setAttribute('id', 'overlay');
  overlay.setAttribute('class', 'overlay ' + 'overlay-' + result);
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

// DISPLAY 2D MOL FILES
export function displayMolFiles(): void {
  // loop all the mol files and display the molecule with ChemDoodle
  $.each($('.molFile'), function() {
    // id of the canvas to attach the viewer to
    const id = $(this).attr('id');
    // now get the file content and display it in the viewer
    ChemDoodle.io.file.content($(this).data('molpath'), function(fileContent: string){
      const mol = ChemDoodle.readMOL(fileContent);
      const viewer = new ChemDoodle.ViewerCanvas(id, 250, 250);
      // load it
      viewer.loadMolecule(mol);
    });
  });
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
      // send the order as an array
      const params = {table: $(this).data('table'), entity: getEntity(), ordering: $(this).sortable('toArray')};
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

export function getCheckedBoxes(): Array<CheckableItem> {
  const checkedBoxes = [];
  $('.item input[type=checkbox]:checked').each(function() {
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

export async function reloadElement(elementId: string): Promise<void> {
  if (!document.getElementById(elementId)) {
    console.error(`Could not find element with id ${elementId} to reload!`);
    return;
  }
  const html = await fetchCurrentPage();
  document.getElementById(elementId).innerHTML = html.getElementById(elementId).innerHTML;

  (new TableSorting()).init();
  listenTrigger(elementId);
}

export async function reloadElements(elementIds: string[]): Promise<void> {
  elementIds.forEach(id => reloadElement(id));
}

/**
 * All elements that have a save-hidden data attribute have their visibility depend on the saved state
 * in localStorage. The localStorage key is the value of the save-hidden data attribute.
 */
export function adjustHiddenState(): void {
  document.querySelectorAll('[data-save-hidden]').forEach(el => {
    const targetElement = (el as HTMLElement).dataset.saveHidden;
    const localStorageKey = targetElement + '-isHidden';
    const button = document.querySelector(`[data-toggle-target="${targetElement}"]`) || el.previousElementSibling;
    const caretIcon =  button.querySelector('i');
    if (localStorage.getItem(localStorageKey) === '1') {
      el.setAttribute('hidden', 'hidden');
      caretIcon.classList.remove('fa-caret-down');
      if (targetElement !== 'filtersDiv') {
        caretIcon.classList.add('fa-caret-right');
      }
      button.setAttribute('aria-expanded', 'false');
    // make sure to explicitly check for the value, because the key might not exist!
    } else if (localStorage.getItem(localStorageKey) === '0') {
      el.removeAttribute('hidden');
      caretIcon.classList.remove('fa-caret-right');
      caretIcon.classList.add('fa-caret-down');
      button.setAttribute('aria-expanded', 'true');
    }
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
            const customid = entity.custom_id ? `${entity.custom_id} - `: '';
            return `${entity.id} - ${category}${customid}${entity.title.substring(0, 60)}`;
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
      ApiC.getJson(`${Model.TeamTags}/?q=${request.term}`).then(json => {
        const res = [];
        json.forEach(tag => {
          res.push(tag.tag);
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

export function removeEmpty(params: object): object {
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

// transform the + icon in - and vice versa
export function togglePlusIcon(plusMinusIcon: HTMLElement): void {
  if (plusMinusIcon.classList.contains('fa-square-plus')) {
    plusMinusIcon.classList.remove('fa-square-plus');
    plusMinusIcon.classList.add('fa-square-minus');
  } else {
    plusMinusIcon.classList.add('fa-square-plus');
    plusMinusIcon.classList.remove('fa-square-minus');
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
  ['!', '|', '&', '(', ')', '"', '\''].forEach(specialChar => {
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
      let value = json.title;
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
