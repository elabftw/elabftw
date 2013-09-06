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
<script src="js/tinymce/tinymce.min.js"></script>
<?php
// ID
if(isset($_GET['id']) && !empty($_GET['id']) && is_pos_int($_GET['id'])){
    $id = $_GET['id'];
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
<a class='align_right' href='delete_item.php?id=<?php echo $id;?>' onClick="return confirm('Delete this item ?');"><img src='themes/<?php echo $_SESSION['prefs']['theme'];?>/img/trash.png' title='delete' alt='delete' /></a>
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
<input name='item_id' type='hidden' value='<?php echo $id;?>' />
<h4>Date</h4><span class='smallgray'> (date format : YYMMDD)</span><br />
<!-- TODO if firefox has support for it: type = date -->
<img src='themes/<?php echo $_SESSION['prefs']['theme'];?>/img/calendar.png' title='date' alt='Date :' /><input name='date' id='datepicker' size='6' type='text' value='<?php echo $data['date'];?>' />
<!-- STAR RATING via ajax request -->
<div id='rating'>
<input id='star1' name="star" type="radio" class="star" value='1' <?php if ($data['rating'] == 1){ echo "checked=checked ";}?>/>
<input id='star2' name="star" type="radio" class="star" value='2' <?php if ($data['rating'] == 2){ echo "checked=checked ";}?>/>
<input id='star3' name="star" type="radio" class="star" value='3' <?php if ($data['rating'] == 3){ echo "checked=checked ";}?>/>
<input id='star4' name="star" type="radio" class="star" value='4' <?php if ($data['rating'] == 4){ echo "checked=checked ";}?>/>
<input id='star5' name="star" type="radio" class="star" value='5' <?php if ($data['rating'] == 5){ echo "checked=checked ";}?>/>
</div><!-- END STAR RATING -->
<br />
<h4>Title</h4><br />
      <textarea id='title_txtarea' name='title' rows="1" cols="80"><?php if(empty($_SESSION['errors'])){
          echo stripslashes($data['title']);
      } else {
          echo stripslashes($_SESSION['new_title']);
      } ?></textarea>
<br />
<br />
<h4>Infos</h4><br />
<textarea id='body_area' class='mceditable' name='body' rows="15" cols="80">
    <?php echo stripslashes($data['body']);?>
</textarea>
<!-- SUBMIT BUTTON -->
<div class='center' id='saveButton'>
    <input type="submit" name="Submit" class='button' value="Save and go back" />
</div>
</form>
<!-- end edit items form -->
<?php
// FILE UPLOAD
require_once('inc/file_upload.php');
// DISPLAY FILES
require_once('inc/display_file.php');
?>
</div>

</div>
</section>
<?php
// unset session variables
unset($_SESSION['errors']);
?>

<script>
// JAVASCRIPT
// TAGS AUTOCOMPLETE LIST
$(function() {
		var availableTags = [
<?php // get all user's tag for autocomplete
$sql = "SELECT DISTINCT tag FROM items_tags ORDER BY id DESC LIMIT 500";
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
// DELETE TAG
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
// ADD TAG
function addTagOnEnter(e){ // the argument here is the event (needed to detect which key is pressed)
    var keynum;
    if(e.which)
        { keynum = e.which;}
    if(keynum == 13){  // if the key that was pressed was Enter (ascii code 13)
        // get tag
        var tag = $('#addtaginput').val();
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
// STAR RATINGS
function updateRating(rating) {
    // POST request
    var jqxhr = $.post('star-rating.php', {
        star: rating,
        item_id: <?php echo $id; ?>
    })
    // reload the div
    .done(function () {
        return false;
    })
}

// READY ? GO !
$(document).ready(function() {
    // ADD TAG JS
    // listen keypress, add tag when it's enter
    $('#addtaginput').keypress(function (e) {
        addTagOnEnter(e);
    });
    // EDITOR
    tinymce.init({
        mode : "specific_textareas",
        editor_selector : "mceditable",
        content_css : "css/tinymce.css",
        plugins : "table textcolor searchreplace code fullscreen insertdatetime paste charmap save",
        toolbar1: "undo redo | bold italic underline | alignleft aligncenter alignright alignjustify | superscript subscript | bullist numlist outdent indent | forecolor backcolor | charmap | save",
        removed_menuitems : "newdocument",
        // save button :
        save_onsavecallback: function() {
            $.ajax({
                type: "POST",
                url: "quicksave.php",
                data: {
                id : <?php echo $id;?>,
                type : 'items',
                // we need this to get the updated content
                title : document.getElementById('title_txtarea').value,
                date : document.getElementById('datepicker').value,
                body : tinymce.activeEditor.getContent()
                }
            });
        },
        // keyboard shortcut to insert today's date at cursor in editor
        setup : function(editor) {
            editor.addShortcut("ctrl+shift+d", "add date at cursor", function() { addDateOnCursor(); });
        }
    });
    // DATEPICKER
    $( "#datepicker" ).datepicker({dateFormat: 'ymmdd'});
    // STARS
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
    // SELECT ALL TXT WHEN FOCUS ON TITLE INPUT
    $("#title").focus(function(){
        $("#title").select();
    });
    // fix for the ' and "
    title = "<?php echo $data['title']; ?>".replace(/\&#39;/g, "'").replace(/\&#34;/g, "\"");
    document.title = title;
});
</script>

