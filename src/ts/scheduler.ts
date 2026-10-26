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
import { ClientSideRowModelModule, createGrid, ModuleRegistry, NumberFilterModule, TextFilterModule } from 'ag-grid-community';
import type { ColDef, GridApi, ICellRendererParams } from 'ag-grid-community';
import 'ag-grid-community/styles/ag-grid.css';
import 'ag-grid-community/styles/ag-theme-alpine.css';
import 'ag-grid-community/styles/ag-theme-quartz.css';
import $ from 'jquery';
import 'bootstrap/js/src/modal.js';
import { DateTime } from 'luxon';
import { ApiC } from './api';
import i18next from './i18n';
import { Action } from './interfaces';
import { collectForm, TomSelect } from './misc';
import { notify } from './notify';
import { on } from './handlers';
import { rebuildTomSelectOptions } from './misc';
import { showModal, showModalAndFocusFirstInput } from './common';
import { getAgGridTheme } from './theme';

ModuleRegistry.registerModules([ClientSideRowModelModule, NumberFilterModule, TextFilterModule]);

type CancelNotificationPayload = {
  action: Action;
  msg: string;
  target: string;
  targetid: number;
  range_direction?: string;
  range_value?: number;
  range_unit?: string;
  notifOnSaved?: number;
  cancel_event?: boolean;
};
type Recurrence = {
  frequency: 'daily' | 'weekly' | 'monthly';
  interval: number;
  count?: number;
  until?: string;
  weekdays?: number[];
};
type BookingRequest = {
  id: number;
  item_title: string;
  fullname: string;
  start: string;
  end: string;
  modified_at: string;
  approval_status: number;
  approval_comment: string | null;
  canapprove: number;
  recurrence_id: string | null;
  recurrence_frequency: 'daily' | 'weekly' | 'monthly' | null;
  recurrence_interval: number | null;
  recurrence_rule: Recurrence | null;
};

type BookingRequestRow = BookingRequest & { occurrences: number; lastStart: string };
type Range = 'day' | 'week' | 'month';
type SavedView = Range | 'listWeek';
const GRID_VIEWS: Record<Range, string> = {
  day: 'timeGridDay',
  week: 'timeGridWeek',
  month: 'dayGridMonth',
};
const TIMELINE_VIEWS: Record<Range, string> = {
  day: 'timelineDay',
  week: 'timelineWeek',
  month: 'timelineMonth',
};
const LIST_WEEK_VIEW = 'listWeek';

// transform a Date object into something we can put as a value of an input of type datetime-local
function toDateTimeInputValueNumber(datetime: Date): number {
  const offset = datetime.getTimezoneOffset() * 60 * 1000;
  return datetime.valueOf() - offset;
}

function setSchedulerMode(mode: 'view' | 'delete'): void {
  document.getElementById('eventViewMode')!.classList.toggle('d-none', mode !== 'view');
  document.getElementById('eventDeleteMode')!.classList.toggle('d-none', mode !== 'delete');
}

on('scheduler-delete-mode', () => setSchedulerMode('delete'));
on('back-to-event', () => setSchedulerMode('view'));

function clearBoundDiv(entity: 'experiment' | 'item') {
  const suffix = entity === 'experiment' ? 'Exp' : 'Item';
  document.getElementById(`eventBound${suffix}`)!.textContent = '';
  const view = document.getElementById(`viewBind${suffix}`) as HTMLAnchorElement;
  view.removeAttribute('href');
  toggleBindState(entity, false);
}

function createBoundDiv(entity: 'experiment' | 'item', title: string, url: string) {
  const suffix = entity === 'experiment' ? 'Exp' : 'Item';
  document.getElementById(`eventBound${suffix}`)!.textContent = title;
  const view = document.getElementById(`viewBind${suffix}`) as HTMLAnchorElement;
  view.href = url;
  toggleBindState(entity, true);
}

function toggleBindState(entity: 'experiment' | 'item', bound: boolean) {
  const suffix = entity === 'experiment' ? 'Exp' : 'Item';
  document.getElementById(`boundInputs${suffix}`)?.classList.toggle('d-none', !bound);
  document.getElementById(`bindInputs${suffix}`)?.classList.toggle('d-none', bound);
}

function lockScopeButtons(selectedItems: string[]): void {
  const showLocked = selectedItems.length > 0;
  ['scopeBtn', 'scopeEventBtn'].forEach(id => {
    document.getElementById(id)?.toggleAttribute('hidden', showLocked);
  });
  ['scopeItemsLocked', 'scopeLocked'].forEach(id => {
    document.getElementById(id)?.toggleAttribute('hidden', !showLocked);
  });
}

document.getElementById('loading-spinner')?.remove();

// map the every/every other/custom controls to the numeric API interval
function getRecurrenceInterval(fields: Element, reportValidity = false): number | false {
  const mode = fields.querySelector<HTMLSelectElement>('.scheduler-recurrence-interval-mode')!.value;
  if (mode === 'every') {
    return 1;
  }
  if (mode === 'other') {
    return 2;
  }
  const interval = fields.querySelector<HTMLInputElement>('.scheduler-recurrence-interval')!;
  if (!interval.checkValidity()) {
    if (reportValidity) {
      interval.reportValidity();
    }
    return false;
  }
  return interval.valueAsNumber;
}

