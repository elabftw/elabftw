/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */
import EntityClass from './Entity.class';
import { EntityType } from './interfaces';
import { Api } from './Apiv2.class';
import { notif } from './misc';
import { DateTime } from 'luxon';
import i18next from 'i18next';
import 'jquery-ui/ui/widgets/autocomplete';
import 'bootstrap/js/src/modal.js';
import { Calendar } from '@fullcalendar/core';
import bootstrapPlugin from '@fullcalendar/bootstrap';
import caLocale from '@fullcalendar/core/locales/ca';
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
import interactionPlugin from '@fullcalendar/interaction';
import timeGridPlugin from '@fullcalendar/timegrid';
import listPlugin from '@fullcalendar/list';
import dayGridPlugin from '@fullcalendar/daygrid';
import Tab from './Tab.class';

document.addEventListener('DOMContentLoaded', () => {
  if (window.location.pathname !== '/team.php') {
    return;
  }

  const TabMenu = new Tab();
  TabMenu.init(document.querySelector('.tabbed-menu'));

  const info = document.getElementById('info').dataset;

  const ApiC = new Api();

  // transform a Date object into something we can put as a value of an input of type datetime-local
  function toDateTimeInputValueNumber(datetime: Date): number {
    const offset = datetime.getTimezoneOffset() * 60 * 1000;
    return datetime.valueOf() - offset;
  }

  // if we show all items, they are not editable
  let editable = true;
  let selectable = true;
  if (info.all) {
    editable = false;
    selectable = false;
  }
  // get the start parameter from url and use that as start time if it's there
  const params = new URLSearchParams(document.location.search.substring(1));
  const start = params.get('start');
  let selectedDate = new Date().valueOf();
  if (start !== null) {
    selectedDate = new Date(decodeURIComponent(start)).valueOf();
  }

  // bind to the element #scheduler
  const calendarEl: HTMLElement = document.getElementById('scheduler');

  // allow filtering the category of items in events
  let queryString = '?';
  if (params.get('cat')) {
    queryString += 'cat=' + params.get('cat');
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
    locales: [ caLocale, deLocale, enLocale, esLocale, frLocale, itLocale, idLocale, jaLocale, koLocale, nlLocale, plLocale, ptLocale, ptbrLocale, ruLocale, skLocale, slLocale, zhcnLocale ],
    // selected locale
    locale: info.calendarlang,
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
        url: `api/v2/events/${info.item}${queryString}`,
      },
    ],
    // first day is monday
    firstDay: 1,
    // remove possibility to book whole day, might add it later
    allDaySlot: false,
    // adjust the background color of event to the color of the item type
    eventBackgroundColor: $('#dropdownMenu1 > span:nth-child(1)').css('color'),
    // selection
    select: function(info): void {
      if (!editable) { return; }
      const title = prompt(i18next.t('comment-add'));
      if (!title) {
        // make the selected area disappear
        calendar.unselect();
        return;
      }
      // get the item id from url
      const params = new URLSearchParams(document.location.search.slice(1));
      const itemid = parseInt(params.get('item'), 10);
      if (!Number.isSafeInteger(itemid)) {
        calendar.unselect();
        return;
      }

      const postParams = {
        'start': info.startStr,
        'end': info.endStr,
        'title': title,
      };
      ApiC.post(`events/${itemid}`, postParams).then(() => {
        // FIXME: it would be best to just properly render the event instead of reloading the whole page
        window.location.replace(`team.php?tab=1&item=${itemid}&start=${encodeURIComponent(info.startStr)}`);
      });
    },
    // on click activate modal window
    eventClick: function(info): void {
      if (!editable) { return; }
      $('[data-action="scheduler-rm-bind"]').hide();
      $('#eventModal').modal('toggle');
      // delete button in modal
      $('#deleteEvent').on('click', function(): void {
        ApiC.delete(`event/${info.event.id}`).then(() => {
          info.event.remove();
          $('#eventModal').modal('toggle');
        });
      });
      // FILL THE BOUND DIV

      // title
      document.getElementById('eventTitle').innerText = info.event.title;

      // start and end inputs
      const startInput = (document.getElementById('schedulerEventModalStart') as HTMLInputElement);
      startInput.valueAsNumber = toDateTimeInputValueNumber(info.event.start);
      const endInput = (document.getElementById('schedulerEventModalEnd') as HTMLInputElement);
      endInput.valueAsNumber = toDateTimeInputValueNumber(info.event.end);
      // add on change event listener on datetime inputs
      [startInput, endInput].forEach(input => {
        input.addEventListener('change', event => {
          const input = (event.currentTarget as HTMLInputElement);
          const dt = DateTime.fromJSDate(input.valueAsDate);
          ApiC.patch(`event/${info.event.id}`, {'target': input.dataset.what, 'epoch': String(dt.toUnixInteger())}).then(() => {
            calendar.refetchEvents();
          });
        });
      });

      if (info.event.extendedProps.experiment != null) {
        $('#eventBoundExp').html(`Event is bound to an experiment: <a href="experiments.php?mode=view&id=${info.event.extendedProps.experiment}">${info.event.extendedProps.experiment_title}</a>.`);
        $('[data-action="scheduler-rm-bind"][data-type="experiment"]').show();
      }
      if (info.event.extendedProps.item_link != null) {
        $('#eventBoundDb').html(`Event is bound to an item: <a href="database.php?mode=view&id=${info.event.extendedProps.item_link}">${info.event.extendedProps.item_link_title}</a>.`);
        $('[data-action="scheduler-rm-bind"][data-type="item_link"]').show();
      }
      // BIND AN ENTITY TO THE EVENT
      $('[data-action="scheduler-bind-entity"]').on('click', function(): void {
        const entityid = parseInt(($('#' + $(this).data('input')).val() as string), 10);
        if (entityid > 0) {
          ApiC.patch(`event/${info.event.id}`, {'target': $(this).data('type'), 'id': entityid}).then(() => {
            $('#bindinput').val('');
            $('#eventModal').modal('toggle');
            window.location.replace('team.php?tab=1&item=' + $('#info').data('item') + '&start=' + encodeURIComponent(info.event.start.toString()));
          });
        }
      });
      // remove the binding
      $('[data-action="scheduler-rm-bind"]').on('click', function(): void {
        ApiC.patch(`event/${info.event.id}`, {'target': $(this).data('type'), 'id': null}).then(() => {
          $('#eventModal').modal('toggle');
          window.location.replace('team.php?tab=1&item=' + $('#info').data('item') + '&start=' + encodeURIComponent(info.event.start.toString()));
        });
      });
      // BIND AUTOCOMPLETE
      // TODO refactor this
      // NOTE: previously the input div had ui-front jquery ui class to make the autocomplete list show properly, but with the new item input below
      // it didn't work well, so now the automplete uses appendTo option
      $('#bindexpinput').autocomplete({
        appendTo: '#binddivexp',
        source: function(request: Record<string, string>, response: (data) => void): void {
          ApiC.getJson(`${EntityType.Experiment}/&q=${request.term}`).then(json => {
            const res = [];
            json.forEach(entity => {
              res.push(`${entity.id} - [${entity.category}] ${entity.title.substring(0, 60)}`);
            });
            response(res);
          });
        },
      });
      $('#binddbinput').autocomplete({
        appendTo: '#binddivdb',
        source: function(request: Record<string, string>, response: (data) => void): void {
          ApiC.getJson(`${EntityType.Item}/&q=${request.term}`).then(json => {
            const res = [];
            json.forEach(entity => {
              res.push(`${entity.id} - [${entity.category}] ${entity.title.substring(0, 60)}`);
            });
            response(res);
          });
        },
      });

    },
    // on mouse enter add shadow and show title
    eventMouseEnter: function(info): void {
      if (editable) {
        $(info.el).css('box-shadow', '5px 4px 4px #474747');
      }
      $(info.el).attr('title', info.event.title);
    },
    // remove the box shadow when mouse leaves
    eventMouseLeave: function(info): void {
      $(info.el).css('box-shadow', 'unset');
    },
    // a drop means we change start date
    eventDrop: function(info): void {
      if (!editable) { return; }
      // TODO catch error and use info.revert();
      ApiC.patch(`event/${info.event.id}`, {'target': 'start', 'delta': info.delta});
    },
    // a resize means we change end date
    eventResize: function(info): void {
      if (!editable) { return; }
      // TODO catch error and use info.revert();
      ApiC.patch(`event/${info.event.id}`, {'target': 'end', 'delta': info.endDelta});
    },
  });

  // only try to render if we actually have some bookable items
  if (calendarEl.dataset.render === 'true') {
    calendar.render();
    calendar.updateSize();
  }

  // Add click listener and do action based on which element is clicked
  document.querySelector('.real-container').addEventListener('click', (event) => {
    const el = (event.target as HTMLElement);
    const TemplateC = new EntityClass(EntityType.Template);
    // IMPORT TPL
    if (el.matches('[data-action="import-template"]')) {
      TemplateC.duplicate(parseInt(el.dataset.id));

    // DESTROY TEMPLATE
    } else if (el.matches('[data-action="destroy-template"]')) {
      if (confirm(i18next.t('generic-delete-warning'))) {
        TemplateC.destroy(parseInt(el.dataset.id))
          .then(() => window.location.replace('team.php?tab=3'))
          .catch((e) => notif({'res': false, 'msg': e.message}));
      }
    }
  });
});
