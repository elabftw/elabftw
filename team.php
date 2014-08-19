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
            <li>- You can use a TODOlist by pressing 't'</li>
            <li>- You can have templates (edit them in your User Control Panel)</li>
            <li>- If you press Ctrl Shift D in the editor, the date will appear under the cursor</li>
            <li>- You can duplicate experiments in one click</li>
            <li>- Click a tag to list all items with this tag</li>
        </ul>
    </p>
</div>

</div>

<script>
$(document).ready(function() {
    // TABS

    // init
    $(".divhandle").hide();
    $("#tab1div").show();
    $("#tab1").addClass('selected');

    $(".tabhandle" ).click(function(event) {
        var tabhandle = '#' + event.target.id;
        var divhandle = '#' + event.target.id + 'div';
        $(".divhandle").hide();
        $(divhandle).show();
        $(".tabhandle").removeClass('selected');
        $(tabhandle).addClass('selected');
    });
});
</script>

<?php require_once('inc/footer.php');?>
