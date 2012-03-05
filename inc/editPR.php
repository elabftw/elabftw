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
echo "<script src='js/editinplace.js' type='text/javascript'></script>";
echo "<h2>EDIT PROTOCOL</h2>";

// ID
if (isset($_GET['id']) && !empty($_GET['id'])) {
    $filter_options = array(
        'options' => array(
            'min_range' => 1
        ));
    $id = filter_var($_GET['id'], FILTER_VALIDATE_INT, $filter_options);
} else {
    die("The id parameter in the URL isn't a valid protocol ID.");
}

// GET CONTENT
$sql = "SELECT title, date, body, userid FROM protocols WHERE id = ".$id;
$req = $bdd->prepare($sql);
$req->execute();
$data = $req->fetch();

// BEGIN CONTENT
?>
<section class='item'>
<a class='align_right' href='delete_item.php?id=<?php echo $_SESSION['id'];?>&type=prot' onClick="return confirm('Delete this protocol ?');"><img src='themes/<?php echo $_SESSION['prefs']['theme'];?>/img/trash.png' title='delete' alt='delete' /></a>
<!-- ADD TAG FORM -->
<form id="addtag" name="addtag" method="post" action="add_tag.php">
<img src='themes/<?php echo $_SESSION['prefs']['theme'];?>/img/tags.gif' alt='' /> <h4>Tags</h4><span class='smallgray'> (click a tag to remove it)</span><br />
<span class='tags'>
<?php
$sql = "SELECT id, tag FROM protocols_tags WHERE item_id = ".$id;
$tagreq = $bdd->prepare($sql);
$tagreq->execute();
// DISPLAY TAGS
while($tags = $tagreq->fetch()){
?>
    <span class='tag'><a href='delete_tag.php?id=<?php echo $tags['id'];?>&item_id=<?php echo $id;?>&type=prot' onClick="return confirm('Delete this tag ?');">
<?php echo stripslashes($tags['tag']);}?></a> 
<input name='item_id' type='hidden' value='<? echo $id;?>' />
<input name='type' type='hidden' value='prot' />
<input id='addtaginput' name='tag' placeholder='Add one tag' />
<a href="javascript: document.forms['addtag'].submit()"><img alt='add' src='themes/<?php echo $_SESSION['prefs']['theme'];?>/img/plus.png' /></a>
</span></span>
</form><!-- END ADD TAG -->
<br />

<!-- BEGIN 2ND FORM -->
<form id="editPR" name="editPR" method="post" action="editPR-exec.php" enctype='multipart/form-data'>
<input name='item_id' type='hidden' value='<? echo $id;?>' />
<input name='date' type='hidden' value='<?php echo kdate();?>'>
<h4>Title</h4><br />
      <textarea id='title' name='title' rows="1" cols="80"><?php if(empty($_SESSION['errors'])){
          echo stripslashes($data['title']);
      } else {
          echo stripslashes($_SESSION['new_title']);
      } ?></textarea>
 <br /><br />
<h4>Protocol</h4><br />
      <textarea id='body' name='body' rows="15" cols="80"><?php if(empty($_SESSION['errors'])){
        echo stripslashes($data['body']);
    } else {
        echo stripslashes($_SESSION['new_body']);
    } ?></textarea>
<br /><br />
<?php
// FILE UPLOAD
require_once('inc/file_upload.php');
// DISPLAY FILES
require_once('inc/display_file.php');
?>
</div>

</div>
<!-- SUBMIT BUTTON -->
<div class='center' id='submitdiv'>
<p>SUBMIT</p>
<input type='image' src='themes/<?php echo $_SESSION['prefs']['theme'];?>/img/submit.png' name='Submit' value='Submit' onClick="this.form.submit();" />
</div>
</form>
</section>
<!-- end edit protocols form -->
<?php
    // unset session variables
unset($_SESSION['errors']);

// Give focus to add tag field if we just added a tag
if(isset($_GET['tagadded'])){echo "
<script type='text/javascript'>document.getElementById('addtaginput').focus();</script>";}
// KEYBOARD SHORTCUTS
echo "<script type='text/javascript'>
key('".$_SESSION['prefs']['shortcuts']['create']."', function(){location.href = 'protocols.php?mode=create'});
key('".$_SESSION['prefs']['shortcuts']['submit']."', function(){document.forms['editPR'].submit()});
</script>";
?>
