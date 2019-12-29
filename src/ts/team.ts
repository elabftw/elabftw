/**
 * @author Nicolas CARPi <nicolas.carpi@curie.fr>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */
import { notif } from './misc';
import 'jquery-ui/ui/widgets/autocomplete';
import 'bootstrap/js/src/modal.js';
import { Calendar } from '@fullcalendar/core';
import bootstrapPlugin from '@fullcalendar/bootstrap';
import '@fullcalendar/core/locales/ca';
import '@fullcalendar/core/locales/de';
import '@fullcalendar/core/locales/en-gb';
import '@fullcalendar/core/locales/es';
import '@fullcalendar/core/locales/fr';
import '@fullcalendar/core/locales/it';
import '@fullcalendar/core/locales/id';
import '@fullcalendar/core/locales/ja';
//import '@fullcalendar/core/locales/kr'
import '@fullcalendar/core/locales/nl';
import '@fullcalendar/core/locales/pl';
import '@fullcalendar/core/locales/pt';
import '@fullcalendar/core/locales/pt-br';
import '@fullcalendar/core/locales/ru';
import '@fullcalendar/core/locales/sk';
import '@fullcalendar/core/locales/sl';
import '@fullcalendar/core/locales/zh-cn';
import interactionPlugin from '@fullcalendar/interaction';
import timeGridPlugin from '@fullcalendar/timegrid';
import listPlugin from '@fullcalendar/list';

function schedulerCreate(start: string, end: string): void {
  const title = prompt('Comment:');
  if (title) {
    // add it to SQL
    $.post('app/controllers/SchedulerController.php', {
      create: true,
      start: start,
      end: end,
      title: title,
      item: $('#info').data('item')
    }).done(function(json) {
      notif(json);
      if (json.res) {
        window.location.replace('team.php?tab=1&item=' + $('#info').data('item'));
      }
    });
  }
}
document.addEventListener('DOMContentLoaded', function() {
  let editable = true;
  let selectable = true;
  if ($('#info').data('all')) {
    editable = false;
    selectable = false;
  }
  const calendarEl: HTMLElement = document.getElementById('scheduler');

  // SCHEDULER
  const calendar = new Calendar(calendarEl, {
    plugins: [ timeGridPlugin, interactionPlugin, listPlugin, bootstrapPlugin ],
    header: {
      left: 'prev,next today',
      center: 'title',
      right: 'timeGridWeek, listWeek',
    },
    themeSystem: 'bootstrap',
    // i18n
    locale: $('#info').data('lang'),
    defaultView: 'timeGridWeek',
    // allow selection of range
    selectable: selectable,
    // draw an event while selecting
    selectMirror: true,
    editable: editable,
    // allow "more" link when too many events
    eventLimit: true,
    // load the events as JSON
    events: 'app/controllers/SchedulerController.php?item=' + $('#info').data('item'),
    // first day is monday
    firstDay: 1,
    // remove possibility to book whole day, might add it later
    allDaySlot: false,
    // day start at 6 am
    minTime: '06:00:00',
    eventBackgroundColor: 'rgb(41,174,185)',
    // selection
    select: function(info): void {
      if (!editable) { return; }
      schedulerCreate(info.startStr, info.endStr);
    },
    // on click activate modal window
    eventClick: function(info): void {
      console.log(info.event);
      if (!editable) { return; }
      $('#rmBind').hide();
      $('#eventModal').modal('toggle');
      // delete button in modal
      $('#deleteEvent').on('click', function(): void {
        $.post('app/controllers/SchedulerController.php', {
          destroy: true,
          id: info.event.id
        }).done(function(json) {
          notif(json);
          if (json.res) {
            info.event.remove();
            $('#eventModal').modal('toggle');
          }
        });
      });
      // fill the bound div
      $('#eventTitle').text(info.event.title);
      if (info.event.extendedProps.experiment != null) {
        $('#eventBound').text('Event is bound to an experiment.');
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
            $('#eventModal').modal('toggle');
            window.location.replace('team.php?tab=1&item=' + $('#info').data('item'));
          }
        });
      });
      // remove the binding
      $('#rmBind').on('click', function(): void {
        $.post('app/controllers/SchedulerController.php', {
          unbind: true,
          id: info.event.id,
        }).done(function(json) {
          $('#eventModal').modal('toggle');
          notif(json);
          window.location.replace('team.php?tab=1&item=' + $('#info').data('item'));
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
  calendar.render();

});

// IMPORT TPL
$(document).on('click', '.importTpl', function() {
  $.post('app/controllers/AjaxController.php', {
    importTpl: true,
    id: $(this).data('id')
  }).done(function(json) {
    notif(json);
  });
});
