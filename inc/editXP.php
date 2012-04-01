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
<h2>EDIT EXPERIMENT</h2>
<!-- to edit comments of attached files -->
<script src='js/editinplace.js' type='text/javascript'></script>
<?php
// check ID is valid
if (isset($_GET['id']) && !empty($_GET['id'])) {
    $filter_options = array(
        'options' => array(
            'min_range' => 1
        ));
    $id = filter_var($_GET['id'], FILTER_VALIDATE_INT, $filter_options);
} else {
    die("The id parameter in the URL isn't a valid protocol ID.");
}

// Check id is owned by connected user
$sql = "SELECT userid FROM experiments WHERE id = ".$id;
$req = $bdd->prepare($sql);
$req->execute();
$resultat = $req->fetchColumn();
if ($resultat != $_SESSION['userid']) {
    die("You are trying to edit an experiment which is not yours.");
}

// SQL for editXP
$sql = "SELECT title, date, body, outcome, protocol FROM experiments WHERE id = ".$id;
$req = $bdd->prepare($sql);
$req->execute();
$data = $req->fetch();

// BEGIN CONTENT
?>
<section class='<?php echo $data['outcome'];?>'>
<a class='align_right' href='delete_item.php?id=<?php echo $id;?>&type=exp' onClick="return confirm('Delete this experiment ?');"><img src='themes/<?php echo $_SESSION['prefs']['theme'];?>/img/trash.png' title='delete' alt='delete' /></a>
<!-- ADD TAG FORM -->
<form id="addtag" name="addtag" method="post" action="add_tag.php">
<img src='themes/<?php echo $_SESSION['prefs']['theme'];?>/img/tags.gif' alt='' /> <h4>Tags</h4><span class='smallgray'> (click a tag to remove it)</span><br />
<span class='tags'>
<?php
$sql = "SELECT id, tag FROM experiments_tags WHERE item_id = ".$id;
$tagreq = $bdd->prepare($sql);
$tagreq->execute();
// DISPLAY TAGS
while($tags = $tagreq->fetch()){
?>
    <span class='tag'><a href='delete_tag.php?id=<?php echo $tags['id'];?>&item_id=<?php echo $id;?>&type=exp' onClick="return confirm('Delete this tag ?');">
<?php echo stripslashes($tags['tag']);}?></a> 
<input name='item_id' type='hidden' value='<? echo $id;?>' />
<input name='type' type='hidden' value='exp' />
<input id='addtaginput' name='tag' placeholder='Add one tag' />
<a href="javascript: document.forms['addtag'].submit()"><img alt='add' src='themes/<?php echo $_SESSION['prefs']['theme'];?>/img/plus.png' /></a>
</span></span>
</form><!-- END ADD TAG -->
<br />
<?php
//echo "<p>or add from tagcloud</p>";
//require_once('inc/tagcloud.php');
?>
<!-- BEGIN 2ND FORM -->
<form id="editXP" name="editXP" method="post" action="editXP-exec.php" enctype='multipart/form-data'>
<input name='item_id' type='hidden' value='<? echo $id;?>' />
<h4>Date</h4><span class='smallgray'> (date format : YYMMDD)</span><br />
<img src='themes/<?php echo $_SESSION['prefs']['theme'];?>/img/calendar.png' title='date' alt='Date :' /><input name='date' size='6' type='text' value='<?php echo $data['date'];?>' /><br />
<br /><h4>Title</h4><br />
      <textarea id='title' name='title' rows="1" cols="80"><?php if(empty($_SESSION['errors'])){
          echo stripslashes($data['title']);
      } else {
          echo stripslashes($_SESSION['new_title']);
      } ?></textarea>
<br /><br /><h4>Experiment</h4>
<? // SQL to get user's templates
$sql = "SELECT id, name FROM experiments_templates WHERE userid = ".$_SESSION['userid'];
$req = $bdd->prepare($sql);
$req->execute();
echo "<select>";
echo "<option>Choose template</option>";
echo "<option disabled='disabled'>----------------</option>";
while ($data = $req->fetch()) {
    echo "<option class='template_link' id='".$data['id']."' value='".$data['name']."'>".$data['name']."</option>";
}
echo "</select>";
?>
<script type='text/javascript'>
$(".template_link").click(function() {
    $.get("load_tpl.php", {tpl_id: $(this).attr('id')}, 
        function(data) {
            $('#body_textarea').val(data);
        });    
    return false;
});
</script>
      <textarea id='body_textarea' name='body' rows="15" cols="80"><?php if(empty($_SESSION['errors'])){
        echo stripslashes($data['body']);
    } else {
        echo stripslashes($_SESSION['new_body']);
    } ?></textarea>
      <br /><br /><h4>Outcome</h4>
<!-- outcome get selected by default -->
      <select name="outcome">
<option <?php echo ($data['outcome'] === "running") ? "selected" : "";?> value="running">Running</option>
<option <?php echo ($data['outcome'] === "success") ? "selected" : "";?> value="success">Success</option>
<option <?php echo ($data['outcome'] === "redo") ? "selected" : "";?> value="redo">Need to be redone</option>
<option <?php echo ($data['outcome'] === "fail") ? "selected" : ""; ?> value="fail">Fail</option>
</select><br /><br />
<h4>Link to protocol</h4>
<select name="protocol">
<option value='None'>-- None --</option>
<?php
// SQL to get all protocols
$sql = "SELECT id, title FROM protocols ORDER BY title";
$req = $bdd->prepare($sql);
$req->execute();
while ($protdata = $req->fetch()) {
    // we limit the title size so it's not too large
    echo "<option ";
    if ($protdata['id'] === $data['protocol']) {
        echo "selected ";
    }
    echo "value=".$protdata['id'].">".substr($protdata['title'], 0, 60)."</option>";
}
?>
</select><br /><br />
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
<!-- end editXP form -->
<?php
// Give focus to add tag field if we just added a tag
if(isset($_GET['tagadded'])){echo "
<script type='text/javascript'>document.getElementById('addtaginput').focus();</script>";}
// KEYBOARD SHORTCUTS
echo "<script type='text/javascript'>
key('".$_SESSION['prefs']['shortcuts']['create']."', function(){location.href = 'experiments.php?mode=create'});
key('".$_SESSION['prefs']['shortcuts']['submit']."', function(){document.forms['editXP'].submit()});
</script>";
?>
