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
<script src="js/editinplace.js" type="text/javascript"></script>
<h2>VIEW PROTOCOL</h2>

<?php
// Check id is valid and assign it to $id
if(filter_var($_GET['id'], FILTER_VALIDATE_INT)) {
    $id = $_GET['id'];
} else {
    die("The id parameter in the URL isn't a valid protocol ID");
}

// SQL for viewXP
$sql = "SELECT * FROM protocols WHERE id = ".$id;
$req = $bdd->prepare($sql);
$req->execute();
$data = $req->fetch();

// Display protocol
?>
<!-- click section to edit PR -->
<section OnClick="document.location='protocols.php?mode=edit&id=<?php echo $data['id'];?>'" class="item">
<a class='align_right' href='delete_item.php?id=<?php echo $data['id'];?>&type=prot' onClick="return confirm('Delete this protocol ?');"><img src='themes/<?php echo $_SESSION['prefs']['theme'];?>/img/trash.png' title='delete' alt='delete' /></a>
<?php
echo "<span class='date'><img src='themes/".$_SESSION['prefs']['theme']."/img/calendar.png' title='date' alt='Date :' />".$data['date']."</span><br />
    <a href='protocols.php?mode=edit&id=".$data['id']."'><img src='themes/".$_SESSION['prefs']['theme']."/img/edit.png' title='edit' alt='edit' /></a> 
<a href='make_pdf.php?id=".$data['id']."&type=prot'><img src='themes/".$_SESSION['prefs']['theme']."/img/pdf.png' title='make a pdf' alt='pdf' /></a> 
<a href='javascript:window.print()'><img src='themes/".$_SESSION['prefs']['theme']."/img/print.png' title='Print this page' alt='Print' /></a> 
<a href='make_zip.php?id=".$data['id']."&type=prot'><img src='themes/".$_SESSION['prefs']['theme']."/img/zip.gif' title='make a zip archive' alt='zip' /></a>";
// TAGS
$sql = "SELECT tag FROM protocols_tags WHERE item_id = ".$id;
$req = $bdd->prepare($sql);
$req->execute();
echo "<span class='tags'><img src='themes/".$_SESSION['prefs']['theme']."/img/tags.gif' alt='' /> ";
while($tags = $req->fetch()){
    echo "<a href='protocols.php?mode=show&tag=".stripslashes($tags['tag'])."'>".stripslashes($tags['tag'])."</a> ";
}
echo "</span>";
// END TAGS
?>
<?php
echo "<p class='title'>". stripslashes($data['title']) . "</p>";
// BODY (show only if not empty)
if ($data['body'] != ''){
echo "<p class='txt'>".nl2br(stripslashes($data['body']))."</p>";
}
// Get userinfo
$sql = "SELECT firstname, lastname FROM users WHERE userid = ".$data['userid'];
$requser = $bdd->prepare($sql);
$requser->execute();
$datauser = $requser->fetch();
echo "Last modified by ".$datauser['firstname']." ".$datauser['lastname']." on ".$data['date'];
echo "</section>";
// DISPLAY FILES
require_once('inc/display_file.php');
// KEYBOARD SHORTCUTS
echo "<script type='text/javascript'>
key('".$_SESSION['prefs']['shortcuts']['create']."', function(){location.href = 'protocols.php?mode=create'});
key('".$_SESSION['prefs']['shortcuts']['edit']."', function(){location.href = 'protocols.php?mode=edit&id=".$id."'});
</script>";
?>
