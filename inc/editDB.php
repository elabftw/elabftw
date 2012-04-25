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
// inc/editDB.php
?>
<script src="js/tiny_mce/tiny_mce.js"></script>
<?php
// ID
if (isset($_GET['id']) && !empty($_GET['id'])) {
    $filter_options = array(
        'options' => array(
            'min_range' => 1
        ));
    $id = filter_var($_GET['id'], FILTER_VALIDATE_INT, $filter_options);
} else {
    die("The id parameter in the URL isn't a valid item ID.");
}

// GET CONTENT
$sql = "SELECT * FROM items WHERE id = ".$id;
$req = $bdd->prepare($sql);
$req->execute();
$data = $req->fetch();

// BEGIN CONTENT
?>
<section class='item'>
<a class='align_right' href='delete_item.php?id=<?php echo $id;?>&type=item' onClick="return confirm('Delete this item ?');"><img src='themes/<?php echo $_SESSION['prefs']['theme'];?>/img/trash.png' title='delete' alt='delete' /></a>
<!-- ADD TAG FORM -->
<img src='themes/<?php echo $_SESSION['prefs']['theme'];?>/img/tags.gif' alt='' /> <h4>Tags</h4><span class='smallgray'> (click a tag to remove it)</span><br />
<div class='tags'>
<span id='tags_div'>
<?php
$sql = "SELECT id, tag FROM items_tags WHERE item_id = ".$id;
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
<form method="post" action="editDB-exec.php" enctype='multipart/form-data'>
<input name='item_id' type='hidden' value='<? echo $id;?>' />
<h4>Date</h4><span class='smallgray'> (date format : YYMMDD)</span><br />
<img src='themes/<?php echo $_SESSION['prefs']['theme'];?>/img/calendar.png' title='date' alt='Date :' /><input name='date' id='datepicker' size='6' type='text' value='<?php echo $data['date'];?>' /><br />
<h4>Title</h4><br />
      <textarea id='title' name='title' rows="1" cols="80"><?php if(empty($_SESSION['errors'])){
          echo stripslashes($data['title']);
      } else {
          echo stripslashes($_SESSION['new_title']);
      } ?></textarea>
<br />
<!-- STAR RATING via ajax request -->
<div id='rating'>
<input id='star1' name="star" type="radio" class="star" value='1' <?php if ($data['rating'] == 1){ echo "checked=checked ";}?>/>
<input id='star2' name="star" type="radio" class="star" value='2' <?php if ($data['rating'] == 2){ echo "checked=checked ";}?>/>
<input id='star3' name="star" type="radio" class="star" value='3' <?php if ($data['rating'] == 3){ echo "checked=checked ";}?>/>
<input id='star4' name="star" type="radio" class="star" value='4' <?php if ($data['rating'] == 4){ echo "checked=checked ";}?>/>
<input id='star5' name="star" type="radio" class="star" value='5' <?php if ($data['rating'] == 5){ echo "checked=checked ";}?>/>
</div><!-- END STAR RATING -->
<br />
<h4>Infos</h4><br />
      <textarea class='mceditable' name='body' rows="15" cols="80"><?php if(empty($_SESSION['errors'])){
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
<!-- end edit items form -->
<?php
// unset session variables
unset($_SESSION['errors']);
?>
<script>
// JAVASCRIPT
<?php
// KEYBOARD SHORTCUTS
//echo "key('".$_SESSION['prefs']['shortcuts']['create']."', function(){location.href = 'create_item.php?type=prot'});";
//echo "key('".$_SESSION['prefs']['shortcuts']['submit']."', function(){document.forms['editPR'].submit()});";
?>
// TAGS AUTOCOMPLETE
$(function() {
		var availableTags = [
<?php // get all user's tag for autocomplete
$sql = "SELECT DISTINCT tag FROM items_tags LIMIT 50";
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
        type:'item'
        })
        .success(function() {$("#tags_div").load("database.php?mode=edit&id="+item_id+" #tags_div");})
    }
    return false;
}
// ADD TAG JS
// listen keypress, add tag when it's enter
jQuery('#addtaginput').keypress(function (e) {
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
            type:'item'
        })
        // reload the tags list
        .success(function() {$("#tags_div").load("database.php?mode=edit&id=<?php echo $id;?> #tags_div");
    // clear input field
    $("#addtaginput").val("");
    return false;
        })
    } // end if key is enter
}
// DATEPICKER + STAR RATINGS
$(function() {
    $( "#datepicker" ).datepicker({dateFormat: 'ymmdd'});
    $('input.star').rating();
    $('#star1').click(function() {
        updateRating(1);
    });
    $('#star2').click(function() {
        updateRating(2);
    });
    $('#star3').click(function() {
        updateRating(3);
    });
    $('#star4').click(function() {
        updateRating(4);
    });
    $('#star5').click(function() {
        updateRating(5);
    });
});
function updateRating(rating) {
        // POST request
        var jqxhr = $.post('star-rating.php', {
            star: rating,
            item_id: <?php echo $id; ?>
        })
        // reload the div
        .done(function () {
            //$("#rating").load("plasmids.php?mode=edit&id=<?php echo $id;?> #rating");
            return false;
        })
}
tinyMCE.init({
    theme : "advanced",
    mode : "specific_textareas",
    editor_selector : "mceditable",
    content_css : "css/tinymce.css",
    theme_advanced_font_sizes: "10px,12px,13px,14px,16px,18px,20px",
    font_size_style_values : "10px,12px,13px,14px,16px,18px,20px"
});
</script>
