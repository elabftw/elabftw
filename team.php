<?php
/********************************************************************************
*                                                                               *
*   Copyright 2012 Nicolas CARPi (nicolas.carpi@gmail.com)                      *
*   http://www.elabftw.net/                                                     *
*                                                                               *
********************************************************************************/

/********************************************************************************
*  This file is part of eLabFTW.                                                *
*                                                                               *
*    eLabFTW is free software: you can redistribute it and/or modify            *
*    it under the terms of the GNU Affero General Public License as             *
*    published by the Free Software Foundation, either version 3 of             *
*    the License, or (at your option) any later version.                        *
*                                                                               *
*    eLabFTW is distributed in the hope that it will be useful,                 *
*    but WITHOUT ANY WARRANTY; without even the implied                         *
*    warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR                    *
*    PURPOSE.  See the GNU Affero General Public License for more details.      *
*                                                                               *
*    You should have received a copy of the GNU Affero General Public           *
*    License along with eLabFTW.  If not, see <http://www.gnu.org/licenses/>.   *
*                                                                               *
********************************************************************************/
require_once('inc/common.php');
$page_title= 'Team'; 
require_once('inc/head.php');
require_once('inc/info_box.php');
?>
<div class='menu'>
<ul>
<li class='tabhandle' id='tab1'>Members</li>
<li class='tabhandle' id='tab2'>Statistics</li>
<li class='tabhandle' id='tab3'>Tips and tricks</li>
</ul>
</div>
<div id='team'>
<!-- *********************** -->
<div class='divhandle' id='tab1div'>
<?php display_message('info_nocross', "You belong to the ".get_team_config('team_name')." team.");?>
<table id='teamtable'>
    <tr>
        <th>Name</th>
        <th>Phone</th>
        <th>Mobile</th>
        <th>Website</th>
        <th>Skype</th>
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
    echo "<td><a href='mailto:".$data['email']."'>".$data['firstname']." ".$data['lastname']."</a></td>";
        if (!empty($data['phone'])) { 
            echo "<td>".$data['phone']."</td>";
        } 
        if (!empty($data['cellphone'])) { 
            echo "<td>".$data['cellphone']."</td>"; 
        }
        if (!empty($data['website'])) { 
            echo "<td><a href='".$data['website']."'>www</a></td>"; 
        }
        if (!empty($data['skype'])) { 
            echo "<td>".$data['skype']."</td>";
        } 
}
?>
</table>
</div>
<!-- *********************** -->
<div class='divhandle' id='tab2div'>
<?php
// show team stats
$count_sql="SELECT
(SELECT COUNT(users.userid) FROM users WHERE users.team = :team) AS totusers,
(SELECT COUNT(items.id) FROM items WHERE items.team = :team) AS totdb,
(SELECT COUNT(experiments.id) FROM experiments WHERE experiments.team = :team) AS totxp";
$count_req = $pdo->prepare($count_sql);
$count_req->bindParam(':team', $_SESSION['team_id']);
$count_req->execute();
$totals = $count_req->fetch(PDO::FETCH_ASSOC);
?>
    <p>There is a total of <?php echo $totals['totxp'];?> experiments by <?php echo $totals['totusers'];?> different users.</p>
    <p>There is a total of <?php echo $totals['totdb'];?> items in the database.</p>
</div>

<!-- *********************** -->
<div class='divhandle' id='tab3div'>
    <p>
        <ul>
            <li class='tip'>You can use a TODOlist by pressing 't'</li>
            <li class='tip'>You can have experiments templates (<a href='ucp.php?tab=3'>Control Panel</a>)</li>
            <li class='tip'>The admin of a team can edit the status and the types of items available (<a href='admin.php?tab=4'>Admin Panel</a>)</li>
            <li class='tip'>If you press Ctrl Shift D in the editor, the date will appear under the cursor</li>
            <li class='tip'>Custom shortcuts are available (<a href='ucp.php?tab=2'>Control Panel</a>)</li>
            <li class='tip'>You can duplicate experiments in one click</li>
            <li class='tip'>Click a tag to list all items with this tag</li>
            <li class='tip'>Register an account with <a href='https://www.universign.eu/en/timestamp'>Universign</a> to start timestamping experiments</li>
            <li class='tip'>Only a locked experiment can be timestamped</li>
            <li class='tip'>Once timestamped, an experiment cannot be unlocked or modified. Only comments can be added.</li>
        </ul>
    </p>
</div>

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
});
</script>

<?php require_once('inc/footer.php');?>
