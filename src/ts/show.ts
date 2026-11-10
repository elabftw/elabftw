/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */
import {
  clearForm,
  collectForm,
  mkSpin,
  mkSpinStop,
  reloadEntitiesShow,
  TomSelect,
} from './misc';
import { Action, Model, LinkSubModel, REACTION_SEARCH_PARAMS } from './interfaces';
import 'bootstrap/js/src/modal.js';
import i18next from './i18n';
import { ApiC } from './api';
import { notify } from './notify';
import { entity } from './getEntity';
import { mountEntitiesTable, unmountEntitiesTable } from './entities-table';
import { get } from 'svelte/store';
import { mount, unmount } from 'svelte';
import { writable } from 'svelte/store';
import SearchBarSv from './components/SearchBar.svelte';
import EntityListSv from './components/EntityList.svelte';
import { core } from './core';
import { clearEntitiesSelection, selectedEntities } from './common';
import { on } from './handlers';
import { initTomSelectFilters } from './tomSelectFilters';

let debounceTimer: number | undefined;
let entityListSvComponent: Record<string, unknown> | null = null;
const initialUrlParams = new URLSearchParams(window.location.search);
const initialQ = initialUrlParams.get('q') ?? '';
initialUrlParams.delete('q');

const searchQuery = writable(initialQ);
const isSearchPending = writable(false);
const entityFilters = writable(initialUrlParams);
let searchQueryInitialized = false;

function updateUrlFromStores(filters = get(entityFilters)): void {
  const url = new URL(window.location.href);
  const q = get(searchQuery).trim();

  url.search = filters.toString();
  url.searchParams.delete('offset');

  if (q.length > 0) {
    url.searchParams.set('q', q);
  } else {
    url.searchParams.delete('q');
  }

  window.history.replaceState({}, '', url.toString());
  window.dispatchEvent(new CustomEvent('entity-filters-changed', {
    detail: new URLSearchParams(url.search),
  }));
}

function setEntityFilterParam(param: string, value: string): void {
  setEntityFilterParams({ [param]: value });
}

// change several params at once, so the entries are only reloaded once
function setEntityFilterParams(values: Record<string, string>): void {
  entityFilters.update(currentParams => {
    const nextParams = new URLSearchParams(currentParams);

    nextParams.delete('offset');

    for (const [param, value] of Object.entries(values)) {
      if (value === '') {
        nextParams.delete(param);
      } else {
        nextParams.set(param, value);
      }
    }

    updateUrlFromStores(nextParams);

    return nextParams;
  });
}

function initReactionSearch(): void {
  const form = document.getElementById('reactionSearchForm') as HTMLFormElement | null;
  if (!form) {
    return;
  }
  const errorEl = document.getElementById('reactionSearchError');
  const field = (name: string) => form.elements.namedItem(name) as HTMLInputElement | HTMLSelectElement;
  const readForm = (): Record<string, string> => ({
    reaction_q: field('reaction_q').value.trim(),
    reaction_structure: field('reaction_structure').value.trim(),
    reaction_role: field('reaction_role').value,
    reaction_exact: (field('reaction_exact') as HTMLInputElement).checked ? '1' : '',
  });

  form.addEventListener('submit', async event => {
    event.preventDefault();
    errorEl.hidden = true;
    const values = readForm();
    // the entries list hides failed queries, so check a structure first to tell what is wrong with it
    if (values.reaction_structure !== '') {
      const params: Record<string, string | number> = { limit: 1, notifOnError: 0 };
      for (const param of REACTION_SEARCH_PARAMS) {
        const value = values[param] ?? new URLSearchParams(window.location.search).get(param) ?? '';
        if (value !== '') {
          params[param] = value;
        }
      }
      try {
        await ApiC.getJson(entity.type, params);
      } catch (error) {
        errorEl.textContent = error instanceof Error ? error.message : String(error);
        errorEl.hidden = false;
        return;
      }
    }
    setEntityFilterParams(values);
  });

  form.addEventListener('click', event => {
    const button = (event.target as HTMLElement).closest<HTMLButtonElement>('[data-reaction-search-clear]');
    if (!button) {
      return;
    }
    errorEl.hidden = true;
    const param = button.dataset.reactionSearchClear;
    if (param === 'all') {
      form.querySelectorAll<HTMLInputElement | HTMLSelectElement>('input, select').forEach(input => {
        if (input instanceof HTMLInputElement && input.type === 'checkbox') {
          input.checked = false;
        } else {
          input.value = '';
        }
      });
      form.querySelectorAll('#reactionSearchLinks [data-reaction-search-clear]').forEach(el => el.remove());
      setEntityFilterParams(Object.fromEntries(REACTION_SEARCH_PARAMS.map(name => [name, ''])));
      return;
    }
    button.remove();
    setEntityFilterParam(param, '');
  });
}

