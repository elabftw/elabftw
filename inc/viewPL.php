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
    die("The id parameter in the URL isn't a valid plasmid ID");
}

// SQL for viewPL
$sql = "SELECT * FROM plasmids WHERE id = ".$id;
$req = $bdd->prepare($sql);
$req->execute();
$data = $req->fetch();

// Display plasmid
?>
<!-- click section to edit PL -->
<section OnClick="document.location='plasmids.php?mode=edit&id=<?php echo $data['id'];?>'" class="item">
<a class='align_right' href='delete_item.php?id=<?php echo $data['id'];?>&type=pla' onClick="return confirm('Delete this plasmid ?');"><img src='themes/<?php echo $_SESSION['prefs']['theme'];?>/img/trash.png' title='delete' alt='delete' /></a>
<?php
echo "<span class='date'><img src='themes/".$_SESSION['prefs']['theme']."/img/calendar.png' title='date' alt='Date :' />".$data['date']."</span><br />
    <a href='plasmids.php?mode=edit&id=".$data['id']."'><img src='themes/".$_SESSION['prefs']['theme']."/img/edit.png' title='edit' alt='edit' /></a> 
<a href='duplicate_item.php?id=".$data['id']."&type=pla'><img src='themes/".$_SESSION['prefs']['theme']."/img/duplicate.png' title='duplicate experiment' alt='duplicate' /></a> 
<a href='javascript:window.print()'><img src='themes/".$_SESSION['prefs']['theme']."/img/print.png' title='Print this page' alt='Print' /></a>";
echo "<div class='title'>". stripslashes($data['title']) . "</div>";
?>
<!-- STAR RATING read only (disabled='disabled') -->
<div id='rating'>
<?php // SQL to get current rating
$sql = "SELECT rating FROM plasmids WHERE id = ".$id;
$req = $bdd->prepare($sql);
$req->execute();
$rating = $req->fetch();
?>
<input id='star1' name="star" type="radio" class="star" value='click to edit' disabled='disabled' <?php if ($rating['rating'] == 1){ echo "checked=checked ";}?>/>
<input id='star2' name="star" type="radio" class="star" value='click to edit' disabled='disabled' <?php if ($rating['rating'] == 2){ echo "checked=checked ";}?>/>
<input id='star3' name="star" type="radio" class="star" value='click to edit' disabled='disabled' <?php if ($rating['rating'] == 3){ echo "checked=checked ";}?>/>
<input id='star4' name="star" type="radio" class="star" value='click to edit' disabled='disabled' <?php if ($rating['rating'] == 4){ echo "checked=checked ";}?>/>
<input id='star5' name="star" type="radio" class="star" value='click to edit' disabled='disabled' <?php if ($rating['rating'] == 5){ echo "checked=checked ";}?>/>
</div><!-- END STAR RATING -->
<br />
<?php
// BODY (show only if not empty)
if ($data['body'] != ''){
echo "<div class='txt'>".stripslashes($data['body'])."</div>";
}
echo "</section>";

// DISPLAY FILES
require_once('inc/display_file.php');
// KEYBOARD SHORTCUTS
echo "<script type='text/javascript'>
    $(function(){
        $('input.star').rating();
        });
key('".$_SESSION['prefs']['shortcuts']['create']."', function(){location.href = 'create_item.php?type=exp'});
key('".$_SESSION['prefs']['shortcuts']['edit']."', function(){location.href = 'plasmids.php?mode=edit&id=".$id."'});
</script>";
?>
