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
require_once 'lang/'.$_SESSION['prefs']['lang'].'.php';
$page_title= TEAM_TITLE; 
$selected_menu = 'Team';
require_once('inc/head.php');
require_once('inc/info_box.php');
?>
<menu>
<ul>
<li class='tabhandle' id='tab1'><?php echo SYSCONFIG_MEMBERS;?></li>
<li class='tabhandle' id='tab2'><?php echo TEAM_STATISTICS;?></li>
<li class='tabhandle' id='tab3'><?php echo TEAM_TIPS_TRICKS;?></li>
</ul>
</menu>
<!-- *********************** -->
<div class='divhandle' id='tab1div'>
<?php display_message('info_nocross', TEAM_BELONG.' '.get_team_config('team_name').' '.TEAM_TEAM);?>
<table id='teamtable'>
    <tr>
        <th><?php echo NAME;?></th>
        <th><?php echo PHONE;?></th>
        <th><?php echo MOBILE;?></th>
        <th><?php echo WEBSITE;?></th>
        <th><?php echo SKYPE;?></th>
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
        } else {
            echo "<td>&nbsp;</td>"; // Placeholder
        }
        if (!empty($data['cellphone'])) { 
            echo "<td>".$data['cellphone']."</td>"; 
        } else {
            echo "<td>&nbsp;</td>";
        }
        if (!empty($data['website'])) { 
            echo "<td><a href='".$data['website']."'>www</a></td>"; 
        } else {
            echo "<td>&nbsp;</td>";
        }
        if (!empty($data['skype'])) { 
            echo "<td>".$data['skype']."</td>";
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
$count_sql="SELECT
(SELECT COUNT(users.userid) FROM users WHERE users.team = :team) AS totusers,
(SELECT COUNT(items.id) FROM items WHERE items.team = :team) AS totdb,
(SELECT COUNT(experiments.id) FROM experiments WHERE experiments.team = :team) AS totxp";
$count_req = $pdo->prepare($count_sql);
$count_req->bindParam(':team', $_SESSION['team_id']);
$count_req->execute();
$totals = $count_req->fetch(PDO::FETCH_ASSOC);
?>
    <p><?php echo TEAM_TOTAL_OF.' '.$totals['totxp'].' '.TEAM_EXP_BY.' '.$totals['totusers'].' '.TEAM_DIFF_USERS;?></p>
    <p><?php echo TEAM_TOTAL_OF.' '.$totals['totdb'].' '.TEAM_ITEMS_DB;?></p>
</div>

<!-- *********************** -->
<div class='divhandle' id='tab3div'>
    <p>
        <ul>
        <li class='tip'><?php echo TEAM_TIP_1;?></li>
        <li class='tip'><?php echo TEAM_TIP_2;?></li>
        <li class='tip'><?php echo TEAM_TIP_3;?></li>
        <li class='tip'><?php echo TEAM_TIP_4;?></li>
        <li class='tip'><?php echo TEAM_TIP_5;?></li>
        <li class='tip'><?php echo TEAM_TIP_6;?></li>
        <li class='tip'><?php echo TEAM_TIP_7;?></li>
        <li class='tip'><?php echo TEAM_TIP_8;?></li>
        <li class='tip'><?php echo TEAM_TIP_9;?></li>
        <li class='tip'><?php echo TEAM_TIP_10;?></li>
        </ul>
    </p>
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
