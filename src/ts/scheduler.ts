/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */
import bootstrapPlugin from '@fullcalendar/bootstrap';
import { Calendar } from '@fullcalendar/core';
import caLocale from '@fullcalendar/core/locales/ca';
import csLocale from '@fullcalendar/core/locales/cs';
import deLocale from '@fullcalendar/core/locales/de';
import enLocale from '@fullcalendar/core/locales/en-gb';
import esLocale from '@fullcalendar/core/locales/es';
import frLocale from '@fullcalendar/core/locales/fr';
import idLocale from '@fullcalendar/core/locales/id';
import itLocale from '@fullcalendar/core/locales/it';
import jaLocale from '@fullcalendar/core/locales/ja';
import koLocale from '@fullcalendar/core/locales/ko';
import nlLocale from '@fullcalendar/core/locales/nl';
import plLocale from '@fullcalendar/core/locales/pl';
import ptLocale from '@fullcalendar/core/locales/pt';
import ptbrLocale from '@fullcalendar/core/locales/pt-br';
import ruLocale from '@fullcalendar/core/locales/ru';
import skLocale from '@fullcalendar/core/locales/sk';
import slLocale from '@fullcalendar/core/locales/sl';
import zhcnLocale from '@fullcalendar/core/locales/zh-cn';
import dayGridPlugin from '@fullcalendar/daygrid';
import interactionPlugin from '@fullcalendar/interaction';
import listPlugin from '@fullcalendar/list';
import timeGridPlugin from '@fullcalendar/timegrid';
import timelinePlugin from '@fullcalendar/timeline';
import i18next from 'i18next';
import $ from 'jquery';
import 'bootstrap/js/src/modal.js';
import { DateTime } from 'luxon';
import 'jquery-ui/ui/widgets/autocomplete';
import { Api } from './Apiv2.class';
import { Action } from './interfaces';
import { escapeHTML, TomSelect } from './misc';