// Keep recurrence controls and singular/plural labels in sync with the current form state
function updateRecurrenceFields(fields: Element): void {
  const enabled = fields.querySelector<HTMLInputElement>('.scheduler-recurrence-enabled')!;
  fields.querySelectorAll('.scheduler-recurrence-options')
    .forEach(element => element.classList.toggle('d-none', !enabled.checked));
  if (!enabled.checked) {
    return;
  }

  const intervalMode = fields.querySelector<HTMLSelectElement>('.scheduler-recurrence-interval-mode')!;
  const interval = getRecurrenceInterval(fields);
  fields.querySelector('.scheduler-recurrence-custom-interval')
    ?.classList.toggle('d-none', intervalMode.value !== 'custom');
  fields.querySelectorAll<HTMLOptionElement>('.scheduler-recurrence-frequency option').forEach(option => {
    option.textContent = interval === 1 ? option.dataset.singular ?? '' : option.dataset.plural ?? '';
  });

  const frequency = fields.querySelector<HTMLSelectElement>('.scheduler-recurrence-frequency')!;
  const isWeekly = frequency.value === 'weekly';
  fields.querySelector('.scheduler-recurrence-weekly')?.classList.toggle('d-none', !isWeekly);
  const weekdayMode = fields.querySelector<HTMLSelectElement>('.scheduler-recurrence-weekday-mode')!;
  fields.querySelector('.scheduler-recurrence-weekdays')
    ?.classList.toggle('d-none', !isWeekly || weekdayMode.value !== 'custom');

  const endMode = fields.querySelector<HTMLSelectElement>('.scheduler-recurrence-end-mode')!;
  fields.querySelector('.scheduler-recurrence-count-wrapper')?.classList.toggle('d-none', endMode.value !== 'count');
  fields.querySelector('.scheduler-recurrence-until-wrapper')?.classList.toggle('d-none', endMode.value !== 'date');
}

// configure weekday shortcuts and end-date limits from the calendar slot used as the anchor occurrence
function configureRecurrenceFields(fields: HTMLElement, start: Date, locale: string): void {
  const startDate = DateTime.fromJSDate(start).setLocale(locale);
  const startWeekday = startDate.weekday;
  const secondDate = startDate.plus({ days: 2 });
  fields.dataset.startWeekday = String(startWeekday);
  fields.dataset.secondWeekday = String(secondDate.weekday);

  const weekdayMode = fields.querySelector<HTMLSelectElement>('.scheduler-recurrence-weekday-mode')!;
  const onDay = fields.dataset.onDay ?? 'on %s';
  const onTwoDays = fields.dataset.onTwoDays ?? 'on %s & %s';
  weekdayMode.querySelector<HTMLOptionElement>('option[value="start"]')!.textContent =
    onDay.replace('%s', startDate.toFormat('cccc'));
  weekdayMode.querySelector<HTMLOptionElement>('option[value="start-and-two-days"]')!.textContent =
    onTwoDays.replace('%s', startDate.toFormat('cccc')).replace('%s', secondDate.toFormat('cccc'));
  const weekdaysOption = weekdayMode.querySelector<HTMLOptionElement>('option[value="weekdays"]')!;
  weekdaysOption.disabled = startWeekday > 5;
  if (weekdaysOption.disabled && weekdayMode.value === 'weekdays') {
    weekdayMode.value = 'start';
  }

  // The anchor weekday must stay selected because the first booking is part of the recurrence
  fields.querySelectorAll<HTMLInputElement>('.scheduler-recurrence-weekday').forEach(checkbox => {
    const isStartDay = Number(checkbox.value) === startWeekday;
    checkbox.checked = isStartDay;
    checkbox.disabled = isStartDay;
  });

  // give date-based recurrence a useful default while preventing dates before the anchor
  const until = fields.querySelector<HTMLInputElement>('.scheduler-recurrence-until')!;
  const startDateValue = startDate.toISODate()!;
  until.min = startDateValue;
  if (until.value === '' || until.value < startDateValue) {
    until.value = startDate.plus({ months: 1 }).toISODate()!;
  }
  updateRecurrenceFields(fields);
}

document.querySelectorAll<HTMLElement>('.scheduler-recurrence-fields').forEach(fields => {
  fields.querySelectorAll('select, input').forEach(input => {
    input.addEventListener('change', () => updateRecurrenceFields(fields));
  });
  fields.querySelector<HTMLInputElement>('.scheduler-recurrence-interval')
    ?.addEventListener('input', () => updateRecurrenceFields(fields));
  updateRecurrenceFields(fields);
});

// null means recurrence disabled, false means invalid input, otherwise return API payload
function getRecurrence(modal: Element): Recurrence | null | false {
  const fields = modal.querySelector<HTMLElement>('.scheduler-recurrence-fields')!;
  const enabled = fields.querySelector<HTMLInputElement>('.scheduler-recurrence-enabled')!;
  if (!enabled.checked) {
    return null;
  }

  const interval = getRecurrenceInterval(fields, true);
  if (interval === false) {
    return false;
  }
  const frequency = fields.querySelector<HTMLSelectElement>('.scheduler-recurrence-frequency')!;
  const recurrence: Recurrence = {
    frequency: frequency.value as Recurrence['frequency'],
    interval,
  };

  if (recurrence.frequency === 'weekly') {
    const startWeekday = Number(fields.dataset.startWeekday);
    const secondWeekday = Number(fields.dataset.secondWeekday);
    const weekdayMode = fields.querySelector<HTMLSelectElement>('.scheduler-recurrence-weekday-mode')!.value;
    if (weekdayMode === 'start') {
      recurrence.weekdays = [startWeekday];
    } else if (weekdayMode === 'start-and-two-days') {
      recurrence.weekdays = [startWeekday, secondWeekday].sort((left, right) => left - right);
    } else if (weekdayMode === 'weekdays') {
      recurrence.weekdays = [1, 2, 3, 4, 5];
    } else {
      recurrence.weekdays = Array.from(
        fields.querySelectorAll<HTMLInputElement>('.scheduler-recurrence-weekday:checked'),
        checkbox => Number(checkbox.value),
      ).sort((left, right) => left - right);
    }
  }

  const endMode = fields.querySelector<HTMLSelectElement>('.scheduler-recurrence-end-mode')!.value;
  if (endMode === 'count') {
    const count = fields.querySelector<HTMLInputElement>('.scheduler-recurrence-count')!;
    if (!count.checkValidity()) {
      count.reportValidity();
      return false;
    }
    recurrence.count = count.valueAsNumber;
  } else {
    const until = fields.querySelector<HTMLInputElement>('.scheduler-recurrence-until')!;
    if (until.value === '' || !until.checkValidity()) {
      until.reportValidity();
      return false;
    }
    recurrence.until = until.value;
  }

  return recurrence;
}

