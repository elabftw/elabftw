/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */
import { DateTime } from 'luxon';
import i18next from 'i18next';
import { Malle, InputType, SelectOptions } from '@deltablot/malle';
import 'jquery-ui/ui/widgets/autocomplete';
import $ from 'jquery';
import 'bootstrap/js/src/modal.js';
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
import bootstrapPlugin from '@fullcalendar/bootstrap';
import interactionPlugin from '@fullcalendar/interaction';
import timeGridPlugin from '@fullcalendar/timegrid';
import listPlugin from '@fullcalendar/list';
import dayGridPlugin from '@fullcalendar/daygrid';
import { Action, ProcurementState } from './interfaces';
import { Api } from './Apiv2.class';
import { TomSelect } from './misc';
import Tab from './Tab.class';

document.addEventListener('DOMContentLoaded', () => {
  if (window.location.pathname !== '/team.php') {
    return;
  }

  const TabMenu = new Tab();
  TabMenu.init(document.querySelector('.tabbed-menu'));

  const ApiC = new Api();

  // start and end inputs
  const startInput = (document.getElementById('schedulerEventModalStart') as HTMLInputElement);
  const endInput = (document.getElementById('schedulerEventModalEnd') as HTMLInputElement);

  // transform a Date object into something we can put as a value of an input of type datetime-local
  function toDateTimeInputValueNumber(datetime: Date): number {
    const offset = datetime.getTimezoneOffset() * 60 * 1000;
    return datetime.valueOf() - offset;
  }
  const params = new URLSearchParams(document.location.search.substring(1));

  // if we show all items, they are not editable
  let editable = true;
  let selectable = true;
  if (!params.has('item')) {
    editable = false;
    selectable = false;
    if (document.getElementById('selectBookableWarningDiv')) {
      document.getElementById('selectBookableWarningDiv').removeAttribute('hidden');
    }
  }
  // get the start parameter from url and use that as start time if it's there
  const start = params.get('start');
  let selectedDate = new Date().valueOf();
  if (start !== null) {
    selectedDate = new Date(decodeURIComponent(start)).valueOf();
  }

  // bind to the element #scheduler
  const calendarEl: HTMLElement = document.getElementById('scheduler');

  let selectedItem = '';
  if (params.has('item') && params.get('item') !== 'all') {
    selectedItem = params.get('item');
  }
  // allow filtering the category of items in events
  let queryString = '';
  if (params.get('cat')) {
    queryString += '?cat=' + params.get('cat');
  }

  let eventBackgroundColor = 'a9a9a9';
  if (document.getElementById('itemSelect')) {
    eventBackgroundColor = (document.getElementById('itemSelect') as HTMLSelectElement).selectedOptions[0].dataset.color;
  }

  // SCHEDULER
  const calendar = new Calendar(calendarEl, {
    plugins: [ dayGridPlugin, timeGridPlugin, interactionPlugin, listPlugin, bootstrapPlugin ],
    headerToolbar: {
      left: 'prev,next today',
      center: 'title',
      right: 'timeGridWeek,listWeek,dayGridMonth',
    },
    themeSystem: 'bootstrap',
    // i18n
    // all available locales
    locales: [ caLocale, csLocale, deLocale, enLocale, esLocale, frLocale, itLocale, idLocale, jaLocale, koLocale, nlLocale, plLocale, ptLocale, ptbrLocale, ruLocale, skLocale, slLocale, zhcnLocale ],
    // selected locale
    locale: calendarEl.dataset.lang,
    initialView: 'timeGridWeek',
    // allow selection of range
    selectable: selectable,
    // draw an event while selecting
    selectMirror: true,
    // if no item is selected, the calendar is not editable
    editable: editable,
    // allow "more" link when too many events
    dayMaxEventRows: true,
    // set the date loaded
    initialDate: selectedDate,
    // display a line for the time of now
    nowIndicator: true,
    // load the events as JSON
    eventSources: [
      {
        url: `api/v2/events/${selectedItem}${queryString}`,
      },
    ],
    // first day is monday
    firstDay: 1,
    // remove possibility to book whole day, might add it later
    allDaySlot: false,
    // adjust the background color of event to the color of the item type
    eventBackgroundColor: '#' + eventBackgroundColor,
    // selection
    select: function(info): void {
      // get the item id from url
      const params = new URLSearchParams(document.location.search.slice(1));
      const itemid = parseInt(params.get('item'), 10);
      if (!Number.isSafeInteger(itemid)) {
        calendar.unselect();
        return;
      }

      const postParams = {
        start: info.startStr,
        end: info.endStr,
      };
      ApiC.post(`events/${itemid}`, postParams).then(()=> {
        // note: here the event is shown empty (without title), until the user clicks somewhere. Still better than a full page reload.
        // FIXME: this should be addressed eventually
        calendar.refetchEvents();
      }).catch(() => {
        calendar.unselect();
        return;
      });
    },
    // on click activate modal window
    eventClick: function(info): void {
      if (!editable) {
        // load page with selected item + correct start depending on current view
        window.location.replace(`team.php?tab=1&item=${info.event.extendedProps.items_id}&start=${calendar.view.activeStart.toISOString()}`);
        return;
      }

      $('[data-action="scheduler-rm-bind"]').hide();
      $('#eventModal').modal('toggle');
      // set the event id on the cancel button
      document.querySelectorAll('.cancelEventBtn').forEach((btn: HTMLButtonElement) => { btn.dataset.id = info.event.id; });
      // FILL THE BOUND DIV

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
      document.querySelectorAll('[data-action="scheduler-rm-bind"]').forEach((btn:HTMLButtonElement) => btn.dataset.eventid = info.event.id);

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

      refreshBoundDivs(info.event.extendedProps);

      // BIND AN ENTITY TO THE EVENT
      $('[data-action="scheduler-bind-entity"]').on('click', function(): void {
        const inputEl = $(this).parent().parent().find('input');
        const entityid = parseInt((inputEl.val() as string), 10);
        if (entityid > 0) {
          ApiC.patch(`event/${info.event.id}`, {'target': $(this).data('type'), 'id': entityid}).then(res => res.json()).then(json => {
            calendar.refetchEvents();
            refreshBoundDivs(json);
            inputEl.val('');
          });
        }
      });
    },
    // on mouse enter add shadow and show title
    eventMouseEnter: function(info): void {
      if (editable) {
        info.el.style.boxShadow = '5px 4px 4px #474747';
      }
      info.el.title = info.event.title;
    },
    // remove the box shadow when mouse leaves
    eventMouseLeave: function(info): void {
      info.el.style.boxShadow = 'unset';
    },
    // a drop means we change start date
    eventDrop: function(info): void {
      if (!editable) { return; }
      ApiC.patch(`event/${info.event.id}`, {'target': 'start', 'delta': info.delta}).catch(() => info.revert());
    },
    // a resize means we change end date
    eventResize: function(info): void {
      if (!editable) { return; }
      ApiC.patch(`event/${info.event.id}`, {'target': 'end', 'delta': info.endDelta}).catch(() => info.revert());
    },
  });

  // only try to render if we actually have some bookable items
  if (calendarEl.dataset.render === 'true') {
    calendar.render();
    calendar.updateSize();
  }

  // transform the enum into the kind of object we want
  const procurementStateArr: SelectOptions[] = Object.keys(ProcurementState)
    .filter(key => !isNaN(Number(key)))
    .map(key => ({
      selected: false,
      text: ProcurementState[key],
      value: key,
    }));

  new Malle({
    cancel : i18next.t('cancel'),
    cancelClasses: ['btn', 'btn-danger', 'mt-2', 'ml-1'],
    inputClasses: ['form-control'],
    fun: (value: string, original: HTMLElement) => {
      return ApiC.patch(`teams/current/procurement_requests/${original.dataset.id}`, {state: value}).then(res => res.json()).then(json => json.state);
    },
    inputType: InputType.Select,
    selectOptions: procurementStateArr,
    listenOn: '.malleableState',
    returnedValueIsTrustedHtml: false,
    submit : i18next.t('save'),
    submitClasses: ['btn', 'btn-primary', 'mt-2'],
    tooltip: i18next.t('click-to-edit'),
  }).listen();



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
    // RECEIVE PROCUREMENT REQUEST
    if (el.matches('[data-action="receive-procurement-request"]')) {
      ApiC.patch(`teams/current/procurement_requests/${el.dataset.id}`);

    // CANCEL PROCUREMENT REQUEST
    } else if (el.matches('[data-action="cancel-procurement-request"]')) {
      if (confirm(i18next.t('generic-delete-warning'))) {
        ApiC.delete(`teams/current/procurement_requests/${el.dataset.id}`).then(() => el.parentElement.parentElement.remove());
      }

    // CANCEL EVENT ACTION
    } else if (el.matches('[data-action="cancel-event"]')) {
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
    // REMOVE BIND
    } else if (el.matches('[data-action="scheduler-rm-bind"]')) {
      const bindType = el.dataset.type;
      ApiC.patch(`event/${el.dataset.eventid}`, {'target': bindType, 'id': null}).then(() => {
        clearBoundDiv(bindType);
        // clear the inputs
        document.querySelectorAll('.bindInput').forEach((input:HTMLInputElement) => input.value = '');
        calendar.refetchEvents();
      });
    }
  });

  ['schedulerSelectCat', 'itemSelect'].forEach(id => {
    new TomSelect(`#${id}`, {
      plugins: [
        'dropdown_input',
        'remove_button',
      ],
    });
  });
});
