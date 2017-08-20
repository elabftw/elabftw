$(document).ready(function() {
    // SCHEDULER
	$('#scheduler').fullCalendar({
			header: {
				left: 'prev,next today',
				center: 'title',
				right: 'agendaWeek'
			},
            defaultView: 'agendaWeek',
            // allow selection of range
			selectable: true,
            // draw an event while selecting
			selectHelper: true,
            editable: true,
            // i18n
            lang: $('#infos').data('lang'),
            // allow "more" link when too many events
			eventLimit: true,
            // load the events as JSON
            eventSources: [
                {
                    url: 'app/controllers/SchedulerController.php',
                    type: 'POST',
                    data: {
                        read: true,
                        item: $('#infos').data('item')
                    },
                    error: function() {
                        notif('There was an error while fetching events!', 'ko');
                    }
                }
            ],
            // first day is monday
            firstDay: 1,
            // remove possibility to book whole day, might add it later
            allDaySlot: false,
            // day start at 6 am
            minTime: "06:00:00",
            eventBackgroundColor: "rgb(41,174,185)",
            dayClick: function(start) {
                schedulerCreate(start.format(), end.format());
            },
            // selection
            select: function(start, end) {
                schedulerCreate(start.format(), end.format());
            },
            // delete by clicking it
            eventClick: function(calEvent) {
                if (confirm('Delete this event?')) {
                    $.post('app/controllers/SchedulerController.php', {
                        destroy: true,
                        id: calEvent.id
                    }).done(function(data) {
                        if (data.res) {
                            $('#scheduler').fullCalendar('removeEvents', calEvent.id);
                            notif(data.msg, 'ok');
                        } else {
                            notif(data.msg, 'ko');
                        }
                    });
                }
            },
            // a drop means we change start date
            eventDrop: function(calEvent) {
                $.post('app/controllers/SchedulerController.php', {
                    updateStart: true,
                    start: calEvent.start.format(),
                    end: calEvent.end.format(),
                    id: calEvent.id
                }).done(function() {
                    notif('Saved', 'ok');
                });
            },
            // a resize means we change end date
            eventResize: function(calEvent) {
                $.post('app/controllers/SchedulerController.php', {
                    updateEnd: true,
                    end: calEvent.end.format(),
                    id: calEvent.id
                }).done(function() {
                    notif('Saved', 'ok');
                });
            }

		});
});

function schedulerCreate(start, end) {
    var title = prompt('Comment:');
    if (title) {
        // add it to SQL
        $.post('app/controllers/SchedulerController.php', {
            create: true,
            start: start,
            end: end,
            title: title,
            item: $('#infos').data('item')
        }).done(function(data) {
            if (data.res) {
                notif(data.msg, 'ok');
                window.location.replace('team.php?tab=1&item=' + $('#infos').data('item'));
            } else {
                notif(data.msg, 'ko');
            }
        });
    }
}
