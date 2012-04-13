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
// inc/editXP.php
?>
<script type="text/javascript" src="js/tiny_mce/tiny_mce.js"></script>
<?php
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


// SQL for editXP
$sql = "SELECT * FROM plasmids WHERE id = ".$id;
$req = $bdd->prepare($sql);
$req->execute();
$data = $req->fetch();

// BEGIN CONTENT
?>
<section>
<a class='align_right' href='delete_item.php?id=<?php echo $id;?>&type=pla' onClick="return confirm('Delete this plasmid ?');"><img src='themes/<?php echo $_SESSION['prefs']['theme'];?>/img/trash.png' title='delete' alt='delete' /></a>
<!-- BEGIN EDITXP FORM -->
<form id="editPL" name="editPL" method="post" action="editPL-exec.php" enctype='multipart/form-data'>
<input name='item_id' type='hidden' value='<? echo $id;?>' />
<h4>Date</h4><span class='smallgray'> (date format : YYMMDD)</span><br />
<img src='themes/<?php echo $_SESSION['prefs']['theme'];?>/img/calendar.png' title='date' alt='Date :' /><input name='date' id='datepicker' size='6' type='text' value='<?php echo $data['date'];?>' /><br />
<br /><h4>Name</h4><br />
      <textarea id='name' name='name' rows="1" cols="80"><?php if(empty($_SESSION['errors'])){
          echo stripslashes($data['name']);
      } else {
          echo stripslashes($_SESSION['new_name']);
      } ?></textarea>
<br /><br /><h4>Alias</h4>
<br />
<textarea name='priority' rows="1" cols="80"><?php if(empty($_SESSION['errors'])){
    echo stripslashes($data['priority']);
    } else {
    echo stripslashes($_SESSION['new_priority']);
    } ?>
</textarea>
<br /><br /><h4>resistance</h4>
<br />
<textarea name='resistance' rows="1" cols="80"><?php if(empty($_SESSION['errors'])){
    echo stripslashes($data['resistance']);
    } else {
    echo stripslashes($_SESSION['new_resistance']);
    } ?>
</textarea>
<br /><br /><h4>organism</h4>
<br />
<textarea name='organism' rows="1" cols="80"><?php if(empty($_SESSION['errors'])){
    echo stripslashes($data['organism']);
    } else {
    echo stripslashes($_SESSION['new_organism']);
    } ?>
</textarea>
<br /><br /><h4>tag</h4>
<br />
<textarea name='tag' rows="1" cols="80"><?php if(empty($_SESSION['errors'])){
    echo stripslashes($data['tag']);
    } else {
    echo stripslashes($_SESSION['new_tag']);
    } ?>
</textarea>
<br /><br /><h4>comment</h4>
<br />
<textarea name='comment' rows="1" cols="80"><?php if(empty($_SESSION['errors'])){
    echo stripslashes($data['comment']);
    } else {
    echo stripslashes($_SESSION['new_comment']);
    } ?>
</textarea>
<br /><br /><h4>results</h4>
<br />
<textarea name='results' rows="1" cols="80"><?php if(empty($_SESSION['errors'])){
    echo stripslashes($data['results']);
    } else {
    echo stripslashes($_SESSION['new_results']);
    } ?>
</textarea>
<?php
// FILE UPLOAD
require_once('inc/file_upload.php');
// DISPLAY FILES
require_once('inc/display_file.php');
?>

<!-- SUBMIT BUTTON -->
<div class='center' id='submitdiv'>
<p>SUBMIT</p>
<input type='image' src='themes/<?php echo $_SESSION['prefs']['theme'];?>/img/submit.png' name='Submit' value='Submit' onClick="this.form.submit();" />
</div>
</form><!-- end editXP form -->
</section>

<script type='text/javascript'>
// JAVASCRIPT
<?php
// KEYBOARD SHORTCUTS
echo "key('".$_SESSION['prefs']['shortcuts']['create']."', function(){location.href = 'create_item.php?type=pla'});";
echo "key('".$_SESSION['prefs']['shortcuts']['submit']."', function(){document.forms['editPL'].submit()});";
?>
// DATEPICKER
$(function() {
    $( "#datepicker" ).datepicker({dateFormat: 'ymmdd'});
});
</script>
