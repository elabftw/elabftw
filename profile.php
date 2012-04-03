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
$page_title = 'PROFILE';
require_once('inc/head.php');
require_once('inc/menu.php');
// SQL to get number of experiments
$sql = "SELECT COUNT(*) FROM experiments WHERE userid = ".$_SESSION['userid'];
$req = $bdd->prepare($sql);
$req->execute();

$count = $req->fetch();

// SQL for profile
$sql = "SELECT * FROM users WHERE userid = ".$_SESSION['userid'];
$req = $bdd->prepare($sql);
$req->execute();
$data = $req->fetch();

// Calculate number of experiments/day TODO take into account business days and holidays
//$days_since_reg = daydiff($data['register_date']);
//// if user registered today; avoid division by 0
//if ($days_since_reg == 0){
//    $days_since_reg = 1;
//}
//$exp_per_day = ($count[0] / $days_since_reg);
//$exp_per_day = number_format($exp_per_day, 1, '.', ' ');

echo "<section class='item'>";
echo "<img src='themes/".$_SESSION['prefs']['theme']."/img/user.png' alt='' /> <h4>INFOS</h4>";
echo "<div class='center'>
    <p>".$data['firstname']." ".$data['lastname']." (".$data['email'].")</p>
    <p>".$count[0]." experiments done since ".date("Y-m-d", $data['register_date']);
if($data['group'] == 'admin') {echo "<p>You ARE admin \o/</p>";}
if($data['group'] === 'journalclub') {echo "<p>You ARE responsible of the <a href='journal-club.php'>Journal Club</a> !</p>";}
echo "</div>";

echo "<hr>";
require_once('inc/statistics.php');
echo "<hr>";
require_once('inc/tagcloud.php');

echo "</section>";

require_once('inc/footer.php');
?>
