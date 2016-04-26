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

use PDO;

/**
 * The team page
 *
 */
require_once 'inc/common.php';
$page_title = _('Team');
$selected_menu = 'Team';
require_once 'inc/head.php';

$Users = new Users();
?>

<menu>
<ul>
<li class='tabhandle' id='tab1'><?= _('Members') ?></li>
<li class='tabhandle' id='tab2'><?= _('Statistics')?></li>
<li class='tabhandle' id='tab3'><?= _('Tools') ?></li>
<li class='tabhandle' id='tab4'><?= _('Help') ?></li>
</ul>
</menu>

<!-- TAB 1 MEMBERS -->
<div class='divhandle' id='tab1div'>
<?php display_message('ok_nocross', sprintf(_('You belong to the %s team.'), get_team_config('team_name'))) ?>

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

<!-- TAB 2 STATISTICS -->
<div class='divhandle' id='tab2div'>
<?php
$count_sql = "SELECT
(SELECT COUNT(users.userid) FROM users WHERE users.team = :team) AS totusers,
(SELECT COUNT(items.id) FROM items WHERE items.team = :team) AS totdb,
(SELECT COUNT(experiments.id) FROM experiments WHERE experiments.team = :team) AS totxp";
$count_req = $pdo->prepare($count_sql);
$count_req->bindParam(':team', $_SESSION['team_id']);
$count_req->execute();
$totals = $count_req->fetch(PDO::FETCH_ASSOC);
?>
    <p><?php echo sprintf(ngettext('There is a total of %d experiment', 'There is a total of %d experiments', $totals['totxp']), $totals['totxp']);
                echo ' '.sprintf(ngettext('by %d different user.', 'by %d different users', $totals['totusers']), $totals['totusers']); ?></p>
    <p><?php echo sprintf(ngettext('There is a total of %d item in the database.', 'There is a total of %d items in the database.', $totals['totdb']), $totals['totdb']); ?></p>
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
});
</script>

<?php require_once('inc/footer.php');
