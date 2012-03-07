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
$page_title= $ini_arr['lab_name'];
require_once('inc/head.php');
require_once('inc/menu.php');
echo "<h2>".strtoupper($ini_arr['lab_name'])."</h2>";
?>
<section class='item'>
<h3>LABMEETINGS</h3>
<p><a href='http://wiki-bio6.curie.fr/wiki/index.php/Piel_Lab_inner_working#Lab_meetings' target='_blank'>Relevant wiki link</a></p>
<p class='center'><img src='img/labmeetings-2012.png' alt='labmeetings' title='labmeetings 2012' /></p>
</section>

<section class='item'>
<h3>TEAM MEMBERS</h3>
<?php // SQL to get members info
$sql = "SELECT * FROM users WHERE validated = 1";
$req = $bdd->prepare($sql);
$req->execute();
echo "<ul>";
while ($data = $req->fetch()) {
    echo "<li>";
    if ($data['is_admin'] == 1){
        echo '# ';
    } elseif ($data['is_pi'] == 1) {
        echo '% ';
    } elseif ($data['is_jc_resp'] == 1) {
        echo '(jc) ';
    } else {
        echo '$ ';
    }
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
</section>

<?php
require_once('inc/journal_club.php');
?>
<?php
require_once('inc/footer.php');
?>