function getRecurrenceDescription(container: HTMLElement, recurrence: Recurrence, locale: string): string {
  const key = recurrence.interval === 1 ? recurrence.frequency : `${recurrence.frequency}Interval`;
  const template = container.dataset[key] ?? '';
  let description = template.replace('%d', String(recurrence.interval));

  if (recurrence.frequency === 'weekly' && recurrence.weekdays?.length) {
    // Use a known Monday only as an ISO weekday anchor for localized weekday names
    const monday = DateTime.fromISO('2026-09-14').setLocale(locale);
    const weekdayNames = recurrence.weekdays.map(weekday => monday.plus({ days: weekday - 1 }).toFormat('cccc'));
    const weekdayList = new Intl.ListFormat(locale, { style: 'long', type: 'conjunction' }).format(weekdayNames);
    description += ` ${(container.dataset.onDays ?? 'on %s').replace('%s', weekdayList)}`;
  }

  if (recurrence.count !== undefined) {
    const countTemplate = recurrence.count === 1
      ? container.dataset.endOnce ?? 'and ends after one occurrence'
      : container.dataset.endCount ?? 'and ends after %d occurrences';
    description += ` ${countTemplate.replace('%d', String(recurrence.count))}`;
  } else if (recurrence.until) {
    const endDate = DateTime.fromISO(recurrence.until).setLocale(locale).toLocaleString(DateTime.DATE_MED);
    description += ` ${(container.dataset.endDate ?? 'and ends on %s').replace('%s', endDate)}`;
  }

  return `${description}.`;
}

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
const currentUserId = Number(calendarEl?.dataset.userId);
const isAdmin = calendarEl?.dataset.isAdmin === 'true';
if (calendarEl) {
  const eventResourceSelectEl = document.getElementById('eventResourceSelect') as HTMLSelectElement;
  const changeEventResourceBtn = document.getElementById('changeEventResourceBtn') as HTMLButtonElement;
  const viewBookedResource = document.getElementById('viewBookedResource') as HTMLAnchorElement;
  const eventResourceSelect = new TomSelect(eventResourceSelectEl, {
    maxItems: 1,
    create: false,
    plugins: ['dropdown_input', 'no_active_items', 'clear_button'],
    placeholder: eventResourceSelectEl.options[0]?.text,
    onChange: value => {
      const selectedItemId = String(value);
      viewBookedResource.href = selectedItemId ? `database.php?mode=view&id=${selectedItemId}` : '#';
      changeEventResourceBtn.disabled = selectedItemId === '' || selectedItemId === changeEventResourceBtn.dataset.itemId;
    },
  });

  const layoutCheckbox = document.getElementById('scheduler_layout') as HTMLInputElement;
  const layout = (layoutCheckbox && layoutCheckbox.checked)
    ? 'timelineDay,timelineWeek,listWeek,timelineMonth' // horizontal axis
    : 'timeGridDay,timeGridWeek,listWeek,dayGridMonth'; // classic grid calendar

  // persist selected view type (day, week, month, and the layout)
  const saved = localStorage.getItem('persistent_schedulerRange') as SavedView | null;
  const range: Range = saved && saved !== LIST_WEEK_VIEW ? saved : 'week';
  const viewMap = layoutCheckbox.checked ? TIMELINE_VIEWS : GRID_VIEWS;
  const initialView =
    saved === LIST_WEEK_VIEW
      ? LIST_WEEK_VIEW
      : viewMap[range];

  const categorySelect = document.getElementById('categorySelect') as HTMLSelectElement;
  categorySelect.value = params.get('category') ?? '';

  // remove existing params to build new event sources for the calendar
  function buildEventSourcesUrl(): string {
    ['items[]', 'category', 'eventOwner'].forEach((param) => params.delete(param));
    const itemSelect = document.getElementById('itemSelect') as HTMLSelectElement & { tomselect?: TomSelect };
    const ownerInput = document.getElementById('eventOwnerSelect') as HTMLInputElement;

    if (itemSelect?.tomselect?.items?.length) {
      lockScopeButtons(itemSelect.tomselect.items);
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
    clearBoundDiv('experiment');
    clearBoundDiv('item');
    if (extendedProps.experiment && extendedProps.experiment_title) {
      createBoundDiv('experiment', extendedProps.experiment_title, `experiments.php?mode=view&id=${extendedProps.experiment}`);
    }
    if (extendedProps.item_link && extendedProps.item_link_title) {
      createBoundDiv('item', extendedProps.item_link_title, `database.php?mode=view&id=${extendedProps.item_link}`);
    }
  }

  // create self-removable badge for selected items (in scheduler & modal)
  const createBadge = (selectInput, tomSelect, wrapper, id) => {
    const opt = selectInput.querySelector(`option[value="${id}"]`) as HTMLOptionElement;
    if (!opt) return;

    const colorCircle = document.createElement('i');
    colorCircle.classList.add('fas', 'fa-circle');
    const rawColor = opt.dataset.color;
    colorCircle.style.color = rawColor?.startsWith('#') ? rawColor : `#${rawColor || '0c58ab'}`;
    const badge = document.createElement('span');
    badge.appendChild(colorCircle);
    badge.className = 'selected-item-badge';
    const link = document.createElement('a');
    link.textContent = opt.textContent;
    link.href = `database.php?mode=view&id=${encodeURIComponent(id)}`;
    link.target = '_blank';
    link.rel = 'noopener';
    // background color for badges
    badge.style.backgroundColor = 'var(--superlight)';

    const removeBtn = document.createElement('button');
    removeBtn.type = 'button';
    removeBtn.ariaLabel = i18next.t('filter-delete-warning');
    removeBtn.className = 'ml-2 close';
    const removeBtnIcon = document.createElement('i');
    removeBtnIcon.classList.add('fas', 'fa-xmark', 'fa-fw');
    removeBtn.appendChild(removeBtnIcon);

    badge.append(link, removeBtn);
    wrapper.appendChild(badge);

    // also handle keydown (enter)
    const removeBadgeHandler = e => {
      e.preventDefault();
      removeBadge(badge, tomSelect, id);
    };
    removeBtn.addEventListener('click', removeBadgeHandler);
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
    initialView: initialView,
    datesSet: (info) => {
      const range =
        info.view.type === 'listWeek' ? 'listWeek' :
          info.view.type.includes('Day') ? 'day' :
            info.view.type.includes('Month') ? 'month' :
              'week';
      localStorage.setItem('persistent_schedulerRange', range);
    },
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
    // background color before event validation
    eventBackgroundColor: 'var(--chrome-bg)',
    // user can see events as disabled if they don't have booking permissions. See #5930
    eventClassNames: (info) => {
      const canBook = Number(info.event.extendedProps.canbook);
      const eventOwnerId = Number(info.event.extendedProps.userid);
      const classNames = ['scheduler-event-colored'];
      if (Number(info.event.extendedProps.approval_status) === 0) {
        classNames.push('scheduler-event-pending');
      }
      if (canBook === 0 && currentUserId !== eventOwnerId && Number(info.event.extendedProps.canapprove) !== 1) {
        classNames.push('calendar-event-disabled');
      }
      return classNames;
    },
    // apply the category color to the scheduler event style
    eventDidMount: (info) => {
      const eventColor = info.event.backgroundColor || info.event.borderColor || '#0c58ab';
      info.el.style.setProperty('--scheduler-event-color', eventColor);
    },
    // Pending approval cannot be moved by drag and drop.
    eventAllow: (info, event) => Number(event.extendedProps.canbook) === 1
      && Number(event.extendedProps.approval_status) !== 0,
    // selection
    select: function(info): void {
      const itemSelectEl = document.getElementById('itemSelect') as HTMLSelectElement & { tomselect?: TomSelect };
      const selectedItemIds: string[] = itemSelectEl.tomselect?.items || [];

      // Handle post action for modal
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

          const modal = confirmBtn.closest('.modal');
          const titleInput = modal?.querySelector<HTMLInputElement>('input[id^="eventTitleInput"]');
          const title = titleInput ? titleInput.value.trim() : '';

          const recurrence = getRecurrence(modal!);
          if (recurrence === false) {
            return;
          }
          const postParams: {
            start: string;
            end: string;
            title: string;
            recurrence?: Recurrence;
          } = {
            start: info.startStr,
            end: info.endStr,
            title,
          };

          if (recurrence !== null) {
            postParams.recurrence = recurrence;
          }

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

      const itemSelectModalEl = document.getElementById('itemSelectModal') as HTMLSelectElement & { tomselect?: TomSelect };
      const categorySelectModalEl = document.getElementById('categorySelectModal') as HTMLSelectElement;

      const renderSelectedItems = (selectedItems: string[]): void => {
        const container = document.getElementById('selectedItemsContainerModal')!;
        const display = document.getElementById('selectedItemsDisplayModal')!;
        display.innerHTML = '';
        if (selectedItems.length === 0) {
          container.classList.add('d-none');
          return;
        }
        container.classList.remove('d-none');
        selectedItems.forEach(id => {
          createBadge(itemSelectModalEl, itemSelectModalEl.tomselect, display, id);
        });
      };

      let manualSelect: TomSelect;
      if (!itemSelectModalEl.tomselect) {
        manualSelect = new TomSelect(itemSelectModalEl, {
          ...sharedTomSelectOptions,
          dropdownParent: '#itemSelectWrapperModal',
          controlInput: '#itemSelectInputModal',
          onChange: renderSelectedItems,
        });

        categorySelectModalEl.addEventListener('change', () => {
          filterOptionsByCategory(itemSelectModalEl, categorySelectModalEl.value);
        });
      } else {
        manualSelect = itemSelectModalEl.tomselect;
      }

      // preselect resources currently selected in the Scheduler (shows badges)
      manualSelect.clear(true);
      manualSelect.setValue(selectedItemIds, true);
      renderSelectedItems(selectedItemIds);

      // Restore the correct recurrence fields when reopening the modal
      const recurrenceFields = document.querySelector<HTMLElement>(
        '#itemPickerSelectModal .scheduler-recurrence-fields',
      );
      if (recurrenceFields) {
        configureRecurrenceFields(recurrenceFields, info.start, calendarEl.dataset.lang || 'en');
      }
      showModal('#itemPickerSelectModal');
      handleConfirm('confirmItemSelect', () => manualSelect.items);
    },
    // on click activate modal window
    eventClick: function(info): void {
      const canBook = Number(info.event.extendedProps.canbook);
      const eventOwnerId = Number(info.event.extendedProps.userid);
      const isPending = Number(info.event.extendedProps.approval_status) === 0;
      if (isPending || (canBook === 0 && currentUserId !== eventOwnerId)) {
        return;
      }
      setSchedulerMode('view');
      showModalAndFocusFirstInput('#eventModal');

      // Show the booked resource and booker directly in the modal header
      const modalLabel = document.getElementById('eventModalLabel')!;
      const resourceTitle = info.event.extendedProps.item_title || i18next.t('Event');
      const categoryTitle = info.event.extendedProps.items_category_title || '';
      const booker = info.event.extendedProps.fullname || '';
      const bookedBy = booker ? (modalLabel.dataset.bookedBy ?? '%s').replace('%s', booker) : '';
      document.getElementById('eventModalResourceTitle')!.textContent = resourceTitle;
      document.getElementById('eventModalResourceMeta')!.textContent = [categoryTitle, bookedBy].filter(Boolean).join(' · ');
      const requiresBookingApproval = Number(info.event.extendedProps.requires_booking_approval) === 1;
      document.getElementById('eventModalApprovalHelp')!.classList.toggle('d-none', !requiresBookingApproval);

      // Preselect the resource currently reserved by this event
      const itemId = String(info.event.extendedProps.items_id);
      if (!eventResourceSelect.options[itemId]) {
        eventResourceSelect.addOption({ value: itemId, text: resourceTitle });
      }
      eventResourceSelect.setValue(itemId, true);
      if (requiresBookingApproval) {
        eventResourceSelect.disable();
      } else {
        eventResourceSelect.enable();
      }
      changeEventResourceBtn.dataset.id = info.event.id;
      changeEventResourceBtn.dataset.itemId = itemId;
      changeEventResourceBtn.disabled = true;
      changeEventResourceBtn.classList.toggle('d-none', requiresBookingApproval);
      viewBookedResource.href = `database.php?mode=view&id=${itemId}`;

      // set the event id on the various elements
      document.querySelectorAll('[data-action="scheduler-bind-entity"]').forEach((btn: HTMLButtonElement) => btn.dataset.id = info.event.id);
      document.querySelectorAll('[data-action="scheduler-rm-bind"]').forEach((btn: HTMLButtonElement) => btn.dataset.eventid = info.event.id);
      document.querySelectorAll('[data-action="cancel-event"], [data-action="cancel-event-with-message"]')
        .forEach((btn: HTMLButtonElement) => btn.dataset.id = info.event.id);

      // title
      const title = document.getElementById('title') as HTMLInputElement;
      title.value = info.event.extendedProps.title_only;
      // set the event id on the title
      title.dataset.eventid = info.event.id;

      // start and end inputs values
      startInput.valueAsNumber = toDateTimeInputValueNumber(info.event.start);
      endInput.valueAsNumber = toDateTimeInputValueNumber(info.event.end);
      startInput.disabled = requiresBookingApproval;
      endInput.disabled = requiresBookingApproval;
      // also adjust the event id so the change listener will send a correct query
      startInput.dataset.eventid = info.event.id;
      endInput.dataset.eventid = info.event.id;
      refreshBoundDivs(info.event.extendedProps);

      // The event modal is reused, so reset recurrence visibility and scope selection on every open
      const isRecurring = Boolean(info.event.extendedProps.recurrence_id);
      document.getElementById('eventResourceRecurrenceHelp')?.classList.toggle('d-none', !isRecurring || requiresBookingApproval);
      const viewRecurrence = document.getElementById('viewRecurrence')!;
      const viewRecurrenceText = document.getElementById('viewRecurrenceText')!;
      viewRecurrence.classList.toggle('d-none', !isRecurring);
      viewRecurrenceText.textContent = '';
      if (isRecurring) {
        const recurrence = info.event.extendedProps.recurrence_rule as Recurrence;
        if (recurrence) {
          viewRecurrenceText.textContent = getRecurrenceDescription(viewRecurrence, recurrence, calendarEl.dataset.lang || 'en');
        }
      }
      document.getElementById('editRecurrenceScope')?.classList.toggle('d-none', !isRecurring);
      document.getElementById('deleteRecurrenceScope')?.classList.toggle('d-none', !isRecurring);
      (document.getElementById('editScopeEvent') as HTMLInputElement).checked = true;
      (document.getElementById('deleteScopeEvent') as HTMLInputElement).checked = true;

      // cancel block: show if event is cancellable OR user is Admin)
      const bookIsCancellable = Number(info.event.extendedProps.book_is_cancellable);
      const isCancellable = isAdmin || bookIsCancellable === 1;
      const deleteBtn = document.getElementById('deleteEventBtn');
      if (deleteBtn) {
        deleteBtn.classList.toggle('d-none', !isCancellable);
      }
      // add owner ids as target for cancel message
      ['targetCancelEventUsers', 'targetCancelEventUsersRange'].forEach(id => {
        const el = document.getElementById(id);
        if (el) {
          el.dataset.targetid = info.event.extendedProps.items_id;
        }
      });
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
    // a drop means we change start date/time
    eventDrop: handleEventDateChange,
    // a resize means we change end date/time
    eventResize: handleEventDateChange,
  });

  initTomSelect();

  // only try to render if we actually have some bookable items
  if (calendarEl.dataset.render === 'true') {
    calendar.render();
    calendar.updateSize();
  }

  // The API returns one event per occurrence; approvals already apply to the whole recurrence.
  const bookingGrids: Partial<Record<'my' | 'pending', GridApi<BookingRequestRow>>> = {};
  const bookingLocale = i18next.language.replace('_', '-');
  const element = <T extends keyof HTMLElementTagNameMap>(tag: T, className = '', text?: string): HTMLElementTagNameMap[T] => {
    const node = document.createElement(tag);
    node.className = className;
    if (text !== undefined) node.textContent = text;
    return node;
  };
  const formatBookingDuration = (start: DateTime, end: DateTime): string => {
    const minutes = Math.max(0, Math.round(end.diff(start, 'minutes').minutes));
    return ([
      ['day', Math.floor(minutes / 1440)],
      ['hour', Math.floor((minutes % 1440) / 60)],
      ['minute', minutes % 60],
    ] as const)
      .filter(([, value]) => value > 0)
      .map(([unit, value]) => new Intl.NumberFormat(bookingLocale, { style: 'unit', unit, unitDisplay: 'long' }).format(value))
      .join(' ') || new Intl.NumberFormat(bookingLocale, { style: 'unit', unit: 'minute', unitDisplay: 'long' }).format(0);
  };

  function renderBookingSchedule(booking: BookingRequestRow): HTMLElement {
    const start = DateTime.fromSQL(booking.start).setLocale(bookingLocale);
    const end = DateTime.fromSQL(booking.end).setLocale(bookingLocale);
    const endLabel = end.hasSame(start, 'day') ? end.toLocaleString(DateTime.TIME_SIMPLE) : end.toLocaleString(DateTime.DATETIME_MED);
    const container = element('div', 'd-flex flex-column justify-content-center h-100');
    container.style.lineHeight = '1.5';
    const slot = element('div');
    slot.append(element('strong', 'mr-2', formatBookingDuration(start, end)),
      element('span', 'smallgray', `${start.toLocaleString(DateTime.DATETIME_MED)} → ${endLabel}`));
    container.appendChild(slot);
    if (booking.recurrence_id) {
      const cadenceUnits = { daily: 'day', weekly: 'week', monthly: 'month' } as const;
      const unit = booking.recurrence_frequency ? cadenceUnits[booking.recurrence_frequency] : null;
      const interval = unit ? new Intl.NumberFormat(bookingLocale, { style: 'unit', unit, unitDisplay: 'long' }).format(booking.recurrence_interval || 1) : null;
      const cadence = unit && booking.recurrence_interval === 1
        ? i18next.t(`booking-every-${unit}`)
        : interval ? i18next.t('booking-repeats-every', { interval }) : null;
      container.appendChild(element('div', 'smallgray', [
        i18next.t('booking-recurring'),
        i18next.t(booking.occurrences === 1 ? 'booking-one-occurrence' : 'booking-occurrences', { num: booking.occurrences }),
        ...(cadence ? [cadence] : []),
      ].join(' · ')));
      if (booking.recurrence_rule?.weekdays?.length) {
        const monday = DateTime.fromISO('2026-09-14').setLocale(bookingLocale);
        const weekdays = booking.recurrence_rule.weekdays.map(day => monday.plus({ days: day - 1 }).toFormat('ccc'));
        container.appendChild(element('div', 'smallgray', i18next.t('booking-on-weekdays', { days: new Intl.ListFormat(bookingLocale, { type: 'conjunction' }).format(weekdays) })));
      }
      const last = DateTime.fromSQL(booking.lastStart).setLocale(bookingLocale);
      container.appendChild(element('div', 'smallgray', i18next.t('booking-last-occurrence', { date: last.toLocaleString(DateTime.DATETIME_MED) })));
    }
    return container;
  }

  function renderBookingActions(booking: BookingRequestRow, kind: 'my' | 'pending'): HTMLElement {
    const actions = element('div', 'd-flex align-items-center pt-2');
    if (kind === 'pending' && Number(booking.canapprove) === 1) {
      for (const [action, style] of [['booking-approve', 'btn-primary'], ['booking-reject', 'btn-danger-ghost']]) {
        const button = element('button', `btn btn-sm mr-2 ${style}`, i18next.t(action));
        button.type = 'button';
        button.dataset.action = action;
        button.dataset.eventid = String(booking.id);
        actions.appendChild(button);
      }
    } else if (kind === 'my' && Number(booking.approval_status) === 0) {
      const cancel = element('button', 'btn btn-sm btn-danger-ghost', i18next.t('booking-cancel-request'));
      cancel.type = 'button';
      cancel.addEventListener('click', async () => {
        if (!window.confirm(i18next.t('booking-cancel-confirm'))) return;
        const endpoint = booking.recurrence_id
          ? `event/${booking.id}/recurrences?recursive=1`
          : `event/${booking.id}`;
        try {
          await ApiC.delete(endpoint);
          calendar.refetchEvents();
          void loadBookingRequests('my');
        } catch {
          // ApiC already notifies the user.
        }
      });
      actions.appendChild(cancel);
    }
    return actions;
  }

  async function loadBookingRequests(kind: 'my' | 'pending'): Promise<void> {
    const list = document.getElementById(kind === 'my' ? 'bookingRequestsMyList' : 'bookingRequestsPendingList')!;
    if (!bookingGrids[kind]) list.textContent = i18next.t('booking-loading');
    try {
      // Fetch the full recurrence, so both its count and its first occurrence are correct.
      const bookings = await ApiC.getJson<BookingRequest[]>(`events?approval_view=${kind}&start=2012-12-31&end=9999-12-31`);
      const groups = new Map<string, BookingRequestRow>();
      for (const booking of bookings) {
        const key = booking.recurrence_id ?? `event:${booking.id}`;
        const row = groups.get(key);
        if (!row) {
          groups.set(key, { ...booking, occurrences: 1, lastStart: booking.start });
        } else {
          row.occurrences++;
          if (booking.start > row.lastStart) row.lastStart = booking.start;
          // by default, display by the last updated event
          const modifiedAt = row.modified_at > booking.modified_at ? row.modified_at : booking.modified_at;
          if (booking.start < row.start) Object.assign(row, booking, { occurrences: row.occurrences, lastStart: row.lastStart });
          row.modified_at = modifiedAt;
        }
      }
      const rows = Array.from(groups.values()).sort((a, b) => b.modified_at.localeCompare(a.modified_at));
      if (bookingGrids[kind]) {
        bookingGrids[kind]!.setGridOption('rowData', rows);
        return;
      }
      list.replaceChildren();
      list.className = getAgGridTheme();
      const statusNames = ['booking-status-pending', 'booking-status-approved', 'booking-status-rejected', 'booking-status-expired'];
      const statusColors = [
        ['lightgold', 'darkgold', 'gold'],
        ['primary', 'primary-fg', 'primary'],
        ['lightred', 'darkred', 'dangerred'],
        ['firstlevel', 'mediumstrong', 'secondlevel'],
      ];
      const columnDefs: ColDef<BookingRequestRow>[] = [
        { field: 'item_title', headerName: i18next.t('item'), flex: 1, minWidth: 160,
          cellRenderer: ({ data }: ICellRendererParams<BookingRequestRow>) => {
            if (!data) return null;
            if (kind !== 'my' || Number(data.approval_status) !== 2 || !data.approval_comment) return data.item_title;
            const container = element('div', 'd-flex flex-column justify-content-center h-100');
            container.style.lineHeight = '1.5';
            container.appendChild(element('div', 'text-truncate', data.item_title));
            const reason = element('div', 'smallgray text-truncate', `${i18next.t('booking-reason')}: ${data.approval_comment}`);
            reason.title = data.approval_comment;
            container.appendChild(reason);
            return container;
          } },
        ...(kind === 'pending' ? [{ field: 'fullname' as const, headerName: i18next.t('booking-requested-by'), flex: 1, minWidth: 150 }] : []),
        { field: 'start', headerName: i18next.t('booking-when'), flex: 2, minWidth: 350,
          cellRenderer: ({ data }: ICellRendererParams<BookingRequestRow>) => data ? renderBookingSchedule(data) : null },
        { field: 'approval_status', headerName: i18next.t('status'), width: 155,
          cellRenderer: ({ data }: ICellRendererParams<BookingRequestRow>) => {
            if (!data) return null;
            const colors = statusColors[Number(data.approval_status)] ?? statusColors[3];
            const badge = element('span', 'badge p-2', i18next.t(statusNames[Number(data.approval_status)] ?? 'booking-status-unknown'));
            badge.style.backgroundColor = `var(--${colors[0]})`;
            badge.style.color = `var(--${colors[1]})`;
            badge.style.border = `1px solid var(--${colors[2]})`;
            const wrapper = element('div', 'd-flex align-items-center pt-2');
            wrapper.appendChild(badge);
            return wrapper;
          } },
        { colId: 'actions', headerName: i18next.t('action'), width: kind === 'pending' ? 195 : 175, sortable: false, filter: false,
          cellRenderer: ({ data }: ICellRendererParams<BookingRequestRow>) => data ? renderBookingActions(data, kind) : null },
      ];
      bookingGrids[kind] = createGrid<BookingRequestRow>(list, {
        theme: 'legacy',
        columnDefs,
        rowData: rows,
        getRowId: params => params.data.recurrence_id ?? `event:${params.data.id}`,
        domLayout: 'autoHeight',
        getRowHeight: params => params.data?.recurrence_id ? (params.data.recurrence_rule?.weekdays?.length ? 110 : 90) : 54,
        defaultColDef: { sortable: true, resizable: true, filter: true },
        overlayNoRowsTemplate: i18next.t('booking-no-requests'),
      });
    } catch {
      if (!bookingGrids[kind]) list.textContent = i18next.t('error-booking-requests-load');
      // ApiC already displays the API error; avoid a duplicate notification.
    }
  }

  // The standard Tab helper handles content visibility and URL state.
  // Fetch only the active list and resize FullCalendar when returning to it.
  document.querySelectorAll<HTMLButtonElement>('.tabbed-menu [data-tabtarget]').forEach(tab => {
    tab.addEventListener('click', () => {
      const target = Number(tab.dataset.tabtarget);
      if (target === 1) {
        window.requestAnimationFrame(() => calendar.updateSize());
      } else if (target === 2 || target === 3) {
        void loadBookingRequests(target === 2 ? 'my' : 'pending');
      }
    });
  });
  const initialTab = new URLSearchParams(window.location.search).get('tab');
  if (initialTab === '2' || initialTab === '3') {
    void loadBookingRequests(initialTab === '2' ? 'my' : 'pending');
  }

  async function decideBooking(el: HTMLElement, decision: 'approved' | 'rejected', comment?: string): Promise<void> {
    try {
      await ApiC.patch(`event/${el.dataset.eventid}`, { action: Action.Update, target: 'approval', decision, comment });
      $('#eventModal').modal('hide');
      calendar.refetchEvents();
      void loadBookingRequests('pending');
    } catch {
      // ApiC already notifies the user.
    }
  }

  on('booking-approve', (el: HTMLElement) => decideBooking(el, 'approved'));
  on('booking-reject', (el: HTMLElement) => {
    const reason = window.prompt(i18next.t('booking-rejection-reason'));
    if (reason !== null) return decideBooking(el, 'rejected', reason);
  });

  on('cancel-event', (el: HTMLElement) => {
    const scope = (document.querySelector('input[name="deleteRecurrenceScope"]:checked') as HTMLInputElement).value;
    const endpoint = scope === 'event'
      ? `event/${el.dataset.id}`
      : `event/${el.dataset.id}/recurrences${scope === 'future' ? '?recursive=1' : ''}`;
    ApiC.delete(endpoint).then(() => calendar.refetchEvents()).catch();
  });

  on('cancel-event-with-message', (el: HTMLElement) => {
    const target = document.querySelector('input[name="targetCancelEvent"]:checked') as HTMLInputElement;
    const msg = (document.getElementById('cancelEventTextarea') as HTMLTextAreaElement).value;
    const payload: CancelNotificationPayload = {
      action: Action.Create,
      msg: msg,
      target: target.value,
      targetid: parseInt(target.dataset.targetid, 10),
    };
    if (target.value === 'bookable_item_range') {
      payload.range_direction = (document.getElementById('cancelEventRangeDirection') as HTMLSelectElement).value;
      payload.range_value = parseInt((document.getElementById('cancelEventRangeValue') as HTMLInputElement).value, 10);
      payload.range_unit = (document.getElementById('cancelEventRangeUnit') as HTMLSelectElement).value;
    }
    payload.notifOnSaved = 0;
    // Delete inside the notification request so a failed cancellation cannot still send cancellation notifications
    payload.cancel_event = true;
    ApiC.post(`event/${el.dataset.id}/notifications`, payload)
      .then(() => calendar.refetchEvents())
      .then(() => notify.success());
  });

  on('edit-event', async (_, e: Event) => {
    e.preventDefault();
    const form = document.getElementById('editEventForm') as HTMLFormElement;
    const params = collectForm(form);
    const scope = String(params['scope'] ?? 'event');
    delete params['scope'];
    const eventId = startInput.dataset.eventid;
    if (!eventId) {
      notify.error('form-validation-error');
      return;
    }
    if (!startInput.disabled) {
      const startVal = startInput.valueAsNumber;
      const endVal = endInput.valueAsNumber;

      if (isNaN(startVal) || isNaN(endVal)) {
        notify.error('Invalid date values.');
        return;
      }
      // Validate start < end
      if (endVal < startVal) {
        notify.error(`End time ${endInput.value} cannot be inferior to start time ${startInput.value}.`);
        return;
      }
      // Convert to Luxon DateTime
      const startDt = DateTime.fromISO(startInput.value, { zone: 'system' });
      const endDt = DateTime.fromISO(endInput.value, { zone: 'system' });
      if (!startDt.isValid || !endDt.isValid) {
        notify.error('invalid-info');
        return;
      }
      // convert both inputs to proper ISO with timezone. also suppress milliseconds for cleaner payload
      params['start'] = startDt.toISO({ suppressMilliseconds: true });
      params['end'] = endDt.toISO({ suppressMilliseconds: true });
    }
    params['target'] = 'datetime';
    const endpoint = scope === 'event'
      ? `event/${eventId}`
      : `event/${eventId}/recurrences${scope === 'future' ? '?future=1' : ''}`;
    try {
      await ApiC.patch(endpoint, params);
      calendar.refetchEvents();
      $('#eventModal').modal('hide');
    } catch (err) {
      notify.error(err);
    }
  });

  on('change-event-resource', async (el: HTMLElement) => {
    const eventId = el.dataset.id;
    const itemId = Number(eventResourceSelect.getValue());
    if (!eventId || !Number.isInteger(itemId) || itemId < 1) {
      notify.error('form-validation-error');
      return;
    }
    try {
      await ApiC.patch(`event/${eventId}`, { target: 'item', id: itemId });
      calendar.refetchEvents();
      $('#eventModal').modal('hide');
      notify.success();
    } catch (err) {
      notify.error(err);
    }
  });

  on('scheduler-bind-entity', (el: HTMLElement) => {
    const inputEl = el.parentNode.parentNode.querySelector('input') as HTMLInputElement;
    const entityid = parseInt((inputEl.value as string), 10);
    if (entityid > 0) {
      ApiC.patch(`event/${el.dataset.id}`, {target: el.dataset.type, id: entityid}).then(res => res.json()).then(json => {
        calendar.refetchEvents();
        refreshBoundDivs(json);
        inputEl.value = '';
      });
    }
  });

  on('scheduler-rm-bind', (el: HTMLElement) => {
    const bindType = el.dataset.type;
    ApiC.patch(`event/${el.dataset.eventid}`, {'target': bindType, 'id': null}).then(() => {
      clearBoundDiv(bindType as 'experiment' | 'item');
      // clear the inputs
      document.querySelectorAll('.bindInput').forEach((input:HTMLInputElement) => input.value = '');
      calendar.refetchEvents();
    });
  });

  on('filter-owner', () => reloadCalendarEvents());

  on('export-scheduler', () => {
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
  });

  // Filters & repopulates the item TomSelect dropdown with options that match the selected category
  function filterOptionsByCategory(
    selectEl: HTMLSelectElement & { tomselect?: TomSelect },
    category: string,
  ): void {
    rebuildTomSelectOptions(selectEl, {
      filter: (option) => !category || option.dataset.category === category,
    });
  }

  async function handleEventDateChange(info): Promise<void> {
    try {
      if (!info.event.start || !info.event.end) {
        info.revert();
        return;
      }
      const startIso = DateTime.fromJSDate(info.event.start, { zone: 'system' }).toISO({ suppressMilliseconds: true });
      const endIso = DateTime.fromJSDate(info.event.end, { zone: 'system' }).toISO({ suppressMilliseconds: true });
      // Drag and resize intentionally omit scope so only the moved occurrence is updated
      await ApiC.patch(`event/${info.event.id}`, {target: 'datetime', start: startIso, end: endIso});
    } catch (err) {
      console.error(err);
      info.revert();
    }
  }

  function initTomSelect(): void {
    const itemSelect = document.getElementById('itemSelect') as HTMLSelectElement;

    const urlParams = new URLSearchParams(window.location.search);
    const selectedItems = urlParams.getAll('items[]');

    const itemTs = new TomSelect(itemSelect, {
      ...sharedTomSelectOptions,
      controlInput: '#itemSelectInput',
      dropdownParent: '#itemSelectWrapper',
      onChange: (selectedItems: string[]) => {
        lockScopeButtons(selectedItems);
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

    if (categorySelect.value) {
      filterOptionsByCategory(itemSelect, categorySelect.value);
    }

    if (selectedItems.length > 0) {
      itemTs.setValue(selectedItems);
      lockScopeButtons(selectedItems);
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
