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

use \PDO;

/**
 * The team page
 *
 */
require_once 'inc/common.php';
$page_title = _('Team');
$selected_menu = 'Team';
require_once 'inc/head.php';
?>
<menu>
<ul>
<li class='tabhandle' id='tab1'><?php echo _('Members'); ?></li>
<li class='tabhandle' id='tab2'><?php echo _('Statistics')?></li>
<li class='tabhandle' id='tab3'><?php echo _('Tools'); ?></li>
<li class='tabhandle' id='tab4'><?php echo _('Help'); ?></li>
</ul>
</menu>
<!-- *********************** -->
<div class='divhandle' id='tab1div'>
<?php display_message('ok_nocross', sprintf(_('You belong to the %s team.'), get_team_config('team_name'))); ?>
<table id='teamtable' class='table'>
    <tr>
        <th><?php echo _('Name'); ?></th>
        <th><?php echo _('Phone'); ?></th>
        <th><?php echo _('Mobile'); ?></th>
        <th><?php echo _('Website'); ?></th>
        <th><?php echo _('Skype'); ?></th>
    </tr>
<?php // SQL to get members info
$sql = "SELECT * FROM users WHERE validated = :validated AND team = :team_id";
$req = $pdo->prepare($sql);
$req->execute(array(
    'validated' => 1,
    'team_id' => $_SESSION['team_id']
));

while ($data = $req->fetch()) {
    echo "<tr>";
    echo "<td><a href='mailto:" . $data['email'] . "'><span";
    // put sysadmin, admin and chiefs in bold
    if ($data['usergroup'] == 3 || $data['usergroup'] == 1 || $data['usergroup'] == 2) {
        echo " style='font-weight:bold'";
    }
    echo ">" . $data['firstname'] . " " . $data['lastname'] . "</span></a>";
    echo "</td>";
    if (!empty($data['phone'])) {
        echo "<td>" . $data['phone'] . "</td>";
    } else {
        echo "<td>&nbsp;</td>"; // Placeholder
    }
    if (!empty($data['cellphone'])) {
        echo "<td>" . $data['cellphone'] . "</td>";
    } else {
        echo "<td>&nbsp;</td>";
    }
    if (!empty($data['website'])) {
        echo "<td><a href='" . $data['website'] . "'>www</a></td>";
    } else {
        echo "<td>&nbsp;</td>";
    }
    if (!empty($data['skype'])) {
        echo "<td>" . $data['skype'] . "</td>";
    } else {
        echo "<td>&nbsp;</td>";
    }
}
?>
</table>
</div>
<!-- *********************** -->
<div class='divhandle' id='tab2div'>
<?php
// show team stats
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
                echo ' ' . sprintf(ngettext('by %d different user.', 'by %d different users', $totals['totusers']), $totals['totusers']); ?></p>
    <p><?php echo sprintf(ngettext('There is a total of %d item in the database.', 'There is a total of %d items in the database.', $totals['totdb']), $totals['totdb']); ?></p>
</div>

<!-- *********************** -->
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

<!-- *********************** -->
<div class='divhandle' id='tab4div'>
    <p>
        <ul>
        <li class='tip'><?php printf(_('There is a manual available %shere%s.'), "<a href='doc/_build/html/manual.html'>", "</a>"); ?></li>
        <li class='tip'><?php echo _("You can use a TODOlist by pressing 't'."); ?></li>
        <li class='tip'><?php printf(_('You can have experiments templates (%sControl Panel%s).'), "<a href='ucp.php?tab=3'>", "</a>"); ?></li>
        <li class='tip'><?php printf(_('The admin of a team can edit the status and the types of items available (%sAdmin Panel%s).'), "<a href='admin.php?tab=4'>", "</a>"); ?></li>
        <li class='tip'><?php echo _('If you press Ctrl Shift D in the editor, the date will appear under the cursor.'); ?></li>
        <li class='tip'><?php printf(_('Custom shortcuts are available (%sControl Panel%s).'), "<a href='ucp.php?tab=2'>", "</a>"); ?></li>
        <li class='tip'><?php echo _('You can duplicate experiments in one click.'); ?></li>
        <li class='tip'><?php echo _('Click a tag to list all items with this tag.'); ?></li>
        <li class='tip'><?php echo _('Only a locked experiment can be timestamped.'); ?></li>
        <li class='tip'><?php echo _('Once timestamped an experiment cannot be unlocked or modified. Only comments can be added.'); ?></li>
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

<?php
require_once('inc/footer.php');
