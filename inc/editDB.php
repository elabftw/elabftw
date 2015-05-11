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
<script src='js/tinymce/tinymce.min.js'></script>
<?php
// ID
if (isset($_GET['id']) && !empty($_GET['id']) && is_pos_int($_GET['id'])) {
    $id = $_GET['id'];
    if (!item_is_in_team($id, $_SESSION['team_id'])) {
        die(_('This section is out of your reach.'));
    }
} else {
    display_message('error', _("The id parameter is not valid!"));
    require_once 'inc/footer.php';
    exit;
}

// GET CONTENT
    $sql = "SELECT items.*,
        items_types.bgcolor
        FROM items
        LEFT JOIN items_types ON (items.type = items_types.id)
        WHERE items.id = :id";
$req = $pdo->prepare($sql);
$req->bindParam(':id', $id);
$req->execute();
$data = $req->fetch();

// Check for lock
if ($data['locked'] == 1) {
    display_message('error', _('<strong>This item is locked.</strong> You cannot edit it.'));
    require_once 'inc/footer.php';
    exit;
}

// BEGIN CONTENT
?>
<section class='box' style='border-left: 6px solid #<?php echo $data['bgcolor']; ?>'>
    <!-- TRASH -->
    <img class='align_right' src='img/big-trash.png' title='delete' alt='delete' onClick="deleteThis('<?php echo $id; ?>','item', 'database.php')" />

    <!-- ADD TAG FORM -->
    <img src='img/tags.png' class='bot5px' alt='tags' /><label for='addtaginput'><?php echo _('Tags'); ?></label>
    <div class='tags'>
        <span id='tags_div'>
        <?php
        $sql = "SELECT id, tag FROM items_tags WHERE item_id = " . $id;
        $tagreq = $pdo->prepare($sql);
        $tagreq->execute();
        // DISPLAY TAGS
        while ($tags = $tagreq->fetch()) {
            echo "<span class='tag'><a onclick='delete_tag(" . $tags['id'] . "," . $id . ")'>";
            echo stripslashes($tags['tag']) . "</a></span>";
        } //end while tags
        ?>
        </span>
        <input type="text" name="tag" id="addtaginput" placeholder="<?php echo _('Add a tag'); ?>" />
    </div>
    <!-- END ADD TAG -->

    <!-- BEGIN 2ND FORM -->
    <form method="post" action="app/editDB-exec.php" enctype='multipart/form-data'>
    <!-- STAR RATING via ajax request -->
    <div class='align_right'>
    <input id='star1' name="star" type="radio" class="star" value='1' <?php if ($data['rating'] == 1) { echo "checked=checked "; }?>/>
    <input id='star2' name="star" type="radio" class="star" value='2' <?php if ($data['rating'] == 2) { echo "checked=checked "; }?>/>
    <input id='star3' name="star" type="radio" class="star" value='3' <?php if ($data['rating'] == 3) { echo "checked=checked "; }?>/>
    <input id='star4' name="star" type="radio" class="star" value='4' <?php if ($data['rating'] == 4) { echo "checked=checked "; }?>/>
    <input id='star5' name="star" type="radio" class="star" value='5' <?php if ($data['rating'] == 5) { echo "checked=checked "; }?>/>
    </div><!-- END STAR RATING -->
    <input name='item_id' type='hidden' value='<?php echo $id; ?>' />
    <img src='img/calendar.png' class='bot5px' title='date' alt='Date :' />
    <label for='datepicker'><?php echo _('Date'); ?></label>
    <!-- TODO if firefox has support for it: type = date -->
    <input name='date' id='datepicker' size='8' type='text' value='<?php echo $data['date']; ?>' />
    <label class='block' for='title_input'><?php echo _('Title'); ?></label>
    <input id='title_input' name='title' rows="1" value='<?php if (empty($_SESSION['errors'])) {
        echo stripslashes($data['title']);
    } else {
        echo stripslashes($_SESSION['new_title']);
    } ?>' required />
        <label for='body_area' class='block'><?php echo _('Infos'); ?></label>
    <textarea id='body_area' class='mceditable' name='body' rows="15" cols="80">
        <?php echo stripslashes($data['body']); ?>
    </textarea>
    <!-- _('Submit') BUTTON -->
    <div class='center' id='saveButton'>
        <button type="submit" name="Submit" class='button'><?php echo _('Save and go back'); ?></button>
    </div>
    </form>
    <!-- end edit items form -->
