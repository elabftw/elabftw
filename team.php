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
require_once('inc/menu.php');
require_once('inc/info_box.php');
?>
<div id='team'>
<ul>
<li><a href='#team-1'>Members</a></li>
<li><a href='#team-2'>Statistics</a></li>
<li><a href='#team-3'>Tips and tricks</a></li>
</ul>
<!-- *********************** -->
<div id='team-1'>
<?php // SQL to get members info
$sql = "SELECT * FROM users WHERE validated = 1";
$req = $bdd->prepare($sql);
$req->execute();
echo "<ul>";
while ($data = $req->fetch()) {
    echo "<li><img src='img/profile.png' alt='' /> ";
    echo "<a href='mailto:".$data['email']."'>".$data['firstname']." ".$data['lastname']."</a>";
        if (!empty($data['phone'])) { 
        echo " <img src='themes/".$_SESSION['prefs']['theme']."/img/phone.png' alt='Phone :' title='phone' /> ".$data['phone'];
        } 
        if (!empty($data['cellphone'])) { 
        echo " <img src='themes/".$_SESSION['prefs']['theme']."/img/cellphone.png' alt='Cellphone :' title='Cellphone' /> ".$data['cellphone']; 
        }
        if (!empty($data['website'])) { 
        echo " <img src='themes/".$_SESSION['prefs']['theme']."/img/website.png' alt='website :' title='website' /> <a href='".$data['website']."'>www</a>"; 
        }
        if (!empty($data['skype'])) { 
        echo " <img src='themes/".$_SESSION['prefs']['theme']."/img/skype.png' alt='skype :' title='skype' /> ".$data['skype'];
        } 
    echo "</li>";
}
echo "</ul>";
?>
</div>
<!-- *********************** -->
<div id='team-2'>
<?php
// show stats about eLabFTW
// number of experiments total
$sql_exp_total = 'SELECT * FROM experiments';
$req_exp_total = $bdd->prepare($sql_exp_total);
$req_exp_total->execute();
// number of items total
$sql_db_total = 'SELECT * FROM items';
$req_db_total = $bdd->prepare($sql_db_total);
$req_db_total->execute();
?>
    <p>There is a total of <?php echo $req_exp_total->rowCount() ;?> experiments.</p>
    <p>There is a total of <?php echo $req_db_total->rowCount() ;?> items in the database.</p>
</div>

<div id='team-3'>
    <p>
        <ul>
            <li>- You can use a TODOlist by pressing 't'</li>
            <li>- You can have templates (edit them in your User Control Panel</li>
            <li>- If you press Ctrl Shift D in the editor, the date will appear under the cursor</li>
            <li>- You can duplicate experiments in one click</li>
            <li>- Click a tag to list all items with this tag</li>
        </ul>
    </p>
</div>

</div>
<?php require_once('inc/footer.php');?>

<script>
$(document).ready(function() {
    // TABS
    $( "#team" ).tabs({
        autoHeight: false
    });
});
</script>

