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
  getCheckedBoxes,
  getEntity,
  notifNothingSelected,
  notif,
  permissionsToJson,
  reloadElements,
  reloadEntitiesShow,
  TomSelect,
} from './misc';
import { Action, Model } from './interfaces';
import 'bootstrap/js/src/modal.js';
import i18next from 'i18next';
import FavTag from './FavTag.class';
import { Api } from './Apiv2.class';
import { SearchSyntaxHighlighting } from './SearchSyntaxHighlighting.class';
declare let key: any; // eslint-disable-line @typescript-eslint/no-explicit-any

document.addEventListener('DOMContentLoaded', () => {
  if (!document.getElementById('info')) {
    return;
  }
  const about = document.getElementById('info').dataset;
  // only run in show mode
  if (about.page !== 'show') {
    return;
  }
  const params = new URLSearchParams(document.location.search.slice(1));

  // SEARCH RELATED CODE
  const searchInput = document.getElementById('extendedArea') as HTMLInputElement;
  SearchSyntaxHighlighting.init(searchInput);

  // TomSelect for extra fields search select
  new TomSelect('#metakey', {
    plugins: [
      'dropdown_input',
      'remove_button',
    ],
  });
  // a filter helper can be a select or an input (for date and extrafield), so we need a function to get its value
  function getFilterValueFromElement(element: HTMLElement): string {
    const escapeDoubleQuotes = (string: string): string => {
      // escape double quotes if not already escaped
      return string.replace(/(?<!\\)"/g, '\\"');
    };
    const handleDate = (): string => {
      const date = (document.getElementById('date') as HTMLInputElement).value;
      const dateTo = (document.getElementById('dateTo') as HTMLInputElement).value;
      const dateOperatorEl = document.getElementById('dateOperator') as HTMLSelectElement;
      const dateOperator = dateOperatorEl.options[dateOperatorEl.selectedIndex].value;
      if (date === '') {
        return '';
      }
      if (dateTo === '') {
        return dateOperator + date;
      }
      return date + '..' + dateTo;
    };
    const handleMetadata = (): string => {
      const metakeyEl = document.getElementById('metakey') as HTMLSelectElement;
      const metakey = metakeyEl.options[metakeyEl.selectedIndex].value;
      const metavalue = (document.getElementById('metavalue') as HTMLInputElement).value;
      if (metakey === '' || metavalue === '') {
        return '';
      }
      const keyQuotes = getQuotes(metakey);
      const valueQuotes = getQuotes(metavalue);
      return keyQuotes + escapeDoubleQuotes(metakey) + keyQuotes + ':' + valueQuotes + escapeDoubleQuotes(metavalue) + valueQuotes;
    };
    if (element instanceof HTMLSelectElement) {
      // clear action
      if (element.options[element.selectedIndex].dataset.action === 'clear') {
        return '';
      }
      if (element.id === 'dateOperator') {
        return handleDate();
      }
      if (element.id === 'metakey') {
        return handleMetadata();
      }
      return escapeDoubleQuotes(element.options[element.selectedIndex].text);
    }
    if (element instanceof HTMLInputElement) {
      if (element.id === 'date') {
        return handleDate();
      }
      if (element.id === 'dateTo') {
        return handleDate();
      }
      if (element.id === 'metavalue') {
        return handleMetadata();
      }
    }
    return '😶';
  }

  // don't add quotes unless we need them (space or some special chars exist)
  function getQuotes(filterValue: string): string {
    let quotes = '';
    if ([' ', '&', '|', '!', ':', '(', ')', '\'', '"'].some(value => filterValue.includes(value))) {
      quotes = '"';
    }
    return quotes;
  }

  // add a change event listener to all elements that helps constructing the query string
  document.querySelectorAll('.filterHelper').forEach(el => {
    el.addEventListener('change', event => {
      const elem = event.currentTarget as HTMLElement;
      const curVal = (document.getElementById('extendedArea') as HTMLInputElement).value;
      const hasInput = curVal.length != 0;
      const hasSpace = curVal.endsWith(' ');
      const addSpace = hasInput ? (hasSpace ? '' : ' ') : '';
      let filterName = elem.dataset.filter ? elem.dataset.filter : (elem as HTMLInputElement).name;

      // look if the filter key already exists in the search input
      // paste the regex on regex101.com to understand it
      const baseRegex = '(?:(?:"((?:\\\\"|(?:(?!")).)+)")|(?:\'((?:\\\\\'|(?:(?!\')).)+)\')|([^\\s:\'"()&|!]+))';
      const operatorRegex = '(?:[<>]=?|!?=)?';
      let valueRegex = baseRegex;

      if (filterName === 'date') {
        // date can use operator
        valueRegex = operatorRegex + baseRegex;
      }
      if (filterName === 'extrafield') {
        // extrafield has key and value so we need the regex above twice
        valueRegex = baseRegex + ':' + baseRegex;
      }
      const regex = new RegExp(filterName + ':' + valueRegex + '\\s?');
      const found = curVal.match(regex);
      // default value is clearing everything
      let filter = '';
      let filterValue = getFilterValueFromElement(elem as HTMLElement);
      let quotes = getQuotes(filterValue);
      // but if we have a correct value, we add the filter
      if (filterValue !== '') {
        if (filterName === 'date') {
          quotes = '';
        }

        if (filterName === '(?:author|group)') {
          filterName = filterValue.split(':')[0];
          filterValue = filterValue.substring(filterName.length + 1);
        }

        filter = filterName + ':' + quotes + filterValue + quotes;

        if (filterName === 'extrafield') {
          filter = `${filterName}:${filterValue}`;
        }
      }

      // add additional filter at cursor position
      if (key.ctrl || key.command) {
        const pos = searchInput.selectionStart;
        const val = searchInput.value;
        const start = val.substring(0, pos);
        const end = val.substring(pos, val.length);
        const hasSpaceBefore = val.substring(pos - 1, pos) === ' ';
        const hasSpaceAfter = val.substring(pos, pos + 1) === ' ';
        const insert = (hasSpaceBefore ? '' : pos === 0 ? '' : ' ') + filter + (hasSpaceAfter ? '' : ' ');
        searchInput.value = start + insert + end;
        SearchSyntaxHighlighting.update(searchInput.value);
        return;
      }

      if (found) {
        searchInput.value = curVal.replace(regex, filter + (filter === '' ? '' : ' '));
      } else {
        searchInput.value = searchInput.value + addSpace + filter;
      }
      SearchSyntaxHighlighting.update(searchInput.value);
    });
  });
  document.querySelectorAll('.filterAuto').forEach(el => {
    el.addEventListener('change', event => {
      const url = new URL(window.location.href);
      const elem = event.target as HTMLSelectElement;
      const elemValue = elem.options[elem.selectedIndex].value;
      url.searchParams.set(elem.name, elemValue);
      // also add it to the main input form
      addHiddenInputToMainSearchForm(elem.name, elemValue);

      window.history.replaceState({}, '', url.toString());
      reloadEntitiesShow();
    });
  });
  // END SEARCH RELATED CODE

  function addHiddenInputToMainSearchForm(name: string, value: string): void
  {
    const form = document.getElementById('mainSearchForm');
    const hiddenInputId = `${name}_hiddenInput`;
    document.getElementById(hiddenInputId)?.remove();
    const input = document.createElement('input');
    input.hidden = true;
    input.name = name;
    input.value = value;
    input.id = hiddenInputId;
    form.appendChild(input);
  }

  const entity = getEntity();
  const FavTagC = new FavTag();
  const ApiC = new Api();

  // background color for selected entities
  const bgColor = '#c4f9ff';

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

  // get query param value as number
  function getParamNum(param: string): number {
    const params = new URLSearchParams(document.location.search);
    let val = params.get(param);
    if (!val) {
      val = '0';
    }
    return parseInt(val, 10);
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
      const checked = getCheckedBoxes();
      if (checked.length === 0) {
        notifNothingSelected();
        return;
      }
      const format = el.value;
      // reset selection so button can be used again with same format
      el.selectedIndex = 0;
      window.location.href = `make.php?format=${format}&type=${entity.type}&id=${checked.map(value => value.id).join('+')}`;
    }
  });

  // the "load more" button triggers a reloading of div#showModeContent
  // so we keep track of the expanded and selected entities
  function getExpandedAndSelectedEntities(): void {
    const expanded = (document.querySelector('[data-action="expand-all-entities"]') as HTMLLinkElement).dataset.status === 'opened';
    const expendedEntities: string[] = [];
    const selectedEntities: string[] = [];
    document.querySelectorAll('[data-action="checkbox-entity"]').forEach((item: HTMLInputElement) => {
      if (item.checked) {
        selectedEntities.push(item.dataset.id);
      }
      if (!document.getElementById(item.dataset.randomid).hidden) {
        expendedEntities.push(item.dataset.id);
      }
    });
    document.getElementById('showModeContent').dataset.expandedAndSelectedEntities = JSON.stringify({expanded, selectedEntities, expendedEntities});
  }

  function setExpandedAndSelectedEntities(): void {
    const state = JSON.parse(document.getElementById('showModeContent').dataset.expandedAndSelectedEntities);
    if (state.expanded) {
      const linkEl = document.querySelector('[data-action="expand-all-entities"]') as HTMLLinkElement;
      linkEl.dataset.status = 'opened';
      document.querySelectorAll('[data-action="toggle-body"]').forEach((toggleButton: HTMLButtonElement) => {
        toggleButton.click();
      });
    }
    if (state.selectedEntities.length > 0) {
      document.getElementById('withSelected').classList.remove('d-none');
    }
    document.querySelectorAll('[data-action="checkbox-entity"]').forEach((item: HTMLInputElement) => {
      if (state.selectedEntities.includes(item.dataset.id)) {
        item.click();
      }
      if (!state.expanded && state.expendedEntities.includes(item.dataset.id)) {
        (document.querySelector(`[data-action="toggle-body"][data-id="${item.dataset.id}"]`) as HTMLButtonElement).click();
      }
    });
  }

  /////////////////////////
  // MAIN CLICK LISTENER //
  /////////////////////////
  document.getElementById('container').addEventListener('click', event => {
    const el = (event.target as HTMLElement);
    const params = new URLSearchParams(document.location.search);
    // LOAD MORE
    if (el.matches('[data-action="load-more"]')) {
      // we keep track of the expanded and selected entities
      getExpandedAndSelectedEntities();
      // NOTE: in an ideal world, we can request the delta elements in json via api and inject them in page
      // this would avoid having to re-query all items every time, especially after a few clicks where limit is a few hundreds, might bring strain on mysql servers
      // so here the strategy is simply to increase the "limit" to show more stuff

      // we want to know if the newly applied limit actually brought new items
      // because if not, we disable the button
      // so simply count them
      const previousNumber = document.querySelectorAll('.entity').length;
      // this will be 0 if the button has not been clicked yet
      const queryLimit = getParamNum('limit');
      const usualLimit = parseInt(about.limit, 10);
      let newLimit = queryLimit + usualLimit;
      // handle edge case for first click
      if (queryLimit < usualLimit) {
        newLimit = usualLimit * 2;
      }
      params.set('limit', String(newLimit));
      history.replaceState(null, '', `?${params.toString()}`);
      reloadEntitiesShow().then(() => {
        // expand and select what was expanded and selected
        setExpandedAndSelectedEntities();
        // remove Load more button if no new entries appeared
        const newNumber = document.querySelectorAll('.entity').length;
        if (previousNumber === newNumber) {
          document.getElementById('loadMoreBtn').remove();
        }
      });

    // SAVE MULTI CHANGES
    } else if (el.matches('[data-action="save-multi-changes"]')) {

      // prevent form submission
      event.preventDefault();

      // get the item id of all checked boxes
      const checked = getCheckedBoxes();
      if (checked.length === 0) {
        notifNothingSelected();
        return;
      }
      // display a warning with the number of impacted entries
      if (!confirm(i18next.t('multi-changes-confirm', { num: checked.length }))) {
        return;
      }
      (el as HTMLButtonElement).disabled = true;
      ApiC.notifOnSaved = false;
      const ajaxs = [];
      const form = document.getElementById('multiChangesForm');
      const params = collectForm(form);
      clearForm(form);
      ['canread', 'canwrite'].forEach(can => {
        // TODO replace with hasOwn when https://github.com/microsoft/TypeScript/issues/44253 is closed
        if (Object.prototype.hasOwnProperty.call(params, can)) {
          params[can] = permissionsToJson(parseInt(params[can], 10), []);
        }
      });
      checked.forEach(chk => {
        const paramsCopy = Object.assign({}, params);
        // they do not have all the same endpoint: handle tags and links the generic patch method
        for (const key in paramsCopy) {
          if (key === 'tags') {
            ajaxs.push(ApiC.post(`${entity.type}/${chk.id}/${Model.Tag}`, {tag: paramsCopy[key]}));
            delete paramsCopy[key];
          } else if (['items_links', 'experiments_links'].includes(key)) {
            ajaxs.push(ApiC.post(`${entity.type}/${chk.id}/${key}/${parseInt(paramsCopy[key], 10)}`));
            delete paramsCopy[key];
          }
        }
        // patch whatever is left
        if (Object.entries(paramsCopy).length > 0) {
          ajaxs.push(ApiC.patch(`${entity.type}/${chk.id}`, paramsCopy));
        }
      });
      // reload the page once it's done
      Promise.all(ajaxs).then(() => {
        notif({msg: i18next.t('saved'), res: true});
        ApiC.notifOnSaved = true;
        reloadEntitiesShow();
      });

    } else if (el.matches('[data-action="clear-form"]')) {
      clearForm(document.getElementById(el.dataset.target));

    // TOGGLE FAVTAGS PANEL
    } else if (el.matches('[data-action="toggle-favtags"]')) {
      FavTagC.toggle();

    // TOGGLE DISPLAY
    } else if (el.matches('[data-action="toggle-items-layout"]')) {
      ApiC.notifOnSaved = false;
      ApiC.getJson(`${Model.User}/me`).then(json => {
        let target = 'it';
        if (json['display_mode'] === 'it') {
          target = 'tb';
        }
        ApiC.patch(`${Model.User}/me`, {'display_mode': target}).then(() => {
          reloadEntitiesShow();
        });
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
      reloadEntitiesShow(el.dataset.tag);

    // remove a favtag
    } else if (el.matches('[data-action="destroy-favtags"]')) {
      FavTagC.destroy(parseInt(el.dataset.id, 10)).then(() => reloadElements(['favtagsTagsDiv']));

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
      if ((el as HTMLInputElement).checked) {
        (el.closest('.entity') as HTMLElement).style.backgroundColor = bgColor;
      } else {
        (el.closest('.entity') as HTMLElement).style.backgroundColor = '';
      }
      // show invert select if any checkbox is selected
      const anyChecked = document.querySelectorAll('[data-action="checkbox-entity"]:checked').length > 0;
      const invertSelections = document.querySelector('a[data-action="invert-entities-selection"]') as HTMLAnchorElement;
      if (anyChecked) {
        invertSelections?.removeAttribute('hidden');
      } else {
        invertSelections?.setAttribute('hidden', 'hidden');
        // Remove withSelected actions if there are no more checked checkboxes
        document.getElementById('withSelected')?.classList.add('d-none');
      }

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
        document.querySelectorAll('.entity input[type=checkbox]').forEach(box => {
          (box as HTMLInputElement).checked = true;
          (box.closest('.entity') as HTMLElement).style.backgroundColor = bgColor;
        });
        document.getElementById('withSelected').classList.remove('d-none');
        el.dataset.target = 'unselect';
      } else {
        document.querySelectorAll('.entity input[type=checkbox]').forEach(box => {
          (box as HTMLInputElement).checked = false;
          (box.closest('.entity') as HTMLElement).style.backgroundColor = '';
        });
        el.dataset.target = 'select';
        document.getElementById('withSelected').classList.add('d-none');
      }
      const icon = el.querySelector('i');
      icon.classList.toggle('fa-square');
      icon.classList.toggle('fa-square-check');
      el.nextElementSibling.removeAttribute('hidden');

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
      // Remove withSelected actions if there are no more checked checkboxes
      const anyChecked = document.querySelectorAll('[data-action="checkbox-entity"]:checked').length > 0;
      const withSelected = document.getElementById('withSelected') as HTMLDivElement;
      if (anyChecked) {
        withSelected.classList.remove('d-none');
      } else {
        withSelected.classList.add('d-none');
      }

    // PATCH ACTIONS FOR CHECKED BOXES : lock, unlock, timestamp, archive
    } else if (el.matches('[data-action="patch-selected-entities"]')) {
      // get the item id of all checked boxes
      const checked = getCheckedBoxes();
      if (checked.length === 0) {
        notifNothingSelected();
        return;
      }
      const action = <Action>el.dataset.what;
      // loop over it and patch with selected action
      const results = [];
      checked.forEach(chk => {
        results.push(ApiC.patch(`${entity.type}/${chk.id}`, {action: action}));
      });
      Promise.all(results).then(() => reloadEntitiesShow());

    // THE DELETE BUTTON FOR CHECKED BOXES
    } else if (el.matches('[data-action="destroy-selected-entities"]')) {
      // get the item id of all checked boxes
      const checked = getCheckedBoxes();
      if (checked.length === 0) {
        notifNothingSelected();
        return;
      }
      // ask for confirmation
      if (!confirm(i18next.t('generic-delete-warning'))) {
        return;
      }
      // loop on it and delete stuff (use curly braces to avoid implicit return)
      checked.forEach(chk => {ApiC.delete(`${entity.type}/${chk.id}`).then(() => document.getElementById(`parent_${chk.randomid}`).remove());});
    }
  });

  // we don't want the favtags opener on search page
  // when a search is done, about.page will be show
  // so check for the type param in url that will be present on search page
  if (!params.get('type')) {
    document.getElementById('sidepanel-buttons').removeAttribute('hidden');
  }

  // FAVTAGS PANEL
  if (localStorage.getItem('isfavtagsOpen') === '1') {
    FavTagC.toggle();
  }

  new TomSelect('#tagFilter', {
    onInitialize: () => {
      // remove the placeholder input once the select is ready
      document.getElementById('tagFilterPlaceholder').remove();
    },
    onChange: value => {
      const url = new URL(window.location.href);
      url.searchParams.delete('tags[]');
      value.forEach(tag => {
        params.append('tags[]', tag);
        url.searchParams.append('tags[]', tag);
      });
      if (value.length === 0) {
        url.searchParams.delete('tags[]');
      }
      addHiddenInputToMainSearchForm('tags[]', value.toString());

      window.history.replaceState({}, '', url.toString());
      reloadEntitiesShow();
    },
    plugins: {
      checkbox_options: {
        checkedClassNames: ['ts-checked'],
        uncheckedClassNames: ['ts-unchecked'],
      },
      clear_button: {},
      dropdown_input: {},
      no_active_items: {},
      remove_button: {},
    },
  });
});
