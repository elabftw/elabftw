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
// inc/editPR.php
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
<a class='align_right' href='delete_item.php?id=<?php echo $id;?>&type=prot' onClick="return confirm('Delete this protocol ?');"><img src='themes/<?php echo $_SESSION['prefs']['theme'];?>/img/trash.png' title='delete' alt='delete' /></a>
<!-- ADD TAG FORM -->
<img src='themes/<?php echo $_SESSION['prefs']['theme'];?>/img/tags.gif' alt='' /> <h4>Tags</h4><span class='smallgray'> (click a tag to remove it)</span><br />
<div class='tags'>
<span id='tags_div'>
<?php
$sql = "SELECT id, tag FROM protocols_tags WHERE item_id = ".$id;
$tagreq = $bdd->prepare($sql);
$tagreq->execute();
// DISPLAY TAGS
while($tags = $tagreq->fetch()){
echo "<span class='tag'><a onclick='delete_tag(".$tags['id'].",".$id.")'>";
echo stripslashes($tags['tag']);?>
</a></span>
<?php } //end while tags ?>
</span>
<input type="text" name="tag" id="addtaginput" placeholder="Add a tag" />
</div>
<!-- END ADD TAG -->

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
?>
<script type='text/javascript'>
// JAVASCRIPT
<?php
// KEYBOARD SHORTCUTS
echo "key('".$_SESSION['prefs']['shortcuts']['create']."', function(){location.href = 'create_item.php?type=prot'});";
echo "key('".$_SESSION['prefs']['shortcuts']['submit']."', function(){document.forms['editPR'].submit()});";
?>
// TAGS AUTOCOMPLETE
$(function() {
		var availableTags = [
<?php // get all user's tag for autocomplete
$sql = "SELECT DISTINCT tag FROM protocols_tags LIMIT 50";
$getalltags = $bdd->prepare($sql);
$getalltags->execute();
while ($tag = $getalltags->fetch()){
    echo "'".$tag[0]."',";
}?>
		];
		$( "#addtaginput" ).autocomplete({
			source: availableTags
		});
	});
// DELETE TAG JS
function delete_tag(tag_id,item_id){
    var you_sure = confirm('Delete this tag ?');
    if (you_sure == true) {
        var jqxhr = $.post('delete_tag.php', {
        id:tag_id,
        item_id:item_id,
        type:'prot'
        })
        .success(function() {$("#tags_div").load("protocols.php?mode=edit&id="+item_id+" #tags_div");})
    }
    return false;
}
// ADD TAG JS
// listen keypress, add tag when it's enter
jQuery(document).keypress(function(e){
    addTagOnEnter(e);
});
function addTagOnEnter(e){ // the argument here is the event (needed to detect which key is pressed)
    var keynum;
    if(e.which)
        { keynum = e.which;}
    if(keynum == 13){  // if the key that was pressed was Enter (ascii code 13)
        // get tag
    var tag = $('#addtaginput').attr('value');
    // POST request
        var jqxhr = $.post('add_tag.php', {
            tag:tag,
            item_id:<?php echo $id;?>,
            type:'prot'
        })
        // reload the tags list
        .success(function() {$("#tags_div").load("protocols.php?mode=edit&id=<?php echo $id;?> #tags_div");
    // clear input field
    $("#addtaginput").val("");
    return false;
        })
    } // end if key is enter
}
</script>
