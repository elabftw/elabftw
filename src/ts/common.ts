/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */
import $ from 'jquery';
import { Api } from './Apiv2.class';
import { Malle, InputType, Action as MalleAction } from '@deltablot/malle';
import 'bootstrap/js/src/modal.js';
import {
  adjustHiddenState,
  clearForm,
  collectForm,
  escapeExtendedQuery,
  generateMetadataLink,
  getEntity,
  listenTrigger,
  makeSortableGreatAgain,
  mkSpin,
  mkSpinStop,
  notifError,
  permissionsToJson,
  relativeMoment,
  reloadElements,
  reloadEntitiesShow,
  replaceWithTitle,
  toggleEditCompound,
  toggleGrayClasses,
  toggleIcon,
  TomSelect,
  updateEntityBody,
} from './misc';
import i18next from 'i18next';
import { Metadata } from './Metadata.class';
import { DateTime } from 'luxon';
import { Action, EntityType, Model, Target } from './interfaces';
import { MathJaxObject } from 'mathjax-full/js/components/startup';
declare const MathJax: MathJaxObject;
import 'bootstrap-markdown-fa5/js/bootstrap-markdown';
import 'bootstrap-markdown-fa5/locale/bootstrap-markdown.de.js';
import 'bootstrap-markdown-fa5/locale/bootstrap-markdown.es.js';
import 'bootstrap-markdown-fa5/locale/bootstrap-markdown.fr.js';
import 'bootstrap-markdown-fa5/locale/bootstrap-markdown.it.js';
import 'bootstrap-markdown-fa5/locale/bootstrap-markdown.ja.js';
import 'bootstrap-markdown-fa5/locale/bootstrap-markdown.kr.js';
import 'bootstrap-markdown-fa5/locale/bootstrap-markdown.nl.js';
import 'bootstrap-markdown-fa5/locale/bootstrap-markdown.pl.js';
import 'bootstrap-markdown-fa5/locale/bootstrap-markdown.ptBR.js';
import 'bootstrap-markdown-fa5/locale/bootstrap-markdown.ru.js';
import 'bootstrap-markdown-fa5/locale/bootstrap-markdown.sl.js';
import 'bootstrap-markdown-fa5/locale/bootstrap-markdown.sv.js';
import 'bootstrap-markdown-fa5/locale/bootstrap-markdown.zh.js';
import TableSorting from './TableSorting.class';
import { KeyboardShortcuts } from './KeyboardShortcuts.class';
import JsonEditorHelper from './JsonEditorHelper.class';
import { Counter } from './Counter.class';
import { getEditor } from './Editor.class';

