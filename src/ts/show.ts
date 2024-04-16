/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */
import {
  getCheckedBoxes,
  getEntity,
  notif,
  permissionsToJson,
  reloadElement,
  reloadEntitiesShow,
  TomSelect,
} from './misc';
import { Action, Model } from './interfaces';
import 'bootstrap/js/src/modal.js';
import i18next from 'i18next';
import EntityClass from './Entity.class';
import FavTag from './FavTag.class';
import { Api } from './Apiv2.class';
import $ from 'jquery';

document.addEventListener('DOMContentLoaded', () => {
  if (!document.getElementById('info')) {
    return;
  }
  const about = document.getElementById('info').dataset;
  // only run in show mode or on search page (which is kinda show mode too)
  const pages = ['show', 'search'];
  if (!pages.includes(about.page)) {
    return;
  }

  const entity = getEntity();
  const EntityC = new EntityClass(entity.type);
  const FavTagC = new FavTag();
  const ApiC = new Api();

  // THE CHECKBOXES
  const nothingSelectedError = {
    'msg': i18next.t('nothing-selected'),
    'res': false,
  };

  // background color for selected entities
  const bgColor = '#c4f9ff';

  if (document.getElementById('favtagsPanel')) {
    document.getElementById('favtagsPanel').addEventListener('keyup', event => {
      const el = (event.target as HTMLInputElement);
      const query = el.value;
      if (el.matches('[data-action="favtags-search"]')) {
        // find all links that are endpoints
        document.querySelectorAll('[data-action="add-tag-filter"]').forEach(el => {
          // begin by showing all so they don't stay hidden
          el.removeAttribute('hidden');
          // now simply hide the ones that don't match the query
          if (!(el as HTMLElement).innerText.toLowerCase().includes(query)) {
            el.setAttribute('hidden', '');
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
        notif(nothingSelectedError);
        return;
      }
      const format = el.value;
      // reset selection so button can be used again with same format
      el.selectedIndex = 0;
      window.location.href = `make.php?format=${format}&type=${about.type}&id=${checked.map(value => value.id).join('+')}`;

    // UPDATE CATEGORY OR STATUS
    } else if (el.matches('[data-action="update-catstat-selected-entities"]')) {
      const ajaxs = [];
      // get the item id of all checked boxes
      const checked = getCheckedBoxes();
      if (checked.length === 0) {
        notif(nothingSelectedError);
        return;
      }
      // loop on it and update the status/item type
      checked.forEach(chk => {
        const params = {};
        params[el.dataset.target] = el.value;
        ajaxs.push(ApiC.patch(`${about.type}/${chk.id}`, params));
      });
      // reload the page once it's done
      // a simple reload would not work here
      // we need to use when/then
      $.when.apply(null, ajaxs).then(function() {
        reloadEntitiesShow();
      });
      notif({'msg': 'Saved', 'res': true});

    // UPDATE VISIBILITY
    } else if (el.matches('[data-action="update-visibility-selected-entities"]')) {
      const ajaxs = [];
      // get the item id of all checked boxes
      const checked = getCheckedBoxes();
      if (checked.length === 0) {
        notif(nothingSelectedError);
        return;
      }
      // loop on it and update the status/item type
      checked.forEach(chk => {
        ajaxs.push(ApiC.patch(`${about.type}/${chk.id}`, {'canread': permissionsToJson(parseInt(el.value, 10), [])}));
      });
      // reload the page once it's done
      // a simple reload would not work here
      // we need to use when/then
      $.when.apply(null, ajaxs).then(function() {
        reloadEntitiesShow();
      });
      notif({'msg': 'Saved', 'res': true});
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
      linkEl.textContent = linkEl.dataset.collapse;
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

    // TOGGLE PIN
    } else if (el.matches('[data-action="toggle-pin"]')) {
      ApiC.patch(`${entity.type}/${parseInt(el.dataset.id, 10)}`, {'action': Action.Pin}).then(() => el.closest('.entity').remove());

    // remove a favtag
    } else if (el.matches('[data-action="destroy-favtags"]')) {
      FavTagC.destroy(parseInt(el.dataset.id, 10)).then(() => reloadElement('favtagsTagsDiv'));

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
      ['advancedSelectOptions', 'withSelected'].forEach(id => {
        const el = document.getElementById(id);
        const scroll = el.classList.contains('d-none');
        el.classList.remove('d-none');
        if (id === 'withSelected' && scroll && el.getBoundingClientRect().bottom > 0) {
          window.scrollBy({top: el.offsetHeight, behavior: 'instant'});
        }
      });
      if ((el as HTMLInputElement).checked) {
        (el.closest('.entity') as HTMLElement).style.backgroundColor = bgColor;
      } else {
        (el.closest('.entity') as HTMLElement).style.backgroundColor = '';
      }

    // EXPAND ALL
    } else if (el.matches('[data-action="expand-all-entities"]')) {
      event.preventDefault();
      if (el.dataset.status === 'closed') {
        el.dataset.status = 'opened';
        el.innerText = el.dataset.collapse;
      } else {
        el.dataset.status = 'closed';
        el.innerText = el.dataset.expand;
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

    // SELECT ALL CHECKBOXES
    } else if (el.matches('[data-action="select-all-entities"]')) {
      event.preventDefault();
      // check all boxes and set background color
      document.querySelectorAll('.entity input[type=checkbox]').forEach(box => {
        (box as HTMLInputElement).checked = true;
        (box.closest('.entity') as HTMLElement).style.backgroundColor = bgColor;
      });
      // show advanced options and withSelected menu
      ['advancedSelectOptions', 'withSelected'].forEach(id => {
        document.getElementById(id).classList.remove('d-none');
      });

    // UNSELECT ALL CHECKBOXES
    } else if (el.matches('[data-action="unselect-all-entities"]')) {
      event.preventDefault();
      document.querySelectorAll('.entity input[type=checkbox]').forEach(box => {
        (box as HTMLInputElement).checked = false;
        (box.closest('.entity') as HTMLElement).style.backgroundColor = '';
      });
      // hide menu
      ['advancedSelectOptions', 'withSelected'].forEach(id => {
        document.getElementById(id).classList.add('d-none');
      });

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


    // THE LOCK BUTTON FOR CHECKED BOXES
    } else if (el.matches('[data-action="lock-selected-entities"]')) {
      // get the item id of all checked boxes
      const checked = getCheckedBoxes();
      if (checked.length === 0) {
        notif(nothingSelectedError);
        return;
      }

      // loop over it and lock entities
      const results = [];
      checked.forEach(chk => {
        results.push(EntityC.patchAction(chk.id, Action.Lock));
      });

      Promise.all(results).then(() => {
        reloadEntitiesShow();
      });


    // THE TIMESTAMP BUTTON FOR CHECKED BOXES
    } else if (el.matches('[data-action="timestamp-selected-entities"]')) {
      const checked = getCheckedBoxes();
      if (checked.length === 0) {
        notif(nothingSelectedError);
        return;
      }
      // loop on it and timestamp it
      checked.forEach(chk => {
        EntityC.patchAction(chk.id, Action.Timestamp).then(() => reloadEntitiesShow());
      });

    // THE ARCHIVE BUTTON FOR CHECKED BOXES
    } else if (el.matches('[data-action="archive-selected-entities"]')) {
      // get the item id of all checked boxes
      const checked = getCheckedBoxes();
      if (checked.length === 0) {
        notif(nothingSelectedError);
        return;
      }

      // loop over it and lock entities
      const results = [];
      checked.forEach(chk => {
        results.push(EntityC.patchAction(chk.id, Action.Archive));
      });

      Promise.all(results).then(() => {
        reloadEntitiesShow();
      });

    // THE DELETE BUTTON FOR CHECKED BOXES
    } else if (el.matches('[data-action="destroy-selected-entities"]')) {
      // get the item id of all checked boxes
      const checked = getCheckedBoxes();
      if (checked.length === 0) {
        notif(nothingSelectedError);
        return;
      }
      // ask for confirmation
      if (!confirm(i18next.t('generic-delete-warning'))) {
        return;
      }
      // loop on it and delete stuff (use curly braces to avoid implicit return)
      checked.forEach(chk => {EntityC.destroy(chk.id).then(() => document.getElementById(`parent_${chk.randomid}`).remove());});
    }
  });

  // we don't want the favtags opener on search page
  // when a search is done, about.page will be show
  // so check for the type param in url that will be present on search page
  const params = new URLSearchParams(document.location.search.slice(1));
  if (!params.get('type')) {
    document.getElementById('sidepanel-buttons').removeAttribute('hidden');
  }

  // FAVTAGS PANEL
  if (localStorage.getItem('isfavtagsOpen') === '1') {
    FavTagC.toggle();
  }

  new TomSelect('#tagFilter', {
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
