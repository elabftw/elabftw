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
require_once 'inc/common.php';
$page_title = _('Profile');
$selected_menu = null;
require_once 'inc/head.php';

// SQL to get number of experiments
$sql = "SELECT COUNT(*) FROM experiments WHERE userid = :userid";
$req = $pdo->prepare($sql);
$req->bindParam(':userid', $_SESSION['userid'], PDO::PARAM_INT);
$req->execute();

$count = $req->fetch();

// SQL for profile
$sql = "SELECT * FROM users WHERE userid = :userid";
$req = $pdo->prepare($sql);
$req->bindParam(':userid', $_SESSION['userid'], PDO::PARAM_INT);
$req->execute();
$data = $req->fetch();

echo "<section class='box'>";
echo "<img src='img/user.png' alt='user' /> <h4>" . _('Infos') . "</h4>";
echo "<div class='center'>
    <p>".$data['firstname'] . " " . $data['lastname'] . " (" . $data['email'] . ")</p>
    <p>".$count[0] . " " . _('experiments done since') . " " . date("l jS \of F Y", $data['register_date'])
    ."<p><a href='ucp.php'>" . _('Go to user control panel') . "</a>";
echo "</div>";
echo "</section>";
require_once 'inc/statistics.php';
require_once 'inc/tagcloud.php';
require_once 'inc/footer.php';
