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
  reloadEntitiesShow,
  TomSelect,
} from './misc';
import { Action, Model, LinkSubModel } from './interfaces';
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
import $ from 'jquery';
import { core } from './core';

type TeamScopedTomSelect = TomSelectWithAllOptions & {
  _showAll?: boolean;
};

type ActiveFilterControl = {
  control: TeamScopedTomSelect;
  param: string;
  title: string;
};


type EntityFilterParam = 'owner' | 'category' | 'status';

type EntityFilterRequestedDetail = {
  param: EntityFilterParam;
  value: string;
  label?: string | null;
};

const activeFilters = document.getElementById('activeFiltersDiv');
let debounceTimer: number | undefined;
let entityListSvComponent: Record<string, unknown> | null = null;
const initialQ = new URL(window.location.href).searchParams.get('q') ?? '';
const filterControls: ActiveFilterControl[] = [];
const searchQuery = writable(initialQ);
const selectedEntities = writable<string[]>([]);

searchQuery.subscribe(value => {
  window.clearTimeout(debounceTimer);

  debounceTimer = window.setTimeout(() => {
    const trimmedValue = value.trim();
    const url = new URL(window.location.href);

    if (trimmedValue.length > 0) {
      url.searchParams.set('q', trimmedValue);
    } else {
      url.searchParams.delete('q');
    }

    window.history.replaceState({}, '', url.toString());
    window.dispatchEvent(new CustomEvent('entity-filters-changed'));
  }, 250);
});

function handleInitialLoadDone(): void {
  // remove skeleton
  document.getElementById('itemListSkeleton')?.remove();
}

async function getDisplayMode() {
  return ApiC.getJson(`${Model.User}/me`).then((json: { display_mode?: string }) => {
    return json['display_mode'];
  }).catch(() => 'it');
}