function setEntityFilterParamValues(param: string, values: string[]): void {
  entityFilters.update(currentParams => {
    const nextParams = new URLSearchParams(currentParams);

    nextParams.delete('offset');
    nextParams.delete(param);

    values.filter(Boolean).forEach(value => nextParams.append(param, value));
    updateUrlFromStores(nextParams);

    return nextParams;
  });
}

function getEntityFilterParamValues(param: string, splitComma = true): string[] {
  const params = get(entityFilters);

  if (param === 'tags[]') {
    return params.getAll(param);
  }

  const value = params.get(param);

  if (!value) {
    return [];
  }

  return splitComma ? value.split(',').filter(Boolean) : [value];
}

searchQuery.subscribe(() => {
  if (!searchQueryInitialized) {
    searchQueryInitialized = true;
    return;
  }

  window.clearTimeout(debounceTimer);

  debounceTimer = window.setTimeout(() => {
    updateUrlFromStores();
  }, 250);
});

function handleInitialLoadDone(): void {
  // remove skeleton
  document.getElementById('itemListSkeleton')?.remove();
}

async function getMe() {
  return ApiC.getJson(`${Model.User}/me`);
}

function getCurrentUrlParam(param: string): string {
  return new URLSearchParams(window.location.search).get(param) ?? '';
}

function getCurrentUrlNumberParam(param: string): number | null {
  const value = getCurrentUrlParam(param);

  if (value.length === 0) {
    return null;
  }

  const numberValue = Number(value);

  return Number.isFinite(numberValue) ? numberValue : null;
}

function getPreferredOrder(user: Record<string, unknown>): string {
  const order = user['orderby'] ?? user['order'];

  return typeof order === 'string' && order.length > 0 ? order : 'date';
}

function getPreferredSort(user: Record<string, unknown>): string {
  const sort = user['sort'];

  return typeof sort === 'string' && sort.length > 0 ? sort : 'desc';
}

const mountEntityListSv = (target: HTMLElement, order: string, sort: string): void => {
  if (entityListSvComponent) {
    return;
  }

  entityListSvComponent = mount(EntityListSv, {
    target,
    props: {
      entityType: entity.type,
      limit: 15,
      order: order,
      sort: sort,
      searchQuery,
      queryPending: isSearchPending,
      entityFilters,
      selectedEntities,
      currentUserId: core.currentUserid,
      currentTeam: core.currentTeam,
      isAnon: core.isAnon,
      onInitialLoadDone: handleInitialLoadDone,
    },
  });
};

const unmountEntityListSv = async (): Promise<void> => {
  if (!entityListSvComponent) {
    return;
  }

  await unmount(entityListSvComponent, { outro: true });
  entityListSvComponent = null;
};

async function displayEntities(
  mode: string,
  order: string,
  sort: string,
  related: number | null = getCurrentUrlNumberParam('related'),
  relatedOrigin: string = getCurrentUrlParam('related_origin'),
) {
  const rootEl = document.getElementById('entityList');
  if (mode === 'tb') {
    unmountEntityListSv();
    mountEntitiesTable(rootEl, selectedEntities, order, sort, related, relatedOrigin);
    handleInitialLoadDone();
    return;
  }
  unmountEntitiesTable();
  mountEntityListSv(rootEl, order, sort);
}

