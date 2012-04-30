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
<?php
// Here we don't check that the experiment id is owned by the viewer, so links can be shared :)
// Check id is valid and assign it to $id
if(filter_var($_GET['id'], FILTER_VALIDATE_INT)) {
    $id = $_GET['id'];
} else {
    die("The id parameter in the URL isn't a valid experiment ID");
}

// SQL for viewXP
$sql = "SELECT * FROM experiments WHERE id = ".$id;
$req = $bdd->prepare($sql);
$req->execute();
$data = $req->fetch();

// Display experiment
?>
<!-- click section to edit XP -->
<section OnClick="document.location='experiments.php?mode=edit&id=<?php echo $data['id'];?>'" class="item <?php echo $data['outcome'];?>">
<a class='align_right' href='delete_item.php?id=<?php echo $data['id'];?>&type=exp' onClick="return confirm('Delete this experiment ?');"><img src='themes/<?php echo $_SESSION['prefs']['theme'];?>/img/trash.png' title='delete' alt='delete' /></a>
<?php
echo "<span class='date'><img src='themes/".$_SESSION['prefs']['theme']."/img/calendar.png' title='date' alt='Date :' />".$data['date']."</span><br />
    <a href='experiments.php?mode=edit&id=".$data['id']."'><img src='themes/".$_SESSION['prefs']['theme']."/img/edit.png' title='edit' alt='edit' /></a> 
<a href='duplicate_item.php?id=".$data['id']."&type=exp'><img src='themes/".$_SESSION['prefs']['theme']."/img/duplicate.png' title='duplicate experiment' alt='duplicate' /></a> 
<a href='make_pdf.php?id=".$data['id']."&type=exp'><img src='themes/".$_SESSION['prefs']['theme']."/img/pdf.png' title='make a pdf' alt='pdf' /></a> 
<a href='javascript:window.print()'><img src='themes/".$_SESSION['prefs']['theme']."/img/print.png' title='Print this page' alt='Print' /></a> 
<a href='make_zip.php?id=".$data['id']."&type=exp'><img src='themes/".$_SESSION['prefs']['theme']."/img/zip.gif' title='make a zip archive' alt='zip' /></a>";
// <a href='publish.php?id=".$data['id']."&type=exp'><img src='themes/".$_SESSION['prefs']['theme']."/img/publish.png' title='submit to a journal' alt='publish' /></a>";
// TAGS
$sql = "SELECT tag FROM experiments_tags WHERE item_id = ".$id;
$req = $bdd->prepare($sql);
$req->execute();
echo "<span class='tags'><img src='themes/".$_SESSION['prefs']['theme']."/img/tags.gif' alt='' /> ";
while($tags = $req->fetch()){
    echo "<a href='experiments.php?mode=show&tag=".stripslashes($tags['tag'])."'>".stripslashes($tags['tag'])."</a> ";
}
echo "</span>";
// END TAGS
?>
<?php
echo "<div class='title'>". stripslashes($data['title']) . "<span class='align_right' id='outcome'>(".$data['outcome'].")<span></div> ";
// BODY (show only if not empty)
if ($data['body'] != ''){
echo "<div class='txt'>".stripslashes($data['body'])."</div>";
}
// DISPLAY PROTOCOL
if ($data['item'] != NULL) {
    // SQL to get title
    $sql = "SELECT id, title FROM items WHERE id = ".$data['item'];
    $req = $bdd->prepare($sql);
    $req->execute();
    $itemdata = $req->fetch();
    echo "<p>Linked item : <a href='database.php?mode=view&id=".$itemdata['id']."'>".$itemdata['title']."</a></p>";
}
echo "</section>";

// DISPLAY FILES
require_once('inc/display_file.php');
// KEYBOARD SHORTCUTS
echo "<script>
key('".$_SESSION['prefs']['shortcuts']['create']."', function(){location.href = 'create_item.php?type=exp'});
key('".$_SESSION['prefs']['shortcuts']['edit']."', function(){location.href = 'experiments.php?mode=edit&id=".$id."'});
</script>";
?>