document.addEventListener('DOMContentLoaded', () => {
  // HEARTBEAT
  // this function is to check periodically that we are still authenticated
  // and show a message if we the session is not valid anymore but we are still on a page requiring auth
  // only run if we are an auth user by checking the presence of this element in the footer
  if (document.getElementById('is-auth')) {
    // check every 5 minutes
    const heartRate = 300000;
    setInterval(() => {
      fetch('app/controllers/HeartBeat.php').then(response => {
        if (!response.ok) {
          localStorage.clear();
          alert('Your session expired!');
          window.location.replace('login.php');
        }
      }).catch(error => alert(error));
    }, heartRate);
  }

  const ApiC = new Api();

  const TableSortingC = new TableSorting();
  TableSortingC.init();

  const userPrefs = document.getElementById('user-prefs').dataset;
  // set the language for js translated strings
  i18next.changeLanguage(userPrefs.lang);

  makeSortableGreatAgain();

  if (userPrefs.scDisabled === '0') {
    const kbd = new KeyboardShortcuts(
      userPrefs.scCreate,
      userPrefs.scEdit,
      userPrefs.scTodolist,
      userPrefs.scFavorite,
      userPrefs.scSearch,
    );
    kbd.init();
  }

  // ACTIVATE REACTIVE COUNT OF .COUNTABLE ITEMS
  document.querySelectorAll('[data-count-for]').forEach((container: HTMLElement) => new Counter(container));

  // BACK TO TOP BUTTON
  const btn = document.createElement('button');
  btn.type = 'button';
  btn.dataset.action = 'scroll-top';
  // make it look like a button, and on the right side of the screen, not too close from the bottom
  btn.classList.add('btn', 'btn-neutral', 'floating-middle-right');
  // element is invisible at first so we can make it visible so it triggers a css transition and appears progressively
  btn.style.opacity = '0';
  // will not be shown for small screens, only large ones
  btn.classList.add('d-none', 'd-xl-inline', 'd-lg-inline');
  // the button is an up arrow
  btn.innerHTML = '<i class="fas fa-arrow-up"></i>';
  // give it an id so we can remove it easily
  btn.id = 'backToTopButton';
  btn.setAttribute('aria-label', 'Back to top');
  btn.title = 'Back to top';

  // called when viewport approaches the footer
  const intersectionCallback = (entries): void => {
    entries.forEach(entry => {
      if (entry.isIntersecting && document.getElementById('backToTopButton')) {
        // we're near the top of the screen, remove the button if it's here
        document.getElementById('backToTopButton').remove();
      } else if (!entry.isIntersecting && !document.getElementById('backToTopButton')){ // user scrolled the trigger out AND button is not here
        const addedBtn = document.getElementById('container').appendChild(btn);
        // here we use requestAnimationFrame or the browser won't see the change and the css transition won't be triggered
        requestAnimationFrame(() => {
          addedBtn.style.opacity = '100';
        });
      }
    });
  };

  const observer = new IntersectionObserver(intersectionCallback);
  observer.observe(document.getElementById('scrollTopBtnAnchor'));
  // END BACK TO TOP BUTTON

  listenTrigger();

  adjustHiddenState();

  // show human friendly moments
  relativeMoment();

  replaceWithTitle();

  // look for elements that should have focus
  const needFocus = (document.querySelector('[data-focus="1"]') as HTMLInputElement);
  if (needFocus) {
    needFocus.focus();
  }

  // Listen for malleable columns
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

  // Listen for malleable entity title
  new Malle({
    after: (original, _, value) => {
      // special case for title: update the page title on update
      if (original.id === 'documentTitle') {
        document.title = value;
      }
      return true;
    },
    onEdit: (original, _, input) => {
      if (original.innerText === 'unset') {
        input.value = '';
        original.classList.remove('font-italic');
      }
      return true;
    },
    inputClasses: ['form-control'],
    fun: (value, original) => {
      const params = {};
      params[original.dataset.target] = value;
      return ApiC.patch(`${original.dataset.endpoint}/${original.dataset.id}`, params)
        .then(res => res.json())
        .then(json => json[original.dataset.target]);
    },
    listenOn: '.malleableTitle',
    returnedValueIsTrustedHtml: false,
    onBlur: MalleAction.Submit,
    tooltip: i18next.t('click-to-edit'),
  }).listen();

  // Listen for malleable qty_unit - we need a specific code to add the select options
  new Malle({
    cancel : i18next.t('cancel'),
    cancelClasses: ['btn', 'btn-danger', 'mt-2', 'ml-1'],
    inputClasses: ['form-control'],
    inputType: InputType.Select,
    selectOptions: [
      {selected: false, text: '•', value: '•'},
      {selected: false, text: 'μL', value: 'μL'},
      {selected: false, text: 'mL', value: 'mL'},
      {selected: false, text: 'L', value: 'L'},
      {selected: false, text: 'μg', value: 'μg'},
      {selected: false, text: 'mg', value: 'mg'},
      {selected: false, text: 'g', value: 'g'},
      {selected: false, text: 'kg', value: 'kg'},
    ],
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

  // tom-select for team selection on login and register page, and idp selection
  ['init_team_select', 'team', 'idp_login_select'].forEach(id =>{
    if (document.getElementById(id)) {
      new TomSelect(`#${id}`, {
        plugins: [
          'dropdown_input',
          'no_active_items',
        ],
      });
    }
  });

  // validate the form upon change. fix #451
  // add to the input itself, not the form for more flexibility
  // for instance the tags input allow multiple selection, so we don't want to submit on change
  document.querySelectorAll('.autosubmit').forEach(el => {
    el.addEventListener('change', event => {
      // look for all the select that have an empty value and ignore them by setting the name to empty string
      // this is done to avoid the "extended" being repeated with the last one possibly empty taking over the first one
      document.querySelectorAll('select.autosubmit').forEach((sel: HTMLSelectElement) => {
        if (sel.options[sel.selectedIndex].value === '') {
          // using empty name is better than sel.disabled to avoid visual glitch during submit
          sel.name = '';
        }
      });
      (event.currentTarget as HTMLElement).closest('form').submit();
    });
  });

  /**
   * Add listeners for filter bar on top of a table
   * The "filter" data attribute value is the id of the tbody element with rows to filter
   */
  document.querySelectorAll('input[data-filter-target]').forEach((input: HTMLInputElement) => {
    const target = document.getElementById(input.dataset.filterTarget);
    const targetType = input.dataset.targetType;
    // FIRST LISTENER is to filter the rows
    input.addEventListener('keyup', () => {
      target.querySelectorAll(`#${input.dataset.filterTarget} ${targetType}`).forEach((row: HTMLTableRowElement|HTMLUListElement) => {
        // show or hide the row if it matches the query
        if (row.innerText.toLowerCase().includes(input.value.toLowerCase())) {
          row.removeAttribute('hidden');
        } else {
          row.hidden = true;
        }
      });
    });
    // SECOND LISTENER on the clear input button
    input.nextElementSibling.addEventListener('click', () => {
      input.value = '';
      input.focus();
      target.querySelectorAll(`#${input.dataset.filterTarget} ${targetType}`).forEach((row: HTMLTableRowElement) => {
        row.removeAttribute('hidden');
      });
    });
  });

  function generateTable(data: Record<string, string | null>) {
    const table = document.createElement('table');
    table.classList.add('table');
    const tbody = document.createElement('tbody');

    for (const [key, value] of Object.entries(data)) {
      const row = document.createElement('tr');
      const cellKey = document.createElement('td');
      cellKey.textContent = key;
      row.appendChild(cellKey);
      const cellValue: HTMLTableCellElement = document.createElement('td');
      cellValue.textContent = value !== null ? value : 'N/A';
      row.appendChild(cellValue);
      tbody.appendChild(row);
    }

    table.appendChild(tbody);
    return table;
  }

  /**
  * Add an event listener on wheel event to prevent scrolling down with a number input selected.
  * Without this, the number will change to the next integer and information entered is lost.
  * Use the "passive" option to avoid impact on performance.
  */
  document.addEventListener('wheel', () => {
    const activeElement = document.activeElement;
    if (activeElement instanceof HTMLInputElement && activeElement.type === 'number') {
      activeElement.blur();
    }
  }, { passive: true });


  /**
   * make 'toggle next' elements accessible by keyboard
   * redirect to click event
   */
  document.getElementById('container').addEventListener('keydown', event => {
    const el = event.target as HTMLElement;
    if (el.matches('[data-action="toggle-next"]')
        && (event.key === ' ' || event.key === 'Enter' || event.key === 'Spacebar')) {
      el.dispatchEvent(new Event('click', { bubbles: true, cancelable: false }));
      event.preventDefault();
    }
  });

  /**
   * MAIN click event listener bound to container
   * this will listen for click events on the container and if the element
   * matches a known action then that action is triggered
   */
  document.getElementById('container').addEventListener('click', event => {
    const el = event.target as HTMLElement;
    // SHOW PRIVACY POLICY
    if (el.matches('[data-action="show-policy"]')) {
      fetch('app/controllers/UnauthRequestHandler.php').then(resp => resp.json()).then(json => {
        const policy = json[el.dataset.policy];
        let title: string;
        // TODO i18n
        switch (el.dataset.policy) {
        case 'tos':
          title = json.tos_name;
          break;
        case 'a11y':
          title = json.a11y_name;
          break;
        case 'legal':
          title = json.legal_name;
          break;
        default:
          title = json.privacy_name;
        }
        (document.getElementById('policiesModalLabel') as HTMLHeadElement).innerText = title;
        (document.getElementById('policiesModalBody') as HTMLDivElement).innerHTML = policy;
        // modal plugin requires jquery
        $('#policiesModal').modal('toggle');
      });
    } else if (el.matches('[data-reload-on-click]')) {
      reloadElements([el.dataset.reloadOnClick]).then(() => relativeMoment());

    // SWITCH EDITOR
    } else if (el.matches('[data-action="switch-editor"]')) {
      getEditor().switch(getEntity()).then(() => window.location.reload());

    } else if (el.matches('[data-action="insert-param-and-reload-show"]')) {
      const params = new URLSearchParams(document.location.search.slice(1));
      params.set(el.dataset.key, el.dataset.value);
      window.history.replaceState({}, '', `?${params.toString()}`);
      reloadEntitiesShow();

    } else if (el.matches('[data-action="add-query-filter"]')) {
      const params = new URLSearchParams(document.location.search.substring(1));
      params.set(el.dataset.key, el.dataset.value);
      // make sure to set the offset to 0, see #4826
      params.set('offset', '0');
      window.location.href = `?${params.toString()}`;

    // SCROLL TO TOP
    } else if (el.matches('[data-action="scroll-top"]')) {
      document.documentElement.scrollTo({
        top: 0,
        behavior: 'smooth',
      });

    // TOGGLE PINNED
    } else if (el.matches('[data-action="toggle-pin"]')) {
      const entity = getEntity();
      let id = entity.id;
      if (isNaN(id) || id === null) {
        id = parseInt(el.dataset.id, 10);
      }

      ApiC.patch(`${entity.type}/${id}`, {'action': Action.Pin}).then(() => {
        // toggle appearance of button and icon
        toggleGrayClasses(el.classList);
        el.querySelector('i').classList.toggle('color-weak');
      });


    // AUTOCOMPLETE
    } else if (el.matches('[data-complete-target]')) {
      // depending on the type of results, we will want different attributes and formatting
      let transformer = entity => {
        const cat = entity.category_title ? `${entity.category_title} - ` : '';
        const stat = entity.status_title ? `${entity.status_title} - ` : '';
        return `${entity.id} - ${cat}${stat}${entity.title}`;
      };
      // useid data attribute is used in admin panel to grab the userid from input
      if (el.dataset.completeTarget === 'users') {
        transformer = user => `${user.userid} - ${user.fullname} (${user.email})`;
      }

      // use autocomplete jquery-ui plugin
      $(el).autocomplete({
        // this option is necessary or the autocomplete box will get lost under the permissions modal
        appendTo: el.dataset.identifier ? `#autocompleteAnchorDiv_${el.dataset.identifier}` : '',
        source: function(request: Record<string, string>, response: (data: Array<string>) => void): void {
          if (request.term.length < 3) {
            // TODO make it unselectable/grayed out or something, maybe once we use homegrown autocomplete
            response([i18next.t('type-3-chars')]);
            return;
          }
          if (['experiments', 'items'].includes(el.dataset.completeTarget)) {
            request.term = escapeExtendedQuery(request.term);
          }
          ApiC.getJson(`${el.dataset.completeTarget}/?q=${request.term}`).then(json => {
            response(json.map(entry => transformer(entry)));
          });
        },
      });

    // TRANSFER OWNERSHIP
    } else if (el.matches('[data-action="transfer-ownership"]')) {
      const value = (document.getElementById('target_owner') as HTMLInputElement).value;
      const entity = getEntity();
      const params = {};
      params[Target.UserId] = parseInt(value.split(' ')[0], 10);
      ApiC.patch(`${entity.type}/${entity.id}`, params).then(() => window.location.reload());

    // ADD USER TO PERMISSIONS
    // create a new li element in the list of existing users, so it is collected at Save action
    } else if (el.matches('[data-action="add-user-to-permissions"]')) {
      // collect userid + name + email from input
      const addUserPermissionsInput = (document.getElementById(`${el.dataset.identifier}_select_users`) as HTMLInputElement);
      const userid = parseInt(addUserPermissionsInput.value, 10);
      if (isNaN(userid)) {
        notifError(new Error('Use the autocompletion menu to add users.'));
        return;
      }
      const userName = addUserPermissionsInput.value.split(' - ')[1];

      // create li element
      const li = document.createElement('li');
      li.classList.add('list-group-item');
      li.dataset.id = String(userid);

      // eye or pencil icon
      const rwIcon = document.createElement('i');
      rwIcon.classList.add('fas');
      const iconClass = el.dataset.rw === 'canread' ? 'eye' : 'pencil-alt';
      rwIcon.classList.add(`fa-${iconClass}`);

      // delete icon
      const deleteSpan = document.createElement('span');
      deleteSpan.dataset.action = 'remove-parent';
      deleteSpan.classList.add('hover-danger');
      const xIcon = document.createElement('i');
      xIcon.classList.add('fas');
      xIcon.classList.add('fa-xmark');
      deleteSpan.insertAdjacentElement('afterbegin', xIcon);

      // construct the li element with all its content
      li.insertAdjacentElement('afterbegin', rwIcon);
      li.insertAdjacentText('beforeend', ' ' + userName + ' ');
      li.insertAdjacentElement('beforeend', deleteSpan);

      // and insert it into the list
      document.getElementById(`${el.dataset.identifier}_list_users`).appendChild(li);

      // clear input
      addUserPermissionsInput.value = '';

    } else if (el.matches('[data-action="remove-parent"]')) {
      el.parentElement.remove();

    // CLEAR FORM
    } else if (el.matches('[data-action="clear-form"]')) {
      const target = document.getElementById(el.dataset.target);
      const inputs = target.querySelectorAll('input');
      inputs.forEach(input => input.value = '');

    // REMOVE A QUERY PARAMETER AND RELOAD PAGE
    } else if (el.matches('[data-action="remove-param-reload"]')) {
      const params = new URLSearchParams(document.location.search.slice(1));
      params.delete(el.dataset.target);
      // reload the page
      document.location.search = params.toString();

    } else if (el.matches('[data-action="reload-page"]')) {
      location.reload();

    // SAVE PERMISSIONS
    } else if (el.matches('[data-action="save-permissions"]')) {
      const params = {};
      // collect existing users listed in ul->li, and store them in a string[] with user:<userid>
      const existingUsers = Array.from(document.getElementById(`${el.dataset.identifier}_list_users`).children)
        .map(u => `user:${(u as HTMLElement).dataset.id}`);

      params[el.dataset.rw] = permissionsToJson(
        parseInt(($('#' + el.dataset.identifier + '_select_base').val() as string), 10),
        ($('#' + el.dataset.identifier + '_select_teams').val() as string[])
          .concat($('#' + el.dataset.identifier + '_select_teamgroups').val() as string[])
          .concat(existingUsers),
      );
      // if we're editing the default read/write permissions for experiments, this data attribute will be set
      if (el.dataset.isUserDefault) {
        // we need to replace canread/canwrite with default_read/default_write for user attribute
        let paramKey = 'default_read';
        if (el.dataset.rw === 'canwrite') {
          paramKey = 'default_write';
        }
        // create a new key and delete the old one
        params[paramKey] = params[el.dataset.rw];
        delete params[el.dataset.rw];
        ApiC.patch(`${Model.User}/me`, params).then(() => reloadElements([el.dataset.identifier + 'Div']));
      } else {
        const entity = getEntity();
        ApiC.patch(`${entity.type}/${entity.id}`, params).then(() => reloadElements([el.dataset.identifier + 'Div']));
      }

    } else if (el.matches('[data-action="select-lang"]')) {
      const select = (document.getElementById('langSelect') as HTMLSelectElement);
      fetch(`app/controllers/UnauthRequestHandler.php?lang=${select.value}`).then(() => window.location.reload());

    /* TOGGLE NEXT ACTION
     * An element with "toggle-next" as data-action value will appear clickable.
     * Clicking on it will toggle the "hidden" attribute of the next sibling element by default.
     */
    } else if (el.matches('[data-action="toggle-next"]')) {
      let targetEl = el.nextElementSibling as HTMLElement;
      if (el.dataset.toggleTarget) {
        targetEl = document.getElementById(el.dataset.toggleTarget);
      }
      const isHidden = targetEl.toggleAttribute('hidden');
      el.setAttribute('aria-expanded', String(!isHidden));

      // might want to toggle another element with toggle-extra
      if (el.dataset.toggleTargetExtra) {
        document.getElementById(el.dataset.toggleTargetExtra).toggleAttribute('hidden');
      }

      // save the hidden state of the target element in localStorage
      if (targetEl.dataset.saveHidden) {
        localStorage.setItem(`${targetEl.dataset.saveHidden}-isHidden`, isHidden ? '1' : '0');
      }

      // now deal with icon of executor element
      toggleIcon(el, isHidden);

    } else if (el.matches('[data-action="expand-all-storage"]')) {
      const root = document.getElementById('storageDiv');
      if (root) {
        const detailsElements = root.querySelectorAll('details');
        detailsElements.forEach((details: HTMLDetailsElement) => {
          details.open = true;
        });
      }
    } else if (el.matches('[data-action="add-storage"]')) {
      const name = prompt('Name');
      const params = {};
      params['parent_id'] = el.dataset.parentId;
      params['name'] = name;
      ApiC.post('storage_units', params).then(() => reloadElements(['storageDiv']));

    } else if (el.matches('[data-action="add-storage-children"]')) {
      const unitName = prompt(i18next.t('location-name'));
      if (!unitName.length) {
        return;
      }
      const params = {};
      params['parent_id'] = el.dataset.parentId;
      params['name'] = unitName;
      ApiC.post('storage_units', params).then(() => reloadElements(['storageDiv']));
    } else if (el.matches('[data-action="create-container"]')) {
      const entity = getEntity();
      const qty_stored = (document.getElementById('containerQtyStoredInput') as HTMLInputElement).value;
      const qty_unit = (document.getElementById('containerQtyUnitSelect') as HTMLSelectElement).value;
      let multiplier = parseInt((document.getElementById('containerMultiplierInput') as HTMLInputElement).value, 10);
      if (isNaN(multiplier) || multiplier <= 0) {
        multiplier = 1;
      }

      const postCalls = Array.from({ length: multiplier }, () =>
        ApiC.post(`${entity.type}/${entity.id}/containers/${el.dataset.id}`, {
          qty_stored: qty_stored,
          qty_unit: qty_unit,
        }),
      );
      // Execute all POST calls and reload elements after all are resolved
      Promise.all(postCalls)
        .then(() => reloadElements(['storageDivContent']))
        .catch((error) => notifError(error));

    } else if (el.matches('[data-action="destroy-container"]')) {
      const entity = getEntity();
      ApiC.delete(`${entity.type}/${entity.id}/containers/${el.dataset.id}`).then(() => reloadElements(['storageDivContent']));
    } else if (el.matches('[data-action="destroy-storage"]')) {
      ApiC.delete(`storage_units/${el.dataset.id}`).then(() => reloadElements(['storageDiv']));

    // REPLACE WITH NEXT ACTION
    } else if (el.matches('[data-action="replace-with-next"]')) {
      const targetEl = el.nextElementSibling as HTMLElement;
      // show the target
      targetEl.toggleAttribute('hidden');
      // hide clicked element
      el.toggleAttribute('hidden');

    // TOGGLE MODAL
    } else if (el.matches('[data-action="toggle-modal"]')) {
      // TODO this requires jquery for now. Not in BS5.
      $('#' + el.dataset.target).modal('toggle');

    // UPDATE ENTITY BODY (SAVE BUTTON)
    } else if (el.matches('[data-action="update-entity-body"]')) {
      updateEntityBody().then(() => {
        // SAVE AND GO BACK BUTTON
        if (el.matches('[data-redirect="view"]')) {
          const entity = getEntity();
          window.location.replace('?mode=view&id=' + entity.id);
        }
      });


    // SEARCH PUBCHEM
    } else if (el.matches('[data-action="search-pubchem"]')) {
      const inputEl = el.parentElement.parentElement.querySelector('input') as HTMLInputElement;
      if (!inputEl.checkValidity()) {
        inputEl.reportValidity();
        return;
      }
      const elOldHTML = mkSpin(el);
      const resultDiv = document.getElementById('pubChemSearchResultDiv');
      const resultTableDiv = document.getElementById('pubChemSearchResultTableDiv');
      const viewOnPubChemLink = document.getElementById('viewOnPubChemLink') as HTMLLinkElement;
      ApiC.getJson(`compounds?search_pubchem_${el.dataset.from}=${inputEl.value}`).then(json => {
        const table = generateTable(json);
        resultDiv.removeAttribute('hidden');
        viewOnPubChemLink.href = `https://pubchem.ncbi.nlm.nih.gov/compound/${json.cid}`;
        // clear any previous result
        resultTableDiv.innerHTML = '';
        resultTableDiv.appendChild(table);
        const importBtn = document.querySelector('[data-action="import-compound"]') as HTMLButtonElement;
        importBtn.removeAttribute('disabled');
        importBtn.dataset.cid = json.cid;
      }).catch(err => {
        console.error(err);
        notifError(new Error(i18next.t('resource-not-found')));
      }).finally(() => {
        mkSpinStop(el, elOldHTML);
      });

    } else if (el.matches('[data-action="search-entity-from-compound"]')) {
      // try and grab the CAS for the search
      let query = (document.getElementById('compoundInput-cas_number') as HTMLInputElement).value;
      // if no cas, use the name
      if (!query) {
        query = (document.getElementById('compoundInput-name') as HTMLInputElement).value;
      }
      window.location.href = `${el.dataset.page}.php?q="${encodeURIComponent(query)}"`;

    // IMPORT FROM PUBCHEM
    } else if (el.matches('[data-action="import-compound"]')) {
      const params = {cid: parseInt(el.dataset.cid, 10), action: Action.Duplicate};
      ApiC.post2location('compounds', params).then(() => {
        document.dispatchEvent(new CustomEvent('dataReload'));
        clearForm(document.getElementById('importFromPubChemModal'));
        const resultDiv = document.getElementById('pubChemSearchResultDiv');
        resultDiv.setAttribute('hidden', 'hidden');
        const resultTableDiv = document.getElementById('pubChemSearchResultTableDiv');
        resultTableDiv.innerHTML = '';
        const importBtn = document.querySelector('[data-action="import-cid"]');
        importBtn.setAttribute('disabled', 'disabled');
      });

    } else if (el.matches('[data-action="create-resource-from-compound"]')) {
      const compoundId = (document.getElementById('compoundInput-id') as HTMLInputElement).value;
      ApiC.post2location('items', {template: el.dataset.tplid}).then(id => {
        // now create a link with that compound
        ApiC.post(`items/${id}/compounds/${compoundId}`).then(() => {
          // also change the title
          const compoundName = (document.getElementById('compoundInput-name') as HTMLInputElement).value;
          ApiC.patch(`items/${id}`, {title: compoundName}).then(() => {
            window.location.href = `/database.php?mode=edit&id=${id}`;
          });
        });
      });

    // CREATE/EDIT COMPOUND MANUALLY
    } else if (el.matches('[data-action="save-compound"]')) {
      try {
        if (el.dataset.compoundId) { // edit
          const compoundForm = document.getElementById('editCompoundInputs');
          const params = collectForm(compoundForm);
          ApiC.patch(`compounds/${el.dataset.compoundId}`, params).then(() => {
            document.dispatchEvent(new CustomEvent('dataReload'));
            $('#editCompoundModal').modal('hide');
            clearForm(compoundForm);
          });
        } else { // create
          const compoundForm = document.getElementById('createCompoundInputs');
          const params = collectForm(compoundForm);
          clearForm(compoundForm);
          ApiC.post2location('compounds', params).then(id => {
            ApiC.getJson(`compounds/${id}`).then((json) => {
              setTimeout(() => {
                toggleEditCompound(json);
              }, 500);
              document.dispatchEvent(new CustomEvent('dataReload'));
            });
          });
        }
      } catch (err) {
        notifError(err);
        return;
      }
    // DELETE SELECTED COMPOUNDS
    } else if (el.matches('[data-action="delete-compounds"]')) {
      const btn = document.getElementById('deleteCompoundsBtn');
      const idList = btn.dataset.target.split(',');
      if (!confirm(`Delete ${idList.length} compound(s)?`)) {
        return;
      }
      idList.forEach(id => {
        ApiC.delete(`compounds/${id}`);
      });
      document.dispatchEvent(new CustomEvent('dataReload'));

    // PASSWORD VISIBILITY TOGGLE
    } else if (el.matches('[data-action="toggle-password"]')) {
      // toggle eye icon
      const icon = el.firstChild as HTMLElement;
      icon.classList.toggle('fa-eye');
      icon.classList.toggle('fa-eye-slash');

      // toggle input type
      const input = document.getElementById(el.dataset.target) as HTMLInputElement;
      let attribute = 'password';
      if (input.getAttribute('type') === 'password') {
        attribute = 'text';
      }
      input.type = attribute;

    // LOGOUT
    } else if (el.matches('[data-action="logout"]')) {
      localStorage.clear();
      window.location.href = 'app/logout.php';

    // ACK NOTIF
    } else if (el.matches('[data-action="ack-notif"]')) {
      if (el.parentElement.dataset.ack === '0') {
        ApiC.patch(`${Model.User}/me/${Model.Notification}/${el.dataset.id}`).then(() => {
          if (el.dataset.href) {
            window.location.href = el.dataset.href;
          } else {
            reloadElements(['navbarNotifDiv']);
          }
        });
      } else {
        if (el.dataset.href) {
          window.location.href = el.dataset.href;
        }
      }

    // DESTROY (clear all) NOTIF
    } else if (el.matches('[data-action="destroy-notif"]')) {
      ApiC.delete(`${Model.User}/me/${Model.Notification}`).then(() => reloadElements(['navbarNotifDiv']));

    } else if (el.matches('[data-action="export-user"]')) {
      let source: string;
      // profile page will set this attribute on the action button
      if (el.dataset.userid) {
        source = el.dataset.userid;
      } else {
        // admin page will provide it from a select element
        source = (document.getElementById('userExport') as HTMLSelectElement).value;
      }
      const format = (document.getElementById('userExportFormat') as HTMLSelectElement).value;
      window.location.href = `make.php?format=${format}&owner=${source}&type=experiments`;

    } else if (el.matches('[data-query]')) {
      const url = new URL(window.location.href);
      // query format: order-sort
      const query = el.dataset.query.split('-');
      url.searchParams.set('order', query[0]);
      url.searchParams.set('sort', query[1]);
      window.location.href = url.href;

    // CREATE EXPERIMENT, TEMPLATE or DATABASE item: main create button in top right
    } else if (el.matches('[data-action="create-entity"]')) {
      let params = {};
      if (el.dataset.hasTitle) {
        params = collectForm(document.getElementById('createNewForm'));
      }
      if (el.dataset.tplid) {
        params['template'] = el.dataset.tplid;
      }
      // look for any tag present in the url, we will create the entry with these tags
      const urlParams = new URLSearchParams(document.location.search);
      const tags = urlParams.getAll('tags[]');
      if (tags) {
        params['tags'] = tags;
      }
      let page = 'experiments.php';
      if (el.dataset.type === 'experiments_templates') {
        page = 'templates.php';
      }
      if (el.dataset.type === 'database') {
        el.dataset.type = 'items';
        page = 'database.php';
      }
      ApiC.post2location(`${el.dataset.type}`, params).then(id => {
        window.location.href = `${page}?mode=edit&id=${id}`;
      });

    } else if (el.matches('[data-action="report-bug"]')) {
      event.preventDefault();
      el.querySelector('i').classList.add('moving-bug');
      setTimeout(() => window.location.assign('https://github.com/elabftw/elabftw/issues/new/choose'), 3000);

    // DOWNLOAD TEMPLATE
    } else if (el.matches('[data-action="download-template"]')) {
      window.location.href = `make.php?format=eln&type=experiments_templates&id=${el.dataset.id}`;
    // TOGGLE ANONYMOUS READ ACCESS
    } else if (el.matches('[data-action="toggle-anonymous-access"]')) {
      const entity = getEntity();
      ApiC.patch(`${entity.type}/${entity.id}`, {'action': Action.AccessKey}).then(response => response.json()).then(json => {
        document.getElementById('anonymousAccessUrlDiv').toggleAttribute('hidden');
        (document.getElementById('anonymousAccessUrlInput') as HTMLInputElement).value = json.sharelink;
      });

    // COPY TO CLIPBOARD
    } else if (el.matches('[data-action="copy-to-clipboard"]')) {
      navigator.clipboard.writeText((document.getElementById(el.dataset.target) as HTMLInputElement).value);
      // indicate that the data was copied by changing the icon into text and back into the icon
      const previousHTML = el.innerHTML;
      window.setTimeout(function() {
        el.innerHTML = previousHTML;
      }, 1337);
      el.innerText = 'Copied!';

    // CHECK MAX SIZE
    } else if (el.matches('[data-action="check-max-size"]')) {
      const input = document.getElementById(el.dataset.input) as HTMLInputElement;
      // file.size from input will be in bytes, maxsize will be in Mb
      const maxsize = parseInt(el.dataset.maxsize, 10) * 1024 * 1024;
      if (input.files[0].size > maxsize) {
        document.getElementById('errorHolder').innerText = 'Error: file is too large!';
        // prevent the form from being submitted
        event.preventDefault();
      }
    // REMOVE COMPOUND LINK
    } else if (el.matches('[data-action="delete-compoundlink"]')) {
      const entity = getEntity();
      ApiC.delete(`${entity.type}/${entity.id}/compounds/${el.dataset.id}`).then(() => reloadElements(['compoundDiv']));
    // CLICK the NOW button of a time or date extra field
    } else if (el.matches('[data-action="update-to-now"]')) {
      const input = el.closest('.input-group').querySelector('input');
      // use Luxon lib here
      const now = DateTime.local();
      // date format
      let format = 'yyyy-MM-dd';
      if (input.type === 'time') {
        format = 'HH:mm';
      }
      if (input.type === 'datetime-local') {
        /* eslint-disable-next-line quotes */
        format = "yyyy-MM-dd'T'HH:mm";
      }
      input.value = now.toFormat(format);
      // trigger change event so it is saved
      input.dispatchEvent(new Event('change'));

    // TOGGLE BODY
    } else if (el.matches('[data-action="toggle-body"]')) {
      const randId = el.dataset.randid;
      const bodyDiv = document.getElementById(randId);
      let action = 'hide';
      // transform the + in - and vice versa
      if (bodyDiv.hasAttribute('hidden')) {
        action = 'show';
      }
      toggleIcon(el, !bodyDiv.hasAttribute('hidden'));
      // don't reload body if it is already loaded for show action
      // and the hide action is just toggle hidden attribute and do nothing else
      if ((action === 'show' && bodyDiv.dataset.bodyLoaded) || action === 'hide') {
        bodyDiv.toggleAttribute('hidden');
        return;
      }

      const contentDiv = bodyDiv.querySelector('div');

      // prepare the get request
      const entityId = parseInt(el.dataset.id, 10);
      let queryUrl = `${el.dataset.type}/${entityId}`;
      // special case for revisions
      if (el.dataset.revid) {
        queryUrl += `/revisions/${el.dataset.revid}`;
      }
      ApiC.getJson(queryUrl).then(json => {
        // add extra fields elements from metadata json
        const entity = {type: el.dataset.type as EntityType, id: entityId};
        const MetadataC = new Metadata(entity, new JsonEditorHelper(entity));
        MetadataC.metadataDiv = contentDiv;
        MetadataC.display('view').then(() => {
          // go over all the type: url elements and create a link dynamically
          generateMetadataLink();
        });
        // add html content
        contentDiv.innerHTML = json.body_html;

        // adjust the width of the children
        // get the width of the parent. The -30 is to make it smaller than parent even with the margins
        const width = document.getElementById('parent_' + randId).clientWidth - 30;
        bodyDiv.style.width = String(width);

        // ask mathjax to reparse the page
        MathJax.typeset();

        TableSortingC.init();

        bodyDiv.toggleAttribute('hidden');
        bodyDiv.dataset.bodyLoaded = '1';
      });
    }
  });
});
