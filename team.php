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

/**
 * The team page
 *
 */
require_once 'inc/common.php';
$page_title = _('Team');
$selected_menu = 'Team';
require_once 'inc/head.php';

$Users = new Users();
$TeamsView = new TeamsView();
$Database = new Database($_SESSION['team_id']);
$Scheduler = new Scheduler($_SESSION['team_id']);
?>

<script src='js/moment/moment.js'></script>
<script src='js/fullcalendar/dist/fullcalendar.js'></script>
<script src='js/fullcalendar/dist/lang-all.js'></script>
<link rel='stylesheet' media='all' href='js/fullcalendar/dist/fullcalendar.css'>

<menu>
<ul>
<li class='tabhandle' id='tab1'><?= _('Scheduler') ?></li>
<li class='tabhandle' id='tab2'><?= _('Infos') ?></li>
<li class='tabhandle' id='tab3'><?= _('Tools') ?></li>
<li class='tabhandle' id='tab4'><?= _('Help') ?></li>
</ul>
</menu>

<!-- TAB 1 SCHEDULER -->
<div class='divhandle' id='tab1div'>
<?php
// we only want the bookable type of items
$Database->bookableFilter = "AND bookable = 1";
$items = $Database->readAll();
if (count($items) === 0) {
    display_message('warning_nocross', _("No bookable items."));
} else {
    ?>
    <select id='scheduler-select' onChange="insertParamAndReload('item', this.value)">
    <option selected disabled><?= _("Select an equipment") ?></option>
    <?php
    foreach ($items as $item) {
        echo "<option ";
        if (isset($_GET['item']) && ($_GET['item'] == $item['id'])) {
            echo "selected ";
        }
        echo "value='" . $item['itemid'] . "'>[" . $item['name'] . "] " . $item['title'] . "</option>";
    }
    ?>
    </select>
    <?php
}
if (isset($_GET['item'])) {
    $Scheduler->setId($_GET['item']);
    echo "<div id='scheduler'></div>";
}
?>
</div>

<!-- TAB 2 INFOS -->
<div class='divhandle' id='tab2div'>
<?php
display_message('ok_nocross', sprintf(
    _('You belong to the %s team. %s'),
    get_team_config('team_name'),
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
foreach ($Users->readAll() as $user) {
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

<!-- TAB 3 TOOLS -->
<div class='divhandle chemdoodle' id='tab3div'>
    <h3><?php echo _('Molecule drawer'); ?></h3>
    <div class='box'>
        <link rel="stylesheet" href="css/chemdoodle.css" type="text/css">
        <script src="js/chemdoodle.js"></script>
        <script src="js/chemdoodle-uis.js"></script>
        <div class='center'>
            <script>
                var sketcher = new ChemDoodle.SketcherCanvas('sketcher', 550, 300, {oneMolecule:true});
            </script>
        </div>
    </div>
</div>

<!-- TAB 4 HELP -->
<div class='divhandle' id='tab4div'>
    <p>
        <ul>
        <li class='tip'><?= sprintf(_('There is a manual available %shere%s.'), "<a href='doc/_build/html/manual.html'>", "</a>") ?></li>
        <li class='tip'><?= _("You can use a TODOlist by pressing 't'.") ?></li>
        <li class='tip'><?= sprintf(_('You can have experiments templates (%sControl Panel%s).'), "<a href='ucp.php?tab=3'>", "</a>") ?></li>
        <li class='tip'><?= sprintf(_('The admin of a team can edit the status and the types of items available (%sAdmin Panel%s).'), "<a href='admin.php?tab=4'>", "</a>") ?></li>
        <li class='tip'><?= _('If you press Ctrl Shift D in the editor, the date will appear under the cursor.') ?></li>
        <li class='tip'><?= sprintf(_('Custom shortcuts are available (%sControl Panel%s).'), "<a href='ucp.php?tab=1'>", "</a>") ?></li>
        <li class='tip'><?= _('You can duplicate experiments in one click.') ?></li>
        <li class='tip'><?= _('Click a tag to list all items with this tag.') ?></li>
        <li class='tip'><?= _('Only a locked experiment can be timestamped.') ?></li>
        <li class='tip'><?= _('Once timestamped an experiment cannot be unlocked or modified. Only comments can be added.') ?></li>
        </ul>
    </p>
</div>
<!-- *********************** -->

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
                // because all the rerender methods fail, reload the page
                // this is because upon creation the event has not all the correct attributes
                // and trying to manipulate it fails
                window.location.replace('team.php?tab=1&item=<?= $_GET['item'] ?>');
            },
            // selection
            select: function(start, end) {
                schedulerCreate(start.format(), end.format());
                window.location.replace('team.php?tab=1&item=<?= $_GET['item'] ?>');
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
            item: $('#scheduler-select').val(),
            start: start,
            end: end,
            title: title
        });
        // now add it to the calendar
        eventData = {
            title: title,
            start: start,
        };
        $('#scheduler').fullCalendar('renderEvent', eventData, true);
    }
}
</script>

<?php require_once('app/footer.inc.php');
