<?php
/**
 * team.php
 *
 * @author Nicolas CARPi <nicolas.carpi@curie.fr>
 * @copyright 2012 Nicolas CARPi
 * @see http://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */
namespace Elabftw\Elabftw;

use Exception;

/**
 * The team page
 *
 */
require_once 'app/init.inc.php';
$page_title = _('Team');
$selected_menu = 'Team';
require_once 'app/head.inc.php';

$Users = new Users();
$TeamsView = new TeamsView(new Teams($_SESSION['team_id']));
$Database = new Database($_SESSION['team_id']);
$Scheduler = new Scheduler($_SESSION['team_id']);
?>

<script src='js/moment/moment.js'></script>
<script src='js/fullcalendar/dist/fullcalendar.js'></script>
<script src='js/fullcalendar/dist/locale-all.js'></script>

<menu>
    <ul>
        <li class='tabhandle' id='tab1'><?= _('Scheduler') ?></li><span class='beta'>beta</span>
        <li class='tabhandle' id='tab2'><?= _('Infos') ?></li>
    </ul>
</menu>

<!-- TAB 1 SCHEDULER -->
<div class='divhandle' id='tab1div'>
<?php
// we only want the bookable type of items
$Database->bookableFilter = "AND bookable = 1";
$items = $Database->readAll();
$dropdown = '';
if (count($items) === 0) {
    display_message('warning_nocross', _("No bookable items."));
} else {
    $dropdown = "<div class='row'>";
    $dropdown .= "<div class='col-md-2'>";
    $dropdown .= "<div class='dropdown'>";
    $dropdown .= "<button class='btn btn-default dropdown-toggle' type='button' id='dropdownMenu1' data-toggle='dropdown' aria-haspopup='true' aria-expanded='true'>";
    $dropdown .= _('Select an equipment');
    $dropdown .= " <span class='caret'></span>";
    $dropdown .= "</button>";
    $dropdown .= "<ul class='dropdown-menu' aria-labelledby='dropdownMenu1'>";
    foreach ($items as $item) {
        $dropdown .= "<li class='dropdown-item'><a data-value='" . $item['title'] . "' href='team.php?item=" . $item['itemid'] . "'><span style='color:#" . $item['bgcolor'] . "'>"
            . $item['name'] . "</span> - " . $item['title'] . "</a></li>";
    }
    $dropdown .= "</ul>";
    $dropdown .= "</div></div></div>";
}
try {
    if (isset($_GET['item']) && Tools::checkId($_GET['item'])) {
        $Scheduler->setId($_GET['item']);
        $itemName = '';
        foreach ($items as $item) {
            if ($item['itemid'] == $_GET['item']) {
                $itemName = $item['name'] . ' - ' . $item['title'];
                $itemId = $item['itemid'];
            }
        }
        if (strlen($itemName) === 0) {
            throw new Exception(_('Nothing to show with this id'));
        }
        echo "<a href='#' onClick=\"insertParamAndReload('item', '')\">" . _('Change item') . "</a>";
        echo "<h4>" . $itemName . "</h4>";
        echo "<div id='scheduler'></div>";
    } else {
        echo $dropdown;
    }
} catch (Exception $e) {
    echo display_message('ko_nocross', $e->getMessage());
}
?>
</div>

<!-- TAB 2 INFOS -->
<div class='divhandle' id='tab2div'>
<?php
display_message('ok_nocross', sprintf(
    _('You belong to the %s team. %s'),
    $TeamsView->Teams->read('team_name'),
    $TeamsView->showStats($_SESSION['team_id'])
))
?>

<table id='teamtable' class='table'>
    <tr>
        <th><?= _('Name') ?></th>
        <th><?= _('Phone') ?></th>
        <th><?= _('Mobile') ?></th>
        <th><?= _('Website') ?></th>
        <th><?= _('Skype') ?></th>
    </tr>
<?php
foreach ($Users->readAllFromTeam($_SESSION['team_id']) as $user) {
    echo "<tr>";
    echo "<td><a href='mailto:" . $user['email'] . "'><span";
    // put sysadmin, admin and chiefs in bold
    if ($user['usergroup'] === '3' || $user['usergroup'] === '1' || $user['usergroup'] === '2') {
        echo " style='font-weight:bold'";
    }
    echo ">" . $user['firstname'] . " " . $user['lastname'] . "</span></a>";
    echo "</td>";
    if (!empty($user['phone'])) {
        echo "<td>" . $user['phone'] . "</td>";
    } else {
        echo "<td>&nbsp;</td>"; // Placeholder
    }
    if (!empty($user['cellphone'])) {
        echo "<td>" . $user['cellphone'] . "</td>";
    } else {
        echo "<td>&nbsp;</td>";
    }
    if (!empty($user['website'])) {
        echo "<td><a href='" . $user['website'] . "'>" . $user['website'] . "</a></td>";
    } else {
        echo "<td>&nbsp;</td>";
    }
    if (!empty($user['skype'])) {
        echo "<td>" . $user['skype'] . "</td>";
    } else {
        echo "<td>&nbsp;</td>";
    }
}
?>
</table>

</div>

<script>
$(document).ready(function() {
    // TABS
    // get the tab=X parameter in the url
    var params = getGetParameters();
    var tab = parseInt(params['tab']);
    if (!isInt(tab)) {
        var tab = 1;
    }
    var initdiv = '#tab' + tab + 'div';
    var inittab = '#tab' + tab;
    // init
    $(".divhandle").hide();
    $(initdiv).show();
    $(inittab).addClass('selected');

    $(".tabhandle" ).click(function(event) {
        var tabhandle = '#' + event.target.id;
        var divhandle = '#' + event.target.id + 'div';
        $(".divhandle").hide();
        $(divhandle).show();
        $(".tabhandle").removeClass('selected');
        $(tabhandle).addClass('selected');
    });
    // END TABS

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
            lang: '<?= Tools::getCalendarLang($_SESSION['prefs']['lang']) ?>',
            // allow "more" link when too many events
			eventLimit: true,
            // load the events as JSON
            events: <?= $Scheduler->read() ?>,
            // first day is monday
            firstDay: 1,
            // remove possibility to book whole day, might add it later
            allDaySlot: false,
            // day start at 6 am
            minTime: "06:00:00",
            eventBackgroundColor: "rgb(41,174,185)",
            dayClick: function(start) {
                schedulerCreate(start.format());
            },
            // selection
            select: function(start, end) {
                schedulerCreate(start.format(), end.format());
            },
            // delete by clicking it
            eventClick: function(calEvent) {
                if (confirm('Delete this event?')) {
                    $('#scheduler').fullCalendar('removeEvents', calEvent.id);
                    $.post('app/controllers/SchedulerController.php', {
                        destroy: true,
                        id: calEvent.id
                    }).done(function() {
                        notif('Deleted', 'ok');
                    });
                }
            },
            // a drop means we change start date
            eventDrop: function(calEvent) {
                $.post('app/controllers/SchedulerController.php', {
                    updateStart: true,
                    start: calEvent.start.format(),
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

function schedulerCreate(start, end = null) {
    var title = prompt('Comment:');
    if (title) {
        // add it to SQL
        $.post('app/controllers/SchedulerController.php', {
            create: true,
            item: <?= isset($itemId) ? $itemId : 1 ?>,
            start: start,
            end: end,
            title: title
        }).done(function() {
            window.location.replace('team.php?tab=1&item=<?= isset($_GET['item']) ? $_GET['item'] : '' ?>');
        });
    }
}
</script>

<?php require_once('app/footer.inc.php');