const mountEntityListSv = (target: HTMLElement): void => {
  if (entityListSvComponent) {
    return;
  }

  entityListSvComponent = mount(EntityListSv, {
    target,
    props: {
      entityType: entity.type,
      limit: 15,
      searchQuery,
      selectedEntities,
      currentUserId: core.currentUserid,
      currentTeam: core.currentTeam,
      isAdmin: core.isAdmin,
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

async function displayEntities(mode: string) {
  const rootEl = document.getElementById('entityList');
  if (mode === 'tb') {
    unmountEntityListSv();
    mountEntitiesTable(rootEl, searchQuery, selectedEntities);
    handleInitialLoadDone();
    return;
  }
  unmountEntitiesTable();
  mountEntityListSv(rootEl);
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

// dynamically handle the available actions depending the state of selected entities
function toggleActionButtonsDependingOnSelected(): void {
  const selected = Array.from(
    document.querySelectorAll<HTMLInputElement>('[data-action="checkbox-entity"]:checked'),
  );
  // collect all states from selected checkboxes
  const selectedStates = new Set<string>();
  selected.forEach((chk) => {
    if (chk.dataset.state) {
      selectedStates.add(chk.dataset.state);
    }
  });

  document.querySelectorAll<HTMLButtonElement>('[data-action="patch-selected-entities"]').forEach(btn => {
    const action = btn.dataset.what;
    // enable "Restore" button if 'Deleted' (3) is among the selected entities' state
    const allowRestore = selectedStates.size === 1 && selectedStates.has('3') && action === 'restore';
    // enable "Unarchive" button if 'Archived' (2) is among the selected entities' state
    const allowUnarchive = selectedStates.size === 1 && selectedStates.has('2') && action === 'unarchive';
    // special actions to disable by default unless above conditions apply
    const isSpecialAction = ['restore', 'unarchive'].includes(action);
    // default enabled actions
    const allowDefault = !selectedStates.has('2') && !selectedStates.has('3') && !isSpecialAction;

    const shouldEnable = allowRestore || allowUnarchive || allowDefault;
    const buttonLabel = btn.getAttribute('aria-label') ?? action;
    const cannotAction = i18next.t('illegal-action');
    // the tooltip when you hover the action, based on the enabled/disabled state
    if (shouldEnable) {
      btn.disabled = false;
      btn.setAttribute('title', buttonLabel);
    } else {
      btn.disabled = true;
      btn.setAttribute('title', cannotAction);
    }
  });
}

type TomSelectOptionLike = Record<string, unknown> & {
  $option?: Element | null;
};

type ToggleableTomSelect = TomSelect & {
  [key: string]: unknown;
};

type TomSelectWithAllOptions = ToggleableTomSelect & {
  _allOptions?: TomSelectOptionLike[];
};

type AnyTS = TomSelectWithAllOptions & {
  _showArchived?: boolean;
};
const bound = new WeakSet<Element>();

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
  // can't have await at top level, so wrap it
  void (async (): Promise<void> => {
    const displayMode = await getDisplayMode();
    displayEntities(displayMode);
  })();

  preventReactiveSearchFormSubmit();

  // TomSelect for extra fields & owner search select
  if (document.getElementById('metakey')) {
    new TomSelect('#metakey', {
      maxOptions: 512,
      plugins: [
        'dropdown_input',
        'remove_button',
      ],
    });
  }

  if (document.getElementById('filterOwner')) {
    const dropdownRoot = document.getElementById('filterOwnerDropdown');
    const menu = document.getElementById('filterOwnerMenu');

    const control = new TomSelect('#filterOwner', {
      dropdownParent: '#filterOwnerMenu',
      maxOptions: 512,
      plugins: {
        dropdown_header: {
          title: 'Users',
          html: buildDropdownToggleHeaderHtml(i18next.t('users'), 'ts-toggle-archived', i18next.t('show-archived')),
        },
        remove_button: {},
      },

      onInitialize(this: AnyTS) {
        this._allOptions = Object.values(this.options) as TomSelectOptionLike[];
        this._showArchived = false;
        applyToggleFilter(this, '_showArchived', isArchivedOption);
      },

      onChange(value: string | string[] | null | undefined) {
        syncMultiSelectParam('owner', value);
        renderActiveFilters();
      },

      render: {
        option(data: AnyTS, escape: (s: string) => string) {
          const optEl = data.$option as HTMLOptionElement | undefined;
          const isArchived = optEl?.getAttribute('data-is-archived') === '1';
          const icon = isArchived ? '<i class="fas fa-box-archive mr-1"></i>' : '';
          return `<div>${icon}${escape(data.text ?? data.name ?? '')}</div>`;
        },

        item(data: AnyTS, escape: (s: string) => string) {
          const optEl = data.$option as HTMLOptionElement | undefined;
          const isArchived = optEl?.getAttribute('data-is-archived') === '1';
          const icon = isArchived ? '<i class="fas fa-box-archive mr-1"></i>' : '';
          return `<div>${icon}${escape(data.text ?? data.name ?? '')}</div>`;
        },
      },
    }) as AnyTS;

    filterControls.push({
      control,
      param: 'owner',
      title: 'Owner',
    });
    renderActiveFilters();

    bindExternalFilterRequest(control, 'owner');

    control.on('dropdown_open', () => {
      bindDropdownToggle(control, '.ts-toggle-archived', '_showArchived', () => {
        applyToggleFilter(control, '_showArchived', isArchivedOption);
        control.open();
      });
    });

    if (dropdownRoot) {
      $(dropdownRoot).on('shown.bs.dropdown', function() {
        control.open();

        window.requestAnimationFrame(() => {
          const input = menu?.querySelector('.ts-control input') as HTMLInputElement | null;
          input?.focus();
        });

        bindDropdownToggle(control, '.ts-toggle-archived', '_showArchived', () => {
          applyToggleFilter(control, '_showArchived', isArchivedOption);
          control.open();
        });
      });

      $(dropdownRoot).on('hide.bs.dropdown', function() {
        control.close();
        control.blur();
      });
    }

    if (menu) {
      $(menu).on('click', function(event) {
        event.stopPropagation();
      });
    }
  }

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

  function getQuotes(value: string): string {
    return /[\s:'"()&|!]/.test(value) ? '"' : '';
  }

  function escapeSearchToken(value: string): string {
    return value.replace(/\\/g, '\\\\').replace(/"/g, '\\"');
  }

  function buildFilterHelperRegex(filterName: string): RegExp {
    const baseRegex = '(?:(?:"((?:\\\\"|(?:(?!")).)+)")|(?:\'((?:\\\\\'|(?:(?!\')).)+)\')|([^\\s:\'"()&|!]+))';
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
    return '(?:(?:"(?:\\\\"|[^"])+")|(?:\'(?:\\\\\'|[^\'])*\')|[^\\s:\'"()&|!]+)';
  }

  function quoteSearchToken(value: string): string {
    const escaped = value.replace(/\\/g, '\\\\').replace(/"/g, '\\"');

    if (/[\s:'"()&|!]/.test(escaped)) {
      return `"${escaped}"`;
    }

    return escaped;
  }

  function buildExtrafieldFilter(): string {
    const metakey = document.getElementById('metakey') as HTMLSelectElement | null;
    const metavalue = document.getElementById('metavalue') as HTMLInputElement | null;

    const key = metakey?.value.trim() ?? '';
    const value = metavalue?.value.trim() ?? '';

    if (key === '' && value === '') {
      return '';
    }

    if (key === '' || value === '') {
      return `extrafield:${quoteSearchToken(key || value)}`;
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
    return new RegExp(`(^|\\s)extrafield:${token}(?::${token})?\\s?`);
  }

  function syncExtrafieldFilterToSearchQuery(): void {
    const curVal = get(searchQuery);
    const filter = buildExtrafieldFilter();
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
    const url = new URL(window.location.href);

    url.searchParams.delete('offset');

    if (value === '') {
      url.searchParams.delete(param);
    } else {
      url.searchParams.set(param, value);
    }

    window.history.replaceState({}, '', url.toString());
    reloadEntitiesShow();
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

  // background color for selected entities
  const bgColor = 'var(--lightblue)';

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

  /////////////////////////
  // MAIN CLICK LISTENER //
  /////////////////////////
  document.getElementById('container').addEventListener('click', async event => {
    const el = (event.target as HTMLElement);
    const params = new URLSearchParams(document.location.search);
    // SAVE MULTI CHANGES
    if (el.matches('[data-action="save-multi-changes"]')) {

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
      (el as HTMLButtonElement).disabled = true;
      const ajaxs: Promise<unknown>[] = [];
      const form = document.getElementById('multiChangesForm');
      const params = collectForm(form);
      clearForm(form);
      checked.forEach(chk => {
        const paramsCopy = Object.assign({}, params);
        // they do not have all the same endpoint: handle tags and links the generic patch method
        for (const key in paramsCopy) {
          if (key === 'tags') {
            ajaxs.push(ApiC.post(`${entity.type}/${chk}/${Model.Tag}`, {notifOnSaved: 0, tag: paramsCopy[key]}));
            delete paramsCopy[key];
          } else if (Object.values(LinkSubModel).includes(key as LinkSubModel)) {
            ajaxs.push(ApiC.post(`${entity.type}/${chk}/${key}/${parseInt(paramsCopy[key], 10)}`));
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

    } else if (el.matches('[data-action="clear-form"]')) {
      clearForm(document.getElementById(el.dataset.target));

      if (el.dataset.target === 'multiChangesForm') {
        selectedEntities.set([]);

        document.querySelectorAll<HTMLInputElement>('[data-action="checkbox-entity"]:checked').forEach(checkbox => {
          checkbox.checked = false;
          (checkbox.closest('.entity') as HTMLElement).style.backgroundColor = '';
        });

        document.getElementById('withSelected')?.classList.add('d-none');
        document.querySelector('a[data-action="invert-entities-selection"]')?.setAttribute('hidden', 'hidden');
        toggleActionButtonsDependingOnSelected();
      }

    // TOGGLE DISPLAY
    } else if (el.matches('[data-action="toggle-items-layout"]')) {
      let target = 'it';
      const currentMode = await getDisplayMode();
      if (currentMode === 'it') {
        target = 'tb';
      }
      ApiC.patch(`${Model.User}/me`, { notifOnSaved: 0, display_mode: target}).then(() => {
        document.getElementById('realContainer')?.classList.toggle('max-width-70', target === 'it');
        displayEntities(target);
      });

    // a tag has been clicked/selected, add it in url and load the page
    } else if (el.matches('[data-action="add-tag-filter"]')) {
      params.set('tags[]', el.dataset.tag);
      // clear out any offset from a previous query
      params.delete('offset');
      history.replaceState(null, '', `?${params.toString()}`);
      document.querySelectorAll('[data-action="add-tag-filter"]').forEach(el => {
        el.classList.remove('selected');
      });
      el.classList.add('selected');
      reloadEntitiesShow();

    // SORT COLUMN IN TABULAR MODE
    } else if (el.matches('[data-action="reorder-entities"]')) {
      const params = new URLSearchParams(document.location.search);
      let sort = 'desc';
      if (params.get('order') === el.dataset.orderby
        && params.get('sort') === 'desc'
      ) {
        sort = 'asc';
      }
      params.set('sort', sort);
      params.set('order', el.dataset.orderby);
      window.location.href = `?${params.toString()}`;

    // CHECK AN ENTITY BOX
    } else if (el.matches('[data-action="checkbox-entity"]')) {
      ['withSelected'].forEach(id => {
        const el = document.getElementById(id);
        const scroll = el.classList.contains('d-none');
        el.classList.remove('d-none');
        if (scroll && el.getBoundingClientRect().bottom > 0) {
          window.scrollBy({top: el.offsetHeight, behavior: 'instant'});
        }
      });
      toggleActionButtonsDependingOnSelected();
      syncSelectedEntitiesFromDom();
      if ((el as HTMLInputElement).checked) {
        (el.closest('.entity') as HTMLElement).style.backgroundColor = bgColor;
      } else {
        (el.closest('.entity') as HTMLElement).style.backgroundColor = '';
      }
      // show invert select if any checkbox is selected
      const anyChecked = get(selectedEntities).length > 0;
      const invertSelections = document.querySelector('a[data-action="invert-entities-selection"]') as HTMLAnchorElement;
      if (anyChecked) {
        invertSelections?.removeAttribute('hidden');
      } else {
        invertSelections?.setAttribute('hidden', 'hidden');
        // Remove withSelected actions if there are no more checked checkboxes
        document.getElementById('withSelected')?.classList.add('d-none');
      }

    // RESTORE ENTITY IN SHOW MODE
    } else if (el.matches('[data-action="restore-entity-showmode"]')) {
      ApiC.patch(`${el.dataset.endpoint}/${el.dataset.id}`, { action: Action.Restore }).then(() => reloadEntitiesShow());

    // EXPAND ALL
    } else if (el.matches('[data-action="expand-all-entities"]')) {
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

    // SELECT ALL CHECKBOXES
    } else if (el.matches('[data-action="toggle-select-all-entities"]')) {
      event.preventDefault();
      if (el.dataset.target === 'select') {
        // check all boxes and set background color
        document.querySelectorAll('.entity input[type=checkbox]')?.forEach(box => {
          (box as HTMLInputElement).checked = true;
          (box.closest('.entity') as HTMLElement).style.backgroundColor = bgColor;
        });
        document.getElementById('withSelected')?.classList.remove('d-none');
        el.dataset.target = 'unselect';
      } else {
        document.querySelectorAll('.entity input[type=checkbox]')?.forEach(box => {
          (box as HTMLInputElement).checked = false;
          (box.closest('.entity') as HTMLElement).style.backgroundColor = '';
        });
        el.dataset.target = 'select';
        document.getElementById('withSelected')?.classList.add('d-none');
      }
      const icon = el.querySelector('i');
      icon.classList.toggle('fa-square');
      icon.classList.toggle('fa-square-check');
      el.nextElementSibling.removeAttribute('hidden');
      syncSelectedEntitiesFromDom();
      toggleActionButtonsDependingOnSelected();

    // INVERT SELECTION
    } else if (el.matches('[data-action="invert-entities-selection"]')) {
      event.preventDefault();
      document.querySelectorAll('.entity input[type=checkbox]').forEach(box => {
        (box as HTMLInputElement).checked = !(box as HTMLInputElement).checked;
        let newBgColor = '';
        if ((box as HTMLInputElement).checked) {
          newBgColor = bgColor;
        }
        (box.closest('.entity') as HTMLElement).style.backgroundColor = newBgColor;
      });
      syncSelectedEntitiesFromDom();
      toggleActionButtonsDependingOnSelected();

      const anyChecked = get(selectedEntities).length > 0;
      // Remove withSelected actions if there are no more checked checkboxes
      const withSelected = document.getElementById('withSelected') as HTMLDivElement;
      if (anyChecked) {
        withSelected.classList.remove('d-none');
      } else {
        withSelected.classList.add('d-none');
      }

    // PATCH ACTIONS FOR CHECKED BOXES : lock, unlock, timestamp, archive
    } else if (el.matches('[data-action="patch-selected-entities"]')) {
      // get the item id of all checked boxes
      const checked = get(selectedEntities);
      if (checked.length === 0) {
        notify.error('nothing-selected');
        return;
      }
      const action = <Action>el.dataset.what;
      // special case: DELETE request for confirmation & deletes div
      if (action === Action.Destroy) {
        if (!confirm(i18next.t('generic-delete-warning'))) {
          return;
        }
        // perform deletes
        const deletes = checked.map(chk =>
          ApiC.delete(`${entity.type}/${chk}`, { notifOnSaved:0 }),
        );
        Promise.all(deletes).then(() => {
          notify.success();
          reloadEntitiesShow();
        });
        return;
      }
      // handle all other PATCH with selected action
      const results = checked.map(chk =>
        ApiC.patch(`${entity.type}/${chk}`, { notifOnSaved: 0, action }),
      );
      Promise.all(results).then(() => {
        notify.success();
        reloadEntitiesShow();
      });
    }
  });


  function buildDropdownToggleHeaderHtml(
    title: string,
    checkboxClass: string,
    label: string,
  ) {
    return (data: { headerClass: string; titleRowClass: string }) => `
      <div class="${data.headerClass}">
        <div class="${data.titleRowClass}" style="display:flex; align-items:center; gap:12px;">
          <div>${title}</div>
          <label style="margin-left:auto; display:flex; align-items:center; gap:6px; font-weight:normal;">
            <input type="checkbox" class="${checkboxClass}">
            ${label}
          </label>
        </div>
      </div>
    `;
  }

  function getOptionFlag(opt: TomSelectOptionLike | undefined | null, attr: string): boolean {
    const el = opt?.$option as HTMLOptionElement | undefined;
    const raw = el?.getAttribute(attr) ?? '0';
    return raw === '1';
  }

  function isCurrentTeamOption(opt: TomSelectOptionLike | undefined | null): boolean {
    return getOptionFlag(opt, 'data-current-team');
  }

  function isArchivedOption(opt: TomSelectOptionLike | undefined | null): boolean {
    return getOptionFlag(opt, 'data-is-archived');
  }

  function applyToggleFilter(
    control: TomSelectWithAllOptions,
    flagKey: string,
    hideWhenFlagFalse: (opt: TomSelectOptionLike) => boolean,
  ) {
    const selected = new Set(control.items.map(String));
    const flagOn = !!control[flagKey];

    if (flagOn) {
      for (const opt of control._allOptions ?? []) control.addOption(opt);
    } else {
      for (const opt of control._allOptions ?? []) {
        const id = String(opt[control.settings.valueField]);
        if (hideWhenFlagFalse(opt) && !selected.has(id)) {
          control.removeOption(id);
        }
      }
    }
    control.refreshOptions(false);
  }

  function applyTeamFilter(control: TeamScopedTomSelect) {
    applyToggleFilter(control, '_showAll', (opt) => !isCurrentTeamOption(opt));
  }

  function syncMultiSelectParam(param: string, value: string | string[] | null | undefined) {
    const url = new URL(window.location.href);

    // Tom Select gives an array for multi-select; normalize just in case
    const selected = Array.isArray(value) ? value : value ? [value] : [];

    if (selected.length === 0) {
      url.searchParams.delete(param);
    } else {
      const joined = selected.join(',');
      url.searchParams.set(param, joined); // param=1,2,5
    }

    window.history.replaceState({}, '', url.toString());
    window.dispatchEvent(new CustomEvent('entity-filters-changed'));
  }

  function bindExternalFilterRequest(
    control: TomSelectWithAllOptions,
    param: EntityFilterParam,
  ): void {
    window.addEventListener('entity-filter-requested', event => {
      const customEvent = event as CustomEvent<EntityFilterRequestedDetail>;
      const detail = customEvent.detail;

      if (detail?.param !== param || !detail.value) {
        return;
      }

      toggleTomSelectItem(
        control,
        detail.value,
        detail.label ?? detail.value,
      );
    });
  }

  function ensureTomSelectOption(
    control: TomSelectWithAllOptions,
    value: string,
    label: string,
  ): void {
    if (control.options[value]) {
      return;
    }

    const valueField = control.settings.valueField || 'value';
    const labelField = control.settings.labelField || 'text';

    const knownOption = (control._allOptions ?? []).find(option => (
      String(option[valueField]) === value
    ));

    if (knownOption) {
      control.addOption(knownOption);
    } else {
      control.addOption({
        [valueField]: value,
        [labelField]: label,
      });
    }

    control.refreshOptions(false);
  }

  function toggleTomSelectItem(
    control: TomSelectWithAllOptions,
    value: string,
    label: string,
  ): void {
    ensureTomSelectOption(control, value, label);

    if (control.items.map(String).includes(value)) {
      control.removeItem(value);
      return;
    }

    control.addItem(value);
  }

  function renderActiveFilters() {
    activeFilters.replaceChildren();

    for (const { control, title } of filterControls) {
      for (const value of control.items) {
        const option = control.options[value];
        const labelField = control.settings.labelField || 'text';
        const label = String(option?.[labelField] ?? value);

        const chip = document.createElement('button');
        chip.type = 'button';
        chip.className = 'btn btn-sm btn-outline-secondary me-2 mb-2 mr-2';
        chip.setAttribute('aria-label', `Remove ${title}: ${label}`);

        const chipText = document.createElement('span');
        chipText.textContent = `${title}: ${label}`;

        const removeIcon = document.createElement('span');
        removeIcon.className = 'ms-1';
        removeIcon.setAttribute('aria-hidden', 'true');
        removeIcon.textContent = '×';

        chip.append(chipText, removeIcon);

        chip.addEventListener('click', () => {
          control.removeItem(value);
        });

        activeFilters.appendChild(chip);
      }
    }

    activeFilters.hidden = activeFilters.children.length === 0;
  }

  function bindDropdownToggle(
    control: ToggleableTomSelect,
    selector: string,
    flagKey: string,
    onToggle: (checked: boolean) => void,
  ) {
    const cb = control.dropdown?.querySelector(selector) as HTMLInputElement | null;
    if (!cb) return;

    // Always sync UI to current state when dropdown opens
    cb.checked = !!control[flagKey];

    // Bind once per checkbox element
    if (bound.has(cb)) return;
    bound.add(cb);

    cb.addEventListener('change', (ev) => {
      const checked = (ev.currentTarget as HTMLInputElement).checked;
      control[flagKey] = checked;
      onToggle(checked);
    });
  }

  function bindShowAllToggle(control: TeamScopedTomSelect) {
    bindDropdownToggle(control, '.ts-toggle-show-all', '_showAll', (checked) => {
      const url = new URL(window.location.href);
      if (checked) url.searchParams.set('scope', '3');
      else url.searchParams.delete('scope');
      window.history.replaceState({}, '', url.toString());

      applyTeamFilter(control);
      control.open(); // keep it open after filtering
      reloadEntitiesShow();
    });
  }

  function initTeamScopedFilter(cfg: {
    selectId: string;
    dropdownId: string;
    menuId: string;
    title: string;
    param: string;
  }) {
    const el = document.getElementById(cfg.selectId) as HTMLSelectElement | null;
    if (!el) return;

    const dropdownRoot = document.getElementById(cfg.dropdownId);
    const menu = document.getElementById(cfg.menuId);

    const control = new TomSelect(el, {
      dropdownParent: `#${cfg.menuId}`,
      maxOptions: 512,
      plugins: {
        dropdown_header: {
          title: cfg.title,
          html: buildDropdownToggleHeaderHtml(cfg.title, 'ts-toggle-show-all', i18next.t('show-all')),
        },
        remove_button: {},
      },

      onInitialize(this: TeamScopedTomSelect) {
        this._allOptions = Object.values(this.options) as TomSelectOptionLike[];
        this._showAll = false;

        applyTeamFilter(this);
      },

      onChange(value: string | string[] | null | undefined) {
        syncMultiSelectParam(cfg.param, value);
        renderActiveFilters();
      },
    }) as TeamScopedTomSelect;

    control.on('dropdown_open', () => bindShowAllToggle(control));
    filterControls.push({
      control,
      param: cfg.param,
      title: cfg.title,
    });
    renderActiveFilters();

    if (cfg.param === 'category' || cfg.param === 'status') {
      bindExternalFilterRequest(control, cfg.param);
    }

    if (dropdownRoot) {
      $(dropdownRoot).on('shown.bs.dropdown', function() {
        control.open();

        window.requestAnimationFrame(() => {
          const input = menu?.querySelector('.ts-control input') as HTMLInputElement | null;
          input?.focus();
        });

        bindShowAllToggle(control);
      });
    }

    if (menu) {
      $(menu).on('click', function(event) {
        event.stopPropagation();
      });
    }

    return control;
  }

  // category tomSelect
  initTeamScopedFilter({
    selectId: 'categoryFilter',
    dropdownId: 'categoryFilterDropdown',
    menuId: 'categoryFilterMenu',
    title: i18next.t('categories'),
    param: 'category',
  });
  // status tomSelect
  initTeamScopedFilter({
    selectId: 'statusFilter',
    dropdownId: 'statusFilterDropdown',
    menuId: 'statusFilterMenu',
    title: i18next.t('status'),
    param: 'status',
  });


  // tags tomselect
  if (document.getElementById('tagFilter')) {
    const dropdownRoot = document.getElementById('tagFilterDropdown');
    const menu = document.getElementById('tagFilterMenu');

    const tsTagFilter = new TomSelect('#tagFilter', {
      dropdownParent: '#tagFilterMenu',

      onChange: (value: unknown) => {
        const selectedTags = Array.isArray(value) ? value as string[] : [];
        const url = new URL(window.location.href);

        url.searchParams.delete('tags[]');

        selectedTags.forEach(tag => {
          url.searchParams.append('tags[]', tag);
        });

        window.history.replaceState({}, '', url.toString());
        window.dispatchEvent(new CustomEvent('entity-filters-changed'));
      },

      onItemAdd() {
        this.setTextboxValue('');
        this.refreshOptions();
      },

      plugins: {
        clear_button: {},
        no_active_items: {},
        remove_button: {},
      },
    });

    if (dropdownRoot) {
      $(dropdownRoot).on('shown.bs.dropdown', function() {
        tsTagFilter.open();

        window.requestAnimationFrame(() => {
          const input = menu?.querySelector('.ts-control input') as HTMLInputElement | null;
          input?.focus();
        });
      });

      $(dropdownRoot).on('hide.bs.dropdown', function() {
        tsTagFilter.close();
        tsTagFilter.blur();
      });
    }

    if (menu) {
      $(menu).on('click', function(event) {
        event.stopPropagation();
      });
    }
  }
});
