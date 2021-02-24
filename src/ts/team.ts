/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */
import Template from './Template.class';
import { notif } from './misc';
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
import { config } from '@fortawesome/fontawesome-svg-core';

document.addEventListener('DOMContentLoaded', function() {
  if (window.location.pathname !== '/team.php') {
    return;
  }
  // use this setting to prevent bug in fullcalendar
  // see https://github.com/fullcalendar/fullcalendar/issues/5544
  config.autoReplaceSvg = 'nest';
  // this setting has a side-effect with the top right fa icon
  // so we set it at a correct size again
  $('.fa-user-circle').css('font-size', '130%');

  // if we show all items, they are not editable
  let editable = true;
  let selectable = true;
  if ($('#info').data('all')) {
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
    locale: $('#info').data('calendarlang'),
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
    // load the events as JSON
    eventSources: [
      {
        url: 'app/controllers/SchedulerController.php',
        extraParams: {
          item: $('#info').data('item'),
        },
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
      $.post('app/controllers/SchedulerController.php', {
        create: true,
        start: info.startStr,
        end: info.endStr,
        title: title,
        item: $('#info').data('item')
      }).done(function(json) {
        notif(json);
        if (json.res) {
          window.location.replace('team.php?tab=1&item=' + $('#info').data('item') + '&start=' + encodeURIComponent(info.startStr));
        }
      });
    },
    // on click activate modal window
    eventClick: function(info): void {
      if (!editable) { return; }
      $('#rmBind').hide();
      ($('#eventModal') as any).modal('toggle');
      // delete button in modal
      $('#deleteEvent').on('click', function(): void {
        $.post('app/controllers/SchedulerController.php', {
          destroy: true,
          id: info.event.id
        }).done(function(json) {
          notif(json);
          if (json.res) {
            info.event.remove();
            ($('#eventModal') as any).modal('toggle');
          }
        });
      });
      // fill the bound div
      $('#eventTitle').text(info.event.title);
      if (info.event.extendedProps.experiment != null) {
        $('#eventBound').html('Event is bound to an <a href="experiments.php?mode=view&id=' + info.event.extendedProps.experiment + '">experiment</a>.');
        $('#rmBind').show();
      }
      // bind an experiment to the event
      $('#goBind').on('click', function(): void {
        $.post('app/controllers/SchedulerController.php', {
          bind: true,
          id: info.event.id,
          expid: parseInt(($('#bindinput').val() as string), 10),
        }).done(function(json) {
          notif(json);
          if (json.res) {
            $('#bindinput').val('');
            ($('#eventModal') as any).modal('toggle');
            window.location.replace('team.php?tab=1&item=' + $('#info').data('item') + '&start=' + encodeURIComponent(info.event.start.toString()));
          }
        });
      });
      // remove the binding
      $('#rmBind').on('click', function(): void {
        $.post('app/controllers/SchedulerController.php', {
          unbind: true,
          id: info.event.id,
        }).done(function(json) {
          ($('#eventModal') as any).modal('toggle');
          notif(json);
          window.location.replace('team.php?tab=1&item=' + $('#info').data('item') + '&start=' + encodeURIComponent(info.event.start.toString()));
        });
      });
      // BIND AUTOCOMPLETE
      const cache = {};
      $('#bindinput').autocomplete({
        source: function(request: any, response: any): void {
          const term = request.term;
          if (term in cache) {
            response(cache[term]);
            return;
          }
          $.getJSON('app/controllers/EntityAjaxController.php?source=experiments', request, function(data) {
            cache[term] = data;
            response(data);
          });
        }
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
      $.post('app/controllers/SchedulerController.php', {
        updateStart: true,
        delta: info.delta,
        id: info.event.id,
      }).done(function(json) {
        notif(json);
      });
    },
    // a resize means we change end date
    eventResize: function(info): void {
      if (!editable) { return; }
      $.post('app/controllers/SchedulerController.php', {
        updateEnd: true,
        end: info.endDelta,
        id: info.event.id,
      }).done(function(json) {
        notif(json);
      });
    },
  });
  // only start it if the element is here
  // otherwise it will error out if the element is not here
  if (document.getElementById('scheduler')) {
    calendar.render();
    calendar.updateSize();
  }

  // IMPORT TPL
  $(document).on('click', '.importTpl', function() {
    new Template().duplicate($(this).data('id'));
  });
});