const searchBar = document.getElementById('searchBar');
if (searchBar) {
  // remove placeholder input
  searchBar.innerHTML = '';
  mount(SearchBarSv, {
    target: searchBar,
    props: {
      name: searchBar.dataset.name ?? 'q',
      value: searchQuery,
      searchQuery,
      isPending: isSearchPending,
      placeholder: searchBar.dataset.placeholder ?? 'Search',
      ariaLabel: searchBar.dataset.ariaLabel ?? 'Search',
      buttonLabel: searchBar.dataset.buttonLabel ?? 'Search',
    },
  });
}

function preventReactiveSearchFormSubmit(): void {
  const form = document.getElementById('mainSearchForm') as HTMLFormElement | null;

  if (!form) {
    return;
  }

  form.addEventListener('submit', event => {
    event.preventDefault();

    const input = form.elements.namedItem('q') as HTMLInputElement | null;

    if (input) {
      searchQuery.set(input.value);
    }
  });
}

function syncSelectedEntitiesFromDom(): void {
  const selectedIds = Array.from(
    document.querySelectorAll<HTMLInputElement>('[data-action="checkbox-entity"]:checked'),
  ).map(item => item.dataset.id).filter(Boolean);

  selectedEntities.set(selectedIds);
}


// DOM
document.addEventListener('DOMContentLoaded', () => {
  if (!document.getElementById('info')) {
    return;
  }
  const about = document.getElementById('info').dataset;
  // only run in show mode
  if (about.page !== 'show') {
    return;
  }

  const withSelected = document.getElementById('withSelected');
  if (withSelected) {
    selectedEntities.subscribe(selected => {
      const isVisible = selected.length > 0;
      withSelected.classList.toggle('is-visible', isVisible);
      withSelected.toggleAttribute('inert', !isVisible);
    });
  }

  // can't have await at top level, so wrap it
  void (async (): Promise<void> => {
    const me = await getMe();
    displayEntities(String(me['display_mode'] ?? 'it'), getPreferredOrder(me), getPreferredSort(me));
  })();

  preventReactiveSearchFormSubmit();
  hydrateFilterAutoControlsFromUrl();
  initReactionSearch();

  // TomSelect for extra fields & owner search select
  const metakeySelect = document.getElementById('metakey');

  if (metakeySelect) {
    let metakeyOptionsLoaded = false;
    let metakeyOptionsLoading = false;

    new TomSelect('#metakey', {
      maxOptions: 512,
      plugins: [
        'dropdown_input',
        'remove_button',
      ],
      valueField: 'value',
      labelField: 'text',
      searchField: [
        'text',
      ],
      preload: 'focus',
      shouldLoad() {
        return !metakeyOptionsLoaded && !metakeyOptionsLoading;
      },
      load(query, callback) {
        if (metakeyOptionsLoading || metakeyOptionsLoaded) {
          callback();
          return;
        }
        metakeyOptionsLoading = true;
        // TODO: add a selectAll public fn to get all
        ApiC.getJson('extra_fields_keys', {limit: 999999})
          .then((extraFieldsKeys) => {
            const options = extraFieldsKeys
              .map((extraFieldKey) => ({
                value: extraFieldKey.extra_fields_key,
                text: extraFieldKey.extra_fields_key,
                frequency: extraFieldKey.frequency,
              }))
              .filter((option) => option.value);

            metakeyOptionsLoaded = true;
            callback(options);
          })
          .catch(() => {
            callback();
          })
          .finally(() => {
            metakeyOptionsLoading = false;
          });
      },
    });
  }

  initTomSelectFilters({
    entityFilters,
    setEntityFilterParam,
    setEntityFilterParamValues,
    getEntityFilterParamValues,
  });

  function getFilterValueFromElement(elem: HTMLElement): string {
    if (elem instanceof HTMLSelectElement) {
      return elem.options[elem.selectedIndex]?.value ?? '';
    }

    if (elem instanceof HTMLInputElement) {
      if (elem.type === 'checkbox') {
        return elem.checked ? elem.value : '';
      }

      return elem.value.trim();
    }

    if (elem instanceof HTMLTextAreaElement) {
      return elem.value.trim();
    }

    return '';
  }

  function hydrateFilterAutoControlsFromUrl(): void {
    document.querySelectorAll<HTMLElement>('.filterAuto').forEach(elem => {
      const param = elem.dataset.param || (elem as HTMLInputElement).name;
      const values = getEntityFilterParamValues(param, false);

      if (values.length === 0) {
        return;
      }

      if (elem instanceof HTMLSelectElement || elem instanceof HTMLTextAreaElement) {
        elem.value = values[0];
        return;
      }

      if (elem instanceof HTMLInputElement) {
        if (elem.type === 'checkbox') {
          const checkboxValues = getEntityFilterParamValues(param, true);
          elem.checked = checkboxValues.includes(elem.value);
          return;
        }

        elem.value = values[0];
      }
    });
  }

  function getQuotes(value: string): string {
    return /[\s:'"()&|!]/.test(value) ? '"' : '';
  }

  function escapeSearchToken(value: string): string {
    return value.replace(/\\/g, '\\\\').replace(/"/g, '\\"');
  }

  function buildFilterHelperRegex(filterName: string): RegExp {
    const baseRegex = '(?:(?:"((?:\\\\"|(?:(?!")).)*)")|(?:\'((?:\\\\\'|(?:(?!\')).)*)\')|([^\\s:\'"()&|!]+))';
    const operatorRegex = '(?:[<>]=?|!?=)?';
    let valueRegex = baseRegex;

    if (filterName === 'date') {
      valueRegex = operatorRegex + baseRegex;
    }

    if (filterName === 'extrafield') {
      valueRegex = `${baseRegex}:${baseRegex}`;
    }

    return new RegExp(`${filterName}:${valueRegex}\\s?`);
  }

  function getFilterHelperName(elem: HTMLElement): string {
    return elem.dataset.filter || (elem as HTMLInputElement).name;
  }

  function buildFilterHelperToken(elem: HTMLElement): string {
    let filterName = getFilterHelperName(elem);
    let filterValue = getFilterValueFromElement(elem);

    if (filterValue === '') {
      return '';
    }

    if (filterName === '(?:author|group)') {
      filterName = filterValue.split(':')[0];
      filterValue = filterValue.substring(filterName.length + 1);
    }

    if (filterName === 'date') {
      return `${filterName}:${filterValue}`;
    }

    if (filterName === 'extrafield') {
      return `${filterName}:${filterValue}`;
    }

    const escapedValue = escapeSearchToken(filterValue);
    const quotes = getQuotes(escapedValue);

    return `${filterName}:${quotes}${escapedValue}${quotes}`;
  }

  function syncFilterHelperToSearchQuery(elem: HTMLElement): void {
    const curVal = get(searchQuery);
    const hasInput = curVal.length !== 0;
    const hasSpace = curVal.endsWith(' ');
    const addSpace = hasInput ? (hasSpace ? '' : ' ') : '';
    const filterName = getFilterHelperName(elem);
    const regex = buildFilterHelperRegex(filterName);
    const filter = buildFilterHelperToken(elem);

    if (curVal.match(regex)) {
      searchQuery.set(curVal.replace(regex, filter === '' ? '' : `${filter} `).trimStart());
      return;
    }

    if (filter !== '') {
      searchQuery.set(`${curVal}${addSpace}${filter}`);
    }
  }

  function getSearchTokenRegexPart(): string {
    return '(?:(?:"(?:\\\\"|[^"])*")|(?:\'(?:\\\\\'|[^\'])*\')|[^\\s:\'"()&|!]+)';
  }

  function quoteSearchToken(value: string): string {
    if (value === '""' || value === '\'\'') {
      return '""';
    }

    const escaped = value.replace(/\\/g, '\\\\').replace(/"/g, '\\"');

    if (/[\s:'"()&|!]/.test(escaped)) {
      return `"${escaped}"`;
    }

    return escaped;
  }

  function buildExtrafieldFilter(forceEmptyValue = false): string {
    const metakey = document.getElementById('metakey') as HTMLSelectElement | null;
    const metavalue = document.getElementById('metavalue') as HTMLInputElement | null;

    const key = metakey?.value.trim() ?? '';
    const value = metavalue?.value.trim() ?? '';

    if (key === '') {
      return '';
    }

    if (forceEmptyValue || value === '""' || value === '\'\'') {
      return `extrafield:${quoteSearchToken(key)}:""`;
    }

    if (value === '') {
      return '';
    }

    return `extrafield:${quoteSearchToken(key)}:${quoteSearchToken(value)}`;
  }

  function buildExtrafieldRegex(): RegExp {
    const token = getSearchTokenRegexPart();

    // Matches:
    // extrafield:Manufacturer
    // extrafield:Manufacturer:abc
    // extrafield:Manufacturer:"abc"
    // extrafield:"Some Field":"abc"
    // extrafield:"Some Field":""
    return new RegExp(`(^|\\s)extrafield:${token}(?::${token})?\\s?`);
  }

  function syncExtrafieldFilterToSearchQuery(forceEmptyValue = false): void {
    const curVal = get(searchQuery);
    const filter = buildExtrafieldFilter(forceEmptyValue);
    const regex = buildExtrafieldRegex();

    if (regex.test(curVal)) {
      const next = curVal.replace(regex, (_match, prefix: string) => {
        return filter === '' ? prefix : `${prefix}${filter} `;
      });

      searchQuery.set(next.trim());
      return;
    }

    if (filter === '') {
      return;
    }

    searchQuery.set(`${curVal}${curVal.trim().length > 0 ? ' ' : ''}${filter}`);
  }

  // FILTERS HANDLER FOR THE SHOW PAGE
  document.querySelectorAll<HTMLElement>('.filterHelper').forEach(el => {
    const eventName = el instanceof HTMLInputElement && el.type === 'text'
      ? 'input'
      : 'change';

    el.addEventListener(eventName, event => {
      const elem = event.currentTarget as HTMLElement;

      if (elem.dataset.filter === 'extrafield') {
        syncExtrafieldFilterToSearchQuery();
        return;
      }

      syncFilterHelperToSearchQuery(elem);
    });
  });

  function syncFilterAutoToUrl(elem: HTMLElement): void {
    const param = elem.dataset.param || (elem as HTMLInputElement).name;
    const value = getFilterValueFromElement(elem);

    setEntityFilterParam(param, value);
  }

  document.querySelectorAll<HTMLElement>('.filterAuto').forEach(el => {
    el.addEventListener('change', event => {
      const elem = event.target as HTMLElement;

      // prevent this listener to be active when toggling archived users
      if (elem.classList.contains('ts-toggle-archived')) {
        return;
      }

      syncFilterAutoToUrl(elem);
    });
  });
  // END SEARCH RELATED CODE

  function highlightEntity(box: Element, isSelected: boolean): void {
    (box.closest('.entity') as HTMLElement).style.backgroundColor = isSelected ? 'var(--lightblue)' : '';
  }

  // the invert selection link only makes sense when something is selected
  function syncInvertSelectionLink(): void {
    document.querySelector('a[data-action="invert-entities-selection"]')
      ?.toggleAttribute('hidden', get(selectedEntities).length === 0);
  }

  if (document.getElementById('favtagsPanel')) {
    document.getElementById('favtagsPanel').addEventListener('keyup', event => {
      const el = (event.target as HTMLInputElement);
      const query = el.value;
      if (el.matches('[data-action="favtags-search"]')) {
        // find all links that are endpoints
        document.querySelectorAll('[data-action="add-tag-filter"]').forEach((el: HTMLElement) => {
          // begin by showing all so they don't stay hidden
          el.removeAttribute('hidden');
          // now simply hide the ones that don't match the query
          if (!el.innerText.toLowerCase().includes(query)) {
            el.hidden = true;
          }
        });
      }
    });
  }

  /////////////////////////////////////////
  // CHANGE LISTENER FOR SELECT ELEMENTS //
  // The select elements don't use a click event because on firefox the click is triggered on the option
  // and on chrome it is on the select instead
  /////////////////////////////////////////
  document.getElementById('container').addEventListener('change', event => {
    const el = (event.target as HTMLSelectElement);
    // EXPORT SELECTED
    if (el.matches('[data-action="export-selected-entities"]')) {
      const checked = get(selectedEntities);
      if (checked.length === 0) {
        notify.error('nothing-selected');
        return;
      }
      const format = el.value;
      const allowedFormats = new Set(['eln', 'zip', 'csv', 'pdf', 'qrpdf', 'json']);
      if (!allowedFormats.has(format)) {
        notify.error('invalid-info');
        return;
      }
      // reset selection so button can be used again with same format
      el.selectedIndex = 0;
      const params = new URLSearchParams({
        format,
        type: entity.type,
        id: checked.join('+'),
      });
      window.location.href = `make.php?${params.toString()}`;
    }
  });

  // START ACTION LISTENERS
  on('save-multi-changes', (el: HTMLElement, event: Event) => {
    // prevent form submission
    event.preventDefault();

    // get the item id of all checked boxes
    const checked = get(selectedEntities);
    if (checked.length === 0) {
      notify.error('nothing-selected');
      return;
    }
    // display a warning with the number of impacted entries
    if (!confirm(i18next.t('multi-changes-confirm', { num: checked.length }))) {
      return;
    }
    const ajaxs: Promise<unknown>[] = [];
    const form = document.getElementById('multiChangesForm');
    let params: object;
    try {
      params = collectForm(form);
    } catch {
      // collectForm reports the invalid field to the user already
      return;
    }
    // link inputs expect an "id - title" value from the autocomplete, not free text
    for (const key of Object.values(LinkSubModel)) {
      if (key in params) {
        const linkId = parseInt(params[key], 10);
        if (Number.isNaN(linkId) || linkId <= 0) {
          const input = form.querySelector<HTMLInputElement>(`[name="${key}"]`);
          input?.classList.add('border-danger');
          input?.focus();
          notify.error('invalid-info');
          return;
        }
        params[key] = linkId;
      }
    }
    clearForm(form);
    (el as HTMLButtonElement).disabled = true;
    checked.forEach(chk => {
      const paramsCopy = Object.assign({}, params, { notifOnSaved: 0 });
      // they do not have all the same endpoint: handle tags and links the generic patch method
      for (const key in paramsCopy) {
        if (key === 'tags') {
          ajaxs.push(ApiC.post(`${entity.type}/${chk}/${Model.Tag}`, {notifOnSaved: 0, tag: paramsCopy[key]}));
          delete paramsCopy[key];
        } else if (Object.values(LinkSubModel).includes(key as LinkSubModel)) {
          ajaxs.push(ApiC.post(`${entity.type}/${chk}/${key}/${paramsCopy[key]}`));
          delete paramsCopy[key];
        }
      }
      // patch whatever is left
      if (Object.entries(paramsCopy).length > 0) {
        ajaxs.push(ApiC.patch(`${entity.type}/${chk}`, paramsCopy));
      }
    });
    // reload the page once it's done
    Promise.all(ajaxs).then(() => {
      notify.success();
      reloadEntitiesShow();
    }).finally(() => (el as HTMLButtonElement).disabled = false);
  });

  on('clear-form', (el: HTMLElement) => {
    clearForm(document.getElementById(el.dataset.target));

    if (el.dataset.target === 'multiChangesForm') {
      clearEntitiesSelection();
    }
  });

  // TOGGLE DISPLAY
  on('toggle-items-layout', async () => {
    let target = 'it';
    const me = await getMe();
    if (me['display_mode'] === 'it') {
      target = 'tb';
    }
    ApiC.patch(`${Model.User}/me`, { notifOnSaved: 0, display_mode: target}).then(resp => resp.json()).then(json => {
      document.getElementById('realContainer')?.classList.toggle('max-width-70', target === 'it');
      displayEntities(target, getPreferredOrder(json), getPreferredSort(json));
    });
  });

  on('add-tag-filter', (el: HTMLElement) => {
    setEntityFilterParamValues('tags[]', [el.dataset.tag ?? '']);
    document.querySelectorAll('[data-action="add-tag-filter"]').forEach(el => {
      el.classList.remove('selected');
    });
    el.classList.add('selected');
  });

  on('search-empty-extrafield', () => {
    const metakey = document.getElementById('metakey') as HTMLSelectElement | null;
    const metavalue = document.getElementById('metavalue') as HTMLInputElement | null;
    const key = metakey?.value.trim() ?? '';
    if (key === '') {
      return;
    }
    if (metavalue) {
      metavalue.value = '';
    }
    syncExtrafieldFilterToSearchQuery(true);
  });

  // CHECK AN ENTITY BOX
  on('checkbox-entity', (el: HTMLElement) => {
    syncSelectedEntitiesFromDom();
    highlightEntity(el, (el as HTMLInputElement).checked);
    syncInvertSelectionLink();
  });

  // RESTORE ENTITY IN SHOW MODE
  on('restore-entity-showmode', (el: HTMLElement) => {
    ApiC.patch(`${el.dataset.endpoint}/${el.dataset.id}`, { action: Action.Restore }).then(() => reloadEntitiesShow());
  });

  // EXPAND ALL
  on('expand-all-entities', (el: HTMLElement, event: Event) => {
    event.preventDefault();
    if (el.dataset.status === 'closed') {
      el.dataset.status = 'opened';
    } else {
      el.dataset.status = 'closed';
    }
    const status = el.dataset.status;
    document.querySelectorAll('[data-action="toggle-body"]').forEach((toggleButton: HTMLElement) => {
      const isHidden = document.getElementById(toggleButton.dataset.randid).hidden;
      if ((status === 'opened' && !isHidden)
        || (status === 'closed' && isHidden)
      ) {
        return;
      }
      toggleButton.click();
    });
    const icon = el.querySelector('i');
    icon.classList.toggle('fa-maximize');
    icon.classList.toggle('fa-minimize');
  });

  // SELECT ALL CHECKBOXES
  on('toggle-select-all-entities', (el: HTMLElement, event: Event) => {
    event.preventDefault();
    const isSelecting = el.dataset.target === 'select';
    document.querySelectorAll<HTMLInputElement>('.entity input[type=checkbox]').forEach(box => {
      box.checked = isSelecting;
      highlightEntity(box, isSelecting);
    });
    el.dataset.target = isSelecting ? 'unselect' : 'select';
    const icon = el.querySelector('i');
    icon.classList.toggle('fa-square');
    icon.classList.toggle('fa-square-check');
    syncSelectedEntitiesFromDom();
    syncInvertSelectionLink();
  });

  // INVERT SELECTION
  on('invert-entities-selection', (el: HTMLElement, event: Event) => {
    event.preventDefault();
    document.querySelectorAll<HTMLInputElement>('.entity input[type=checkbox]').forEach(box => {
      box.checked = !box.checked;
      highlightEntity(box, box.checked);
    });
    syncSelectedEntitiesFromDom();
  });

  // PATCH ACTIONS FOR CHECKED BOXES : lock, unlock, timestamp, archive
  on('patch-selected-entities', (el: HTMLElement) => {
    const checked = get(selectedEntities);

    if (checked.length === 0) {
      notify.error('nothing-selected');
      return;
    }

    const btn = el as HTMLButtonElement;
    const oldHTML = mkSpin(btn);
    const action = el.dataset.what as Action;

    const requests = checked.map(id =>
      ApiC.patch(`${entity.type}/${id}`, {
        notifOnSaved: 0,
        notifOnError: 0,
        action,
      }),
    );

    Promise.allSettled(requests)
      .then(results => {
        const failedIds = results
          .map((result, index) => result.status === 'rejected' ? checked[index] : null)
          .filter((id): id is typeof checked[number] => id !== null);

        if (failedIds.length === 0) {
          notify.success();
        } else {
          // set bg red for failed entries
          for (const id of failedIds) {
            // for table mode we use a different approach, so this only applies to item mode
            const elem = document.querySelector(`[data-entity-id="${id}"`) as HTMLElement;
            if (elem) { elem.style.backgroundColor = 'var(--lightred)'; }
          }

          const modifiedCount = checked.length - failedIds.length;

          notify.warning(
            'entity-patch-multi-warning', {count: modifiedCount, failed: failedIds.length},
          );
          console.warn('Ids of entities that could not be modified are logged below:');
          console.warn(failedIds);
        }

        return reloadEntitiesShow();
      })
      .finally(() => {
        mkSpinStop(btn, oldHTML);
      });
  });
  // END ACTION LISTENERS
});
