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
import $ from 'jquery';
import 'bootstrap/js/src/modal.js';
import { DateTime } from 'luxon';
import 'jquery-ui/ui/widgets/autocomplete';
import { ApiC } from './api';
import i18next from './i18n';
import { Action } from './interfaces';
import { TomSelect } from './misc';

// transform a Date object into something we can put as a value of an input of type datetime-local
function toDateTimeInputValueNumber(datetime: Date): number {
  const offset = datetime.getTimezoneOffset() * 60 * 1000;
  return datetime.valueOf() - offset;
}

function lockScopeButton(selectedItems: string[]): void {
  const scopeBtn = document.getElementById('scopeEventBtn');
  const lockedBtn = document.getElementById('scopeLocked');
  const showLocked = selectedItems.length > 0;
  scopeBtn?.toggleAttribute('hidden', showLocked);
  lockedBtn?.toggleAttribute('hidden', !showLocked);
}

if (window.location.pathname === '/scheduler.php') {
  document.getElementById('loading-spinner')?.remove();

  // TomSelect settings shared on page & modal selects
  const sharedTomSelectOptions = {
    maxItems: null,
    plugins: {
      clear_button: {},
      no_active_items: {},
      remove_button: {},
      no_backspace_delete: {},
    },
  };

  // start and end inputs
  const startInput = (document.getElementById('schedulerEventModalStart') as HTMLInputElement);
  const endInput = (document.getElementById('schedulerEventModalEnd') as HTMLInputElement);

  const params = new URLSearchParams(document.location.search.substring(1));
  // get the start parameter from url and use that as start time if it's there
  const start = params.get('start');
  let selectedDate = new Date().valueOf();
  if (start !== null) {
    selectedDate = new Date(decodeURIComponent(start)).valueOf();
  }

  // bind to the element #scheduler
  const calendarEl: HTMLElement = document.getElementById('scheduler');
  if (calendarEl) {

    const layoutCheckbox = document.getElementById('scheduler_layout') as HTMLInputElement;
    const layout = (layoutCheckbox && layoutCheckbox.checked)
      ? 'timelineDay,timelineWeek,listWeek,timelineMonth' // horizontal axis
      : 'timeGridDay,timeGridWeek,listWeek,dayGridMonth'; // classic grid calendar

    // clean up 'category' parameter on page refresh or else it keeps it as the only available value in the Select
    if (params.has('category')) {
      params.delete('category');
      window.location.replace(`${location.pathname}?${params.toString()}`);
    }

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

    // create self-removable badge for selected items (in scheduler & modal)
    const createBadge = (selectInput, tomSelect, wrapper, id) => {
      const opt = selectInput.querySelector(`option[value="${id}"]`) as HTMLOptionElement;
      if (!opt) return;

      const badge = document.createElement('span');
      badge.textContent = opt.textContent;
      badge.className = 'selected-item-badge';
      const rawColor = opt.dataset.color;
      badge.style.setProperty('--badge-color', rawColor?.startsWith('#') ? rawColor : `#${rawColor || '000'}`);

      const removeBtn = document.createElement('button');
      removeBtn.type = 'button';
      removeBtn.className = 'ml-2 close';
      const removeBtnIcon = document.createElement('i');
      removeBtnIcon.classList.add('fas', 'fa-xmark', 'fa-fw', 'color-white');
      removeBtn.appendChild(removeBtnIcon);

      badge.appendChild(removeBtn);
      wrapper.appendChild(badge);

      // Make badge keyboard-accessible
      badge.setAttribute('tabindex', '0');
      badge.setAttribute('role', 'button');
      badge.setAttribute('aria-label', `Remove ${opt.textContent}`);
      // also handle keydown (enter)
      const removeBadgeHandler = e => {
        e.preventDefault();
        removeBadge(badge, tomSelect, id);
      };
      removeBtn.addEventListener('click', removeBadgeHandler);
      removeBtn.addEventListener('keydown', e =>
        ['Enter', ' '].includes(e.key) && removeBadgeHandler(e),
      );
    };

    const removeBadge = (badge, tomSelect, id) => {
      const confirmRemove = confirm(i18next.t('filter-delete-warning'));
      if (!confirmRemove) return;
      tomSelect.removeItem(id);
      badge.remove();
    };
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
      // background color is $secondlevel for all and it changes after validation of event
      // TODO maybe we could have an automatically generated .ts file exporting colors from _variables.scss
      eventBackgroundColor: '#bdbdbd',
      // user can see events as disabled if they don't have booking permissions. See #5930
      eventClassNames: (info) => {
        return Number(info.event.extendedProps.canbook) === 0 ? ['calendar-event-disabled'] : '';
      },
      // prevent any actions on disabled events
      eventAllow: (info, event) => Number(event.extendedProps.canbook) === 1,
      // selection
      select: function(info): void {
        const itemSelectEl = document.getElementById('itemSelect') as HTMLSelectElement & { tomselect?: TomSelect };
        const selectedItemIds: string[] = itemSelectEl.tomselect?.items || [];

        let manualSelect: TomSelect | null = null;

        // Handle post action for modals
        function handleConfirm(buttonId: string, getIdsFn: () => string[]) {
          const confirmBtn = document.getElementById(buttonId) as HTMLButtonElement;
          if (!confirmBtn) {
            console.warn(`Confirm button "${buttonId}" not found.`);
            return;
          }

          // not using addEventListener or else it infinite loops the confirm modal
          confirmBtn.onclick = () => {
            const itemIdsToPost = getIdsFn();
            if (itemIdsToPost.length === 0) {
              alert('Please select at least one item to book.');
              return;
            }

            const postParams = { start: info.startStr, end: info.endStr };
            Promise.all(
              itemIdsToPost.map(itemId => ApiC.post(`events/${itemId}`, postParams)),
            ).then(() => {
              calendar.refetchEvents();
              // refresh item with its title by triggering unselect (see #5265)
              calendar.unselect();
              $('.modal').modal('hide');
            }).catch(() => {
              calendar.unselect();
              $('.modal').modal('hide');
            });
          };
        }

        // case 1: Already selected items -> checkboxes with selected
        if (selectedItemIds.length > 0) {
          const container = document.getElementById('selectedItemsCheckboxes')!;
          container.innerHTML = '';

          selectedItemIds.forEach(itemId => {
            const option = itemSelectEl.querySelector(`option[value="${itemId}"]`);
            const labelText = option?.textContent || `Item ${itemId}`;

            const div = document.createElement('div');
            div.className = 'form-check';

            const input = document.createElement('input');
            input.className = 'form-check-input';
            input.type = 'checkbox';
            input.value = itemId;
            input.id = `selectedItem${itemId}`;
            input.checked = true;

            const label = document.createElement('label');
            label.className = 'form-check-label';
            label.htmlFor = input.id;
            label.textContent = labelText;

            div.appendChild(input);
            div.appendChild(label);
            container.appendChild(div);
          });

          $('#itemPickerReviewModal').modal('show');

          handleConfirm('confirmItemReview', () => {
            const checked = container.querySelectorAll<HTMLInputElement>('input[type="checkbox"]:checked');
            return Array.from(checked).map(cb => cb.value);
          });
        }

        // case 2: no items selected -> modal with tomSelect
        else {
          const itemSelectModalEl = document.getElementById('itemSelectModal') as HTMLSelectElement & { tomselect?: TomSelect };
          const categorySelectModalEl = document.getElementById('categorySelectModal') as HTMLSelectElement;

          // init TomSelect if not already
          if (!itemSelectModalEl.tomselect) {
            manualSelect = new TomSelect(itemSelectModalEl, {
              ...sharedTomSelectOptions,
              dropdownParent: '#itemSelectWrapperModal',
              controlInput: '#itemSelectInputModal',
              onChange: (selectedItems: string[]) => {
                const container = document.getElementById('selectedItemsContainerModal')!;
                const display = document.getElementById('selectedItemsDisplayModal')!;
                display.innerHTML = '';
                if (selectedItems.length === 0) {
                  container.classList.add('d-none');
                  return;
                }
                container.classList.remove('d-none');
                selectedItems.forEach(id => {
                  createBadge(itemSelectModalEl, manualSelect, display, id);
                });
              },
            });

            categorySelectModalEl.addEventListener('change', () => {
              const selectedCategory = categorySelectModalEl.value;
              filterOptionsByCategory(itemSelectModalEl, selectedCategory);
            });
          } else {
            manualSelect = itemSelectModalEl.tomselect;
          }

          $('#itemPickerSelectModal').modal('show');

          // confirm handler uses selected TomSelect items
          handleConfirm('confirmItemSelect', () => manualSelect?.items || []);
        }
      },
      // on click activate modal window
      eventClick: function(info): void {
        if (Number(info.event.extendedProps.canbook) === 0) {
          return; // do nothing if event is disabled
        }
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

        // cancel block: show if event is cancellable OR user is Admin)
        const cancelDiv = document.getElementById('isCancellableDiv') as HTMLElement;
        if (!cancelDiv) return;
        const isAdmin = cancelDiv.dataset.isAdmin === 'true';
        const bookIsCancellable = Number(info.event.extendedProps.book_is_cancellable);
        const isCancellable = isAdmin || bookIsCancellable === 1;
        cancelDiv.classList.toggle('d-none', !isCancellable);
        // add event owner's id as target for cancel message
        const targetCancel = document.getElementById('targetCancelEventUsers');
        if (targetCancel) {
          targetCancel.dataset.targetid = info.event.extendedProps.items_id;
        }
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

    initTomSelect();

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
      // EXPORTS
      } else if (el.matches('[data-action="export-scheduler"]')) {
        const from = (document.getElementById('schedulerDateFrom') as HTMLInputElement).value;
        const to = (document.getElementById('schedulerDateTo') as HTMLInputElement).value;
        const currentParams = new URLSearchParams(window.location.search);
        // make an export based on the scheduler's current filters
        const exportUrl = new URL('make.php', window.location.origin);
        exportUrl.searchParams.set('format', 'schedulerReport');
        exportUrl.searchParams.set('start', from);
        exportUrl.searchParams.set('end', to);
        // append item filters
        const items = currentParams.getAll('items[]');
        items.forEach(id => exportUrl.searchParams.append('items[]', id));
        // append category if present
        const category = currentParams.get('category');
        if (category && category !== 'all') {
          exportUrl.searchParams.set('category', category);
        }
        // append owner if present
        const owner = currentParams.get('eventOwner');
        if (owner && owner !== 'all') {
          exportUrl.searchParams.set('eventOwner', owner);
        }
        window.location.href = exportUrl.toString();
      }
    });

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

      const urlParams = new URLSearchParams(window.location.search);
      const selectedItems = urlParams.getAll('items[]');

      const itemTs = new TomSelect(itemSelect, {
        ...sharedTomSelectOptions,
        controlInput: '#itemSelectInput',
        dropdownParent: '#itemSelectWrapper',
        onChange: (selectedItems: string[]) => {
          lockScopeButton(selectedItems);
          const container = document.getElementById('selectedItemsContainer')!;
          const display = document.getElementById('selectedItemsDisplay')!;
          display.innerHTML = '';

          const url = new URL(window.location.href);
          url.searchParams.delete('items[]');
          params.delete('items[]');

          if (selectedItems.length === 0) {
            // not hidden attribute because we play with the wrap
            container.classList.add('d-none');
            window.history.replaceState({}, '', url.toString());
            reloadCalendarEvents();
            return;
          }
          container.classList.remove('d-none');

          selectedItems.forEach(id => {
            createBadge(itemSelect, itemTs, display, id);
          });

          window.history.replaceState({}, '', url.toString());
          reloadCalendarEvents();
        },
      });

      if (selectedItems.length > 0) {
        itemTs.setValue(selectedItems);
        lockScopeButton(selectedItems);
      }

      categorySelect.addEventListener('change', () => {
        const selectedCategory = categorySelect.value;
        filterOptionsByCategory(itemSelect, selectedCategory);
        reloadCalendarEvents();
      });

      if (selectedItems.length > 0) {
        reloadCalendarEvents();
      }
    }
  }
}
