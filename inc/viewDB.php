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
// inc/viewDB.php
// ID
if(isset($_GET['id']) && !empty($_GET['id']) && is_pos_int($_GET['id'])){
    $id = $_GET['id'];
} else {
    die("The id parameter in the URL isn't a valid item ID.");
}

// SQL for viewDB
$sql = "SELECT * FROM items WHERE id = :id";
$req = $bdd->prepare($sql);
$req->execute(array(
    'id' => $id
));
$data = $req->fetch();
?>
<section class="item">
<a class='align_right' href='delete_item.php?id=<?php echo $data['id'];?>' onClick="return confirm('Delete this item ?');"><img src='themes/<?php echo $_SESSION['prefs']['theme'];?>/img/trash.png' title='delete' alt='delete' /></a>
<h3 style='color:#<?php echo get_item_info_from_id($data['type'], 'bgcolor');?>'><?php echo get_item_info_from_id($data['type'], 'name');?> </h3>
<?php
echo "<span class='date'><img src='themes/".$_SESSION['prefs']['theme']."/img/calendar.png' title='date' alt='Date :' />".$data['date']."</span><br />";
show_stars($data['rating']);
// buttons
echo "<a href='database.php?mode=edit&id=".$data['id']."'><img src='themes/".$_SESSION['prefs']['theme']."/img/edit.png' title='edit' alt='edit' /></a> 
<a href='duplicate_item.php?id=".$data['id']."&type=db'><img src='themes/".$_SESSION['prefs']['theme']."/img/duplicate.png' title='duplicate item' alt='duplicate' /></a> 
<a href='make_pdf.php?id=".$data['id']."&type=items'><img src='themes/".$_SESSION['prefs']['theme']."/img/pdf.png' title='make a pdf' alt='pdf' /></a> 
<a href='javascript:window.print()'><img src='themes/".$_SESSION['prefs']['theme']."/img/print.png' title='Print this page' alt='Print' /></a> 
<a href='make_zip.php?id=".$data['id']."&type=items'><img src='themes/".$_SESSION['prefs']['theme']."/img/zip.gif' title='make a zip archive' alt='zip' /></a>
<a href='experiments.php?mode=show&related=".$data['id']."'><img src='img/related.png' alt='Linked experiments' title='Linked experiments' /></a>";
// TAGS
echo show_tags($id, 'items_tags');
// TITLE : click on it to go to edit mode
?>
<div OnClick="document.location='database.php?mode=edit&id=<?php echo $data['id'];?>'" class='title'>
    <?php echo stripslashes($data['title']);?>
</div>
<?php
// BODY (show only if not empty)
if ($data['body'] != ''){
    ?>
    <div OnClick="document.location='database.php?mode=edit&id=<?php echo $data['id'];?>'" class='txt'><?php echo stripslashes($data['body'])?></div>
<?php
}
// Get userinfo
$sql = "SELECT firstname, lastname FROM users WHERE userid = :userid";
$requser = $bdd->prepare($sql);
$requser->execute(array(
    'userid' => $data['userid']
));
$datauser = $requser->fetch();
echo "Last modified by ".$datauser['firstname']." ".$datauser['lastname']." on ".$data['date'];
echo "</section>";
// DISPLAY FILES
require_once('inc/display_file.php');
// KEYBOARD SHORTCUTS
echo "<script>
key('".$_SESSION['prefs']['shortcuts']['create']."', function(){location.href = 'create_item.php?type=prot'});
key('".$_SESSION['prefs']['shortcuts']['edit']."', function(){location.href = 'database.php?mode=edit&id=".$id."'});
</script>";
?>
<script>
// change title
$(document).ready(function() {
    // fix for the ' and "
    title = "<?php echo $data['title']; ?>".replace(/\&#39;/g, "'").replace(/\&#34;/g, "\"");
    document.title = title;
});
</script>

