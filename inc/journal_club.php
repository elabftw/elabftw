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
?>
<a name='jc'></a><section class='item'>
<h3>JOURNAL CLUB</h3>

<?php
//////////////////////////////////////////
// List labmembers with journal points and last_jc
////////////////////////////////////////////
echo "<p>Current stats :</p><ul>";

// SQL
$sql = "SELECT firstname, lastname, journal, last_jc FROM users ORDER BY last_jc desc";
$req = $bdd->prepare($sql);
$req->execute();
while ($data = $req->fetch()) {
    echo "<li><span class='strongblue'>".$data['firstname']." ".$data['lastname']."</span> already presented ".$data['journal']. " journals (last was on ".$data['last_jc'].")</li>";
}
echo "</ul>";

// Begin switch between already choose nb or not
if(!isset($_POST['nb'])) {
    echo "<h3>Choose the number of participants :</h3>";
?>
<form name="jcForm" method="post" action="team.php">
<select name="nb">
<option value="1">1</option>
<option value="2">2</option>
<option value="3">3</option>
<option value="4" selected>4</option>
<option value="5">5</option>
<option value="6">6</option>
</select> speaker(s)<br /><br />
<input type="submit" name="Submit" value="Who's next ?" />
</form>
<?php
} else {

//////////////////////////////////////
// Code for displaying next members //
// Only between 0 and 20 members
$int_options = array("options"=>
    array("min_range"=>0, "max_range"=>20));
// Check that nb is an int and assign it to $_SESSION because we need it for the increment page
if(filter_var($_POST['nb'], FILTER_VALIDATE_INT, $int_options)) {
        $_SESSION['S_jcnb'] = intval($_POST['nb']);
        session_write_close();
}
// SQL
$req = $bdd->query("SELECT * FROM `users` ORDER BY `users`.`last_jc` ASC LIMIT 0 , ".$_SESSION['S_jcnb']."");
echo "<a name='pres'></a><p>The next Journal Club will be presented by :</p><ul>";
while ($datas = $req->fetch()) {
    echo "<li><span class='strong'>". $datas['username'] . "</span></li>";
}
echo "</ul>";
$req->closeCursor();
//////////////////////////////////////////
// Link for incrementing journal values //
echo "<p>This journal club has passed : <a href='jc-exec.php?inc=1'>increment</a></p>";
}
echo "</section>";
?>
