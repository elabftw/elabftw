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
// inc/editPL.php
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
    die("The id parameter in the URL isn't a valid plasmid ID.");
}


// SQL for editXP
$sql = "SELECT * FROM plasmids WHERE id = ".$id;
$req = $bdd->prepare($sql);
$req->execute();
$data = $req->fetch();

// BEGIN CONTENT
?>
<section class='item'>
<a class='align_right' href='delete_item.php?id=<?php echo $id;?>&type=pla' onClick="return confirm('Delete this plasmid ?');"><img src='themes/<?php echo $_SESSION['prefs']['theme'];?>/img/trash.png' title='delete' alt='delete' /></a>
<!-- star rating -->
<div id='rating'>
<form name="stars" method="post" action="stars.php">
<input name="star" type="radio" class="star" />
<input name="star" type="radio" class="star" />
<input name="star" type="radio" class="star" checked="checked"/>
<input name="star" type="radio" class="star" />
<input name="star" type="radio" class="star" />
</form>
</div>
<br />
<!-- BEGIN EDITXP FORM -->
<form id="editPL" name="editPL" method="post" action="editPL-exec.php" enctype='multipart/form-data'>
<input name='item_id' type='hidden' value='<? echo $id;?>' />
<h4>Date</h4><span class='smallgray'> (date format : YYMMDD)</span><br />
<img src='themes/<?php echo $_SESSION['prefs']['theme'];?>/img/calendar.png' title='date' alt='Date :' /><input name='date' id='datepicker' size='6' type='text' value='<?php echo $data['date'];?>' /><br />
<br /><h4>Name</h4><br />
      <textarea id='title' name='title' rows="1" cols="80"><?php if(empty($_SESSION['errors'])){
          echo stripslashes($data['title']);
      } else {
          echo stripslashes($_SESSION['new_title']);
      } ?></textarea>
<br /><br /><h4>Infos</h4>
<br />
<textarea name='body' class='mceditable' rows="15" cols="80"><?php if(empty($_SESSION['errors'])){
    echo stripslashes($data['body']);
    } else {
    echo stripslashes($_SESSION['new_body']);
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
// DATEPICKER + STAR RATINGS
$(function() {
    $( "#datepicker" ).datepicker({dateFormat: 'ymmdd'});
    $('input.star').rating();
});
tinyMCE.init({
    theme : "advanced",
    mode : "specific_textareas",
    editor_selector : "mceditable",
    content_css : "css/tinymce.css",
    theme_advanced_font_sizes: "10px,12px,13px,14px,16px,18px,20px",
    font_size_style_values : "10px,12px,13px,14px,16px,18px,20px"
});
</script>