<span class='align_right'>
<?php
// get the list of revisions
$sql = "SELECT COUNT(id) FROM items_revisions WHERE item_id = :item_id ORDER BY savedate DESC";
$req = $pdo->prepare($sql);
$req->execute(array(
    'item_id' => $id
));
$rev_count = $req->fetch();
$count = intval($rev_count[0]);
if ($count > 0) {
    echo $count . " " . ngettext('revision available.', 'revisions available.', $count) . " <a href='revision.php?item_id=" . $id . "'>" . _('Show history') . "</a>";
}
?>
</span>

</section>

<?php
// FILE UPLOAD
require_once 'inc/file_upload.php';
// DISPLAY FILES
require_once 'inc/display_file.php';
?>

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
$getalltags = $pdo->prepare($sql);
$getalltags->execute();
while ($tag = $getalltags->fetch()) {
    echo "'" . $tag[0] . "',";
}?>
		];
		$( "#addtaginput" ).autocomplete({
			source: availableTags
		});
	});
// DELETE TAG
function delete_tag(tag_id,item_id){
    var you_sure = confirm('<?php echo _('Delete this?'); ?>');
    if (you_sure == true) {
        $.post('app/delete.php', {
            id:tag_id,
            item_id:item_id,
            type:'itemtag'
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
        $.post('app/add.php', {
            tag: tag,
            item_id: <?php echo $id; ?>,
            type: 'itemtag'
        })
        // reload the tags list
        .success(function() {$("#tags_div").load("database.php?mode=edit&id=<?php echo $id; ?> #tags_div");
    // clear input field
    $("#addtaginput").val("");
    return false;
        })
    } // end if key is enter
}
// STAR RATINGS
function updateRating(rating) {
    // POST request
    $.post('app/star-rating.php', {
        star: rating,
        item_id: <?php echo $id; ?>
    })
    // reload the div
    .success(function () {
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
        plugins : "table textcolor searchreplace code fullscreen insertdatetime paste charmap save image link",
        toolbar1: "undo redo | bold italic underline | fontsizeselect | alignleft aligncenter alignright alignjustify | superscript subscript | bullist numlist outdent indent | forecolor backcolor | charmap | image link | save",
        removed_menuitems : "newdocument",
        // save button :
        save_onsavecallback: function() {
            $.ajax({
                type: "POST",
                url: "app/quicksave.php",
                data: {
                id : <?php echo $id; ?>,
                type : 'items',
                // we need this to get the updated content
                title : document.getElementById('title_input').value,
                date : document.getElementById('datepicker').value,
                body : tinymce.activeEditor.getContent()
                }
            }).done(showSaved());
        },
        // keyboard shortcut to insert today's date at cursor in editor
        setup : function(editor) {
            editor.addShortcut("ctrl+shift+d", "add date at cursor", function() { addDateOnCursor(); });
        },
        language : '<?php echo $_SESSION['prefs']['lang']; ?>'
    });
    // DATEPICKER
    $( "#datepicker" ).datepicker({dateFormat: 'yymmdd'});
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
    $("#title_input").focus(function(){
        $("#title_input").select();
    });
    // fix for the ' and "
    title = "<?php echo $data['title']; ?>".replace(/\&#39;/g, "'").replace(/\&#34;/g, "\"");
    document.title = title;

    // ask the user if he really wants to navigate out of the page
<?php
if (isset($_SESSION['prefs']['close_warning']) && $_SESSION['prefs']['close_warning'] === 1) {
    echo "
window.onbeforeunload = function (e) {
      e = e || window.event;
      return '"._('Do you want to navigate away from this page? Unsaved changes will be lost!') . "';
};";
}
?>
});
</script>