document.addEventListener('DOMContentLoaded', () => {
  if (window.location.pathname !== '/scheduler.php') {
    return;
  }
  document.getElementById('loading-spinner')?.remove();

  const ApiC = new Api();

  // TomSelect settings shared on page & modal selects
  const sharedTomSelectOptions = {
    maxItems: null,
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
  };

  // start and end inputs
  const startInput = (document.getElementById('schedulerEventModalStart') as HTMLInputElement);
  const endInput = (document.getElementById('schedulerEventModalEnd') as HTMLInputElement);

  // transform a Date object into something we can put as a value of an input of type datetime-local
  function toDateTimeInputValueNumber(datetime: Date): number {
    const offset = datetime.getTimezoneOffset() * 60 * 1000;
    return datetime.valueOf() - offset;
  }
  const params = new URLSearchParams(document.location.search.substring(1));
  // get the start parameter from url and use that as start time if it's there
  const start = params.get('start');
  let selectedDate = new Date().valueOf();
  if (start !== null) {
    selectedDate = new Date(decodeURIComponent(start)).valueOf();
  }

  // bind to the element #scheduler
  const calendarEl: HTMLElement = document.getElementById('scheduler');
  if (!calendarEl) {
    return;
  }

  const layoutCheckbox = document.getElementById('scheduler_layout') as HTMLInputElement;
  const layout = (layoutCheckbox && layoutCheckbox.checked)
    ? 'timelineDay,timelineWeek,listWeek,timelineMonth' // horizontal axis
    : 'timeGridDay,timeGridWeek,listWeek,dayGridMonth'; // classic grid calendar

  // clean up 'category' parameter on page refresh or else it keeps it as the only available value in the Select
  if (params.has('category')) {
    params.delete('category');
    window.location.replace(`${location.pathname}?${params.toString()}`);
  }

  initTomSelect();
  // remove existing params to build new event sources for the calendar
  function buildEventSourcesUrl(): string {
    ['items[]', 'category', 'eventOwner'].forEach((param) => params.delete(param));
    const itemSelect = document.getElementById('itemSelect') as HTMLSelectElement & { tomselect?: TomSelect };
    const categorySelect = document.getElementById('categorySelect') as HTMLSelectElement;
    const ownerInput = document.getElementById('eventOwnerSelect') as HTMLInputElement;

    if (itemSelect?.tomselect?.items?.length) {
      lockScopeButton(itemSelect.tomselect.items);
      itemSelect.tomselect.items.forEach(id => {
        params.append('items[]', id);
      });
    }
    if (categorySelect?.value) {
      params.set('category', categorySelect.value);
    }
    if (ownerInput?.value.trim()) {
      const ownerId = ownerInput.value.trim().split(' ')[0];
      params.set('eventOwner', ownerId);
    }
    return `api/v2/events?${params.toString()}`;
  }
  // refresh calendar when the event source is updated
  function reloadCalendarEvents(): void {
    const newQuery = buildEventSourcesUrl();
    calendar.removeAllEventSources();
    calendar.addEventSource({ url: newQuery });
    calendar.refetchEvents();
    window.history.replaceState({}, '', `${location.pathname}?${params.toString()}`);
  }

  function refreshBoundDivs(extendedProps) {
    // start by clearing the divs
    $('#eventBoundExp').html('');
    $('#eventBoundDb').html('');
    if (extendedProps.experiment != null) {
      $('#eventBoundExp').html(`Event is bound to an experiment: <a href="experiments.php?mode=view&id=${extendedProps.experiment}">${extendedProps.experiment_title}</a>.`);
      $('[data-action="scheduler-rm-bind"][data-type="experiment"]').show();
    }
    if (extendedProps.item_link != null) {
      $('#eventBoundDb').html(`Event is bound to an item: <a href="database.php?mode=view&id=${extendedProps.item_link}">${extendedProps.item_link_title}</a>.`);
      $('[data-action="scheduler-rm-bind"][data-type="item_link"]').show();
    }
  }

  // SCHEDULER
  const calendar = new Calendar(calendarEl, {
    schedulerLicenseKey: 'GPL-My-Project-Is-Open-Source',
    height: '70vh',
    // Determines how far forward the scroll pane is initially scrolled.
    scrollTime: '08:00:00',
    weekends: calendarEl.dataset.weekends === '1',
    plugins: [ dayGridPlugin, timeGridPlugin, interactionPlugin, listPlugin, bootstrapPlugin, timelinePlugin ],
    headerToolbar: {
      left: 'prev,next today',
      center: 'title',
      right: layout,
    },
    views: {
      timelineMonth: {
        slotLabelFormat: [
          { weekday: 'short', day: 'numeric' }, // e.g., "Tue 8" in month view
        ],
      },
    },
    initialView: layoutCheckbox.checked ? 'timelineWeek' : 'timeGridWeek',
    themeSystem: 'bootstrap',
    // i18n
    // all available locales
    locales: [ caLocale, csLocale, deLocale, enLocale, esLocale, frLocale, itLocale, idLocale, jaLocale, koLocale, nlLocale, plLocale, ptLocale, ptbrLocale, ruLocale, skLocale, slLocale, zhcnLocale ],
    // selected locale
    locale: calendarEl.dataset.lang,
    // allow selection of range
    selectable: true,
    // draw an event while selecting
    selectMirror: true,
    editable: true,
    // allow "more" link when too many events
    dayMaxEventRows: true,
    // set the date loaded
    initialDate: selectedDate,
    // display a line for the time of now
    nowIndicator: true,
    // load the events as JSON
    eventSources: [
      {
        url: buildEventSourcesUrl(),
      },
    ],
    // first day is monday
    firstDay: 1,
    // remove possibility to book whole day, might add it later
    allDaySlot: false,
    // adjust the background color of event to the color of the item type
    eventBackgroundColor: '#a9a9a9',
    // selection
    select: function(info): void {
      const itemSelectEl = document.getElementById('itemSelect') as HTMLSelectElement & { tomselect?: TomSelect };
      const selectedItemIds: string[] = itemSelectEl.tomselect?.items || [];

      const body = document.getElementById('modalBody')!;
      body.innerHTML = ''; // Reset modal body

      let manualSelect: TomSelect | null = null;

      if (selectedItemIds.length > 0) {
        // Show checkboxes for already selected items
        selectedItemIds.forEach(itemId => {
          const option = itemSelectEl.querySelector(`option[value="${itemId}"]`);

          // wrapper div
          const div = document.createElement('div');
          div.className = 'form-check';

          // checkbox input
          const input = document.createElement('input');
          input.className = 'form-check-input';
          input.type = 'checkbox';
          input.value = itemId;
          input.id = `selectedItem${itemId}`;
          input.checked = true;

          // label
          const label = document.createElement('label');
          label.className = 'form-check-label';
          label.htmlFor = `selectedItem${itemId}`;
          label.textContent = option?.textContent || `Item ${itemId}`;

          // append input and label to div
          div.appendChild(input);
          div.appendChild(label);
          body.appendChild(div);
        });
      } else {
        // Rebuild TomSelect inputs if nothing selected
        const itemSelectLabel = i18next.t('select-resource');
        const categorySelectLabel = i18next.t('filter-by-category');

        const categorySelectEl = document.getElementById('categorySelect') as HTMLSelectElement;
        const categories = Array.from(categorySelectEl.options)
          .filter(opt => opt.value)
          .map(opt => `<option value="${opt.value}">${opt.textContent}</option>`)
          .join('');
        const items = Array.from(itemSelectEl.options)
          .filter(opt => opt.value)
          .map(opt => `<option value="${opt.value}" data-category="${escapeHTML(opt.dataset.category) ?? ''}">${escapeHTML(opt.textContent)}</option>`)
          .join('');

        body.innerHTML = `
          <div class='input-group ml-2'>
            <div class='input-group-prepend'>
              <span class='input-group-text'><i class='fas fa-magnifying-glass'></i></span>
            </div>
            <select id='categorySelectModal' class='form-control' style='max-width: 40%;' aria-label='${categorySelectLabel}'>
              <option value=''>${categorySelectLabel}</option>
              ${categories}
            </select>
            <select id='itemSelectModal' name='items[]' aria-label='${itemSelectLabel}' class='form-control form-inline'>
              <option value=''>${itemSelectLabel}</option>
              ${items}
            </select>
          </div>
        `;

        const itemSelectModalEl = document.getElementById('itemSelectModal') as HTMLSelectElement & { tomselect?: TomSelect };
        const categorySelectModalEl = document.getElementById('categorySelectModal') as HTMLSelectElement;
        // Initialize TomSelect after modal select is added to DOM
        manualSelect = new TomSelect(itemSelectModalEl, { ...sharedTomSelectOptions });

        // Filter resources from selected category
        categorySelectModalEl.addEventListener('change', () => {
          const selectedCategory = categorySelectModalEl.value;
          filterOptionsByCategory(itemSelectModalEl, selectedCategory);
        });
      }

      $('#itemPickerModal').modal('show');

      const confirmBtn = document.getElementById('confirmItemSelection') as HTMLButtonElement;
      // not using addEventListener or else it infinite loops the confirm modal
      confirmBtn.onclick = () => {
        let itemIdsToPost: string[] = [];

        if (selectedItemIds.length > 0) {
          const checkedInputs = body.querySelectorAll<HTMLInputElement>('input[type="checkbox"]:checked');
          itemIdsToPost = Array.from(checkedInputs).map(cb => cb.value);
        } else {
          itemIdsToPost = manualSelect?.items || [];
        }

        if (itemIdsToPost.length === 0) {
          alert('Please select at least one item to book.');
          return;
        }

        const postParams = {
          start: info.startStr,
          end: info.endStr,
        };

        Promise.all(
          itemIdsToPost.map(itemId =>
            ApiC.post(`events/${itemId}`, postParams),
          ),
        ).then(() => {
          calendar.refetchEvents();
          // refresh item with its title by triggering unselect (see #5265)
          calendar.unselect();
          $('#itemPickerModal').modal('hide');
        }).catch(() => {
          calendar.unselect();
          $('#itemPickerModal').modal('hide');
        });
      };
    },
    // on click activate modal window
    eventClick: function(info): void {
      $('[data-action="scheduler-rm-bind"]').hide();
      $('#eventModal').modal('toggle');
      // set the event id on the various elements
      document.querySelectorAll('[data-action="scheduler-bind-entity"]').forEach((btn: HTMLButtonElement) => btn.dataset.id = info.event.id);
      document.querySelectorAll('[data-action="scheduler-rm-bind"]').forEach((btn:HTMLButtonElement) => btn.dataset.eventid = info.event.id);
      document.querySelectorAll('.cancelEventBtn').forEach((btn: HTMLButtonElement) => btn.dataset.id = info.event.id);

      // title
      const eventTitle = document.getElementById('eventTitle') as HTMLInputElement;
      eventTitle.value = info.event.extendedProps.title_only;
      // set the event id on the title
      eventTitle.dataset.eventid = info.event.id;

      // start and end inputs values
      startInput.valueAsNumber = toDateTimeInputValueNumber(info.event.start);
      endInput.valueAsNumber = toDateTimeInputValueNumber(info.event.end);
      // also adjust the event id so the change listener will send a correct query
      startInput.dataset.eventid = info.event.id;
      endInput.dataset.eventid = info.event.id;
      refreshBoundDivs(info.event.extendedProps);
    },
    // on mouse enter add shadow and show title
    eventMouseEnter: function(info): void {
      info.el.classList.add('calendar-event-hover');
      info.el.title = info.event.title;
    },
    // remove the box shadow when mouse leaves
    eventMouseLeave: function(info): void {
      info.el.classList.remove('calendar-event-hover');
    },
    // a drop means we change start date
    eventDrop: function(info): void {
      ApiC.patch(`event/${info.event.id}`, {'target': 'start', 'delta': info.delta}).catch(() => info.revert());
    },
    // a resize means we change end date
    eventResize: function(info): void {
      ApiC.patch(`event/${info.event.id}`, {'target': 'end', 'delta': info.endDelta}).catch(() => info.revert());
    },
  });

  // only try to render if we actually have some bookable items
  if (calendarEl.dataset.render === 'true') {
    calendar.render();
    calendar.updateSize();
  }

  // add on change event listener on datetime inputs
  [startInput, endInput].forEach(input => {
    input.addEventListener('change', event => {
      const input = (event.currentTarget as HTMLInputElement);
      // Note: valueAsDate was not working on Chromium
      const dt = DateTime.fromMillis(input.valueAsNumber);
      ApiC.patch(`event/${input.dataset.eventid}`, {'target': input.dataset.what, 'epoch': String(dt.toUnixInteger())}).then(() => {
        calendar.refetchEvents();
      }).catch(() => calendar.refetchEvents());
    });
  });

  function clearBoundDiv(type: string) {
    if (type === 'experiment') {
      $('#eventBoundExp').html('');
      $('[data-action="scheduler-rm-bind"][data-type="experiment"]').hide();
      return;
    }
    $('#eventBoundDb').html('');
    $('[data-action="scheduler-rm-bind"][data-type="item_link"]').hide();
  }

  // Add click listener and do action based on which element is clicked
  document.querySelector('.real-container').addEventListener('click', (event) => {
    const el = (event.target as HTMLElement);
    // CANCEL EVENT ACTION
    if (el.matches('[data-action="cancel-event"]')) {
      ApiC.delete(`event/${el.dataset.id}`).then(() => calendar.refetchEvents()).catch();
    // CANCEL EVENT ACTION WITH MESSAGE
    } else if (el.matches('[data-action="cancel-event-with-message"]')) {
      const target = document.querySelector('input[name="targetCancelEvent"]:checked') as HTMLInputElement;
      const msg = (document.getElementById('cancelEventTextarea') as HTMLTextAreaElement).value;
      ApiC.post(`event/${el.dataset.id}/notifications`, {action: Action.Create, msg: msg, target: target.value, targetid: parseInt(target.dataset.targetid, 10)}).then(() => {
        ApiC.delete(`event/${el.dataset.id}`).then(() => calendar.refetchEvents()).catch();
      });
    // SAVE EVENT TITLE
    } else if (el.matches('[data-action="save-event-title"]')) {
      const input = el.parentElement.parentElement.querySelector('input') as HTMLInputElement;
      ApiC.patch(`event/${input.dataset.eventid}`, {target: 'title', content: input.value}).then(() => calendar.refetchEvents());

    // BIND AN ENTITY TO THE EVENT
    } else if (el.matches('[data-action="scheduler-bind-entity"]')) {
      const inputEl = el.parentNode.parentNode.querySelector('input') as HTMLInputElement;
      const entityid = parseInt((inputEl.value as string), 10);
      if (entityid > 0) {
        ApiC.patch(`event/${el.dataset.id}`, {target: el.dataset.type, id: entityid}).then(res => res.json()).then(json => {
          calendar.refetchEvents();
          refreshBoundDivs(json);
          inputEl.value = '';
        });
      }
    // REMOVE BIND
    } else if (el.matches('[data-action="scheduler-rm-bind"]')) {
      const bindType = el.dataset.type;
      ApiC.patch(`event/${el.dataset.eventid}`, {'target': bindType, 'id': null}).then(() => {
        clearBoundDiv(bindType);
        // clear the inputs
        document.querySelectorAll('.bindInput').forEach((input:HTMLInputElement) => input.value = '');
        calendar.refetchEvents();
      });
    // FILTER OWNER
    } else if (el.matches('[data-action="filter-owner"]')) {
      reloadCalendarEvents();
    }
  });

  function lockScopeButton(selectedItems: string[]): void {
    const scopeBtn = document.getElementById('scopeEventBtn');
    const lockedBtn = document.getElementById('scopeLocked');
    const showLocked = selectedItems.length > 0;

    scopeBtn?.toggleAttribute('hidden', showLocked);
    lockedBtn?.toggleAttribute('hidden', !showLocked);
  }

  // Filters & repopulates the item TomSelect dropdown with options that match the selected category
  function filterOptionsByCategory(
    selectEl: HTMLSelectElement & { tomselect?: TomSelect },
    category: string,
  ): void {
    if (!selectEl.tomselect) return;
    selectEl.tomselect.clearOptions();

    Array.from(selectEl.options).forEach(option => {
      if (!category || option.dataset.category === category) {
        selectEl.tomselect.addOption({ value: option.value, text: option.textContent ?? '',
        });
      }
    });
    selectEl.tomselect.refreshOptions(false);
  }

  function initTomSelect(): void {
    const itemSelect = document.getElementById('itemSelect') as HTMLSelectElement;
    const categorySelect = document.getElementById('categorySelect') as HTMLSelectElement;

    new TomSelect(itemSelect, {
      ...sharedTomSelectOptions,
      onChange: (selectedItems) => {
        lockScopeButton(selectedItems);

        const url = new URL(window.location.href);
        url.searchParams.delete('items[]');
        selectedItems.forEach(itemId => {
          params.append('items[]', itemId);
          url.searchParams.append('items[]', itemId);
        });
        if (selectedItems.length === 0) {
          url.searchParams.delete('items[]');
        }
        window.history.replaceState({}, '', url.toString());
        reloadCalendarEvents();
      },
    });

    categorySelect.addEventListener('change', () => {
      const selectedCategory = categorySelect.value;
      filterOptionsByCategory(itemSelect, selectedCategory);
      reloadCalendarEvents();
    });
  }
});
