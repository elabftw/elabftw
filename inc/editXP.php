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
<script src="js/tinymce/tinymce.min.js"></script>
<?php
// ID
if(isset($_GET['id']) && !empty($_GET['id']) && is_pos_int($_GET['id'])){
    $id = $_GET['id'];
} else {
    die("The id parameter in the URL isn't a valid item ID.");
}

// SQL for editXP
$sql = "SELECT * FROM experiments WHERE id = ".$id;
$req = $bdd->prepare($sql);
$req->execute();
$data = $req->fetch();

// Check id is owned by connected user
if ($data['userid'] != $_SESSION['userid']) {
    echo ("<ul class='errors'>You are trying to edit an experiment which is not yours.</ul>");
    require_once('inc/footer.php');
    exit();
}

// Check for lock
if ($data['locked'] == 1) {
    die("Item is locked. Can't edit.");
}

// BEGIN CONTENT
?>
<section class='item <?php echo $data['status'];?>'>
<a class='align_right' href='delete_item.php?id=<?php echo $id;?>&type=exp' onClick="return confirm('Delete this experiment ?');"><img src='themes/<?php echo $_SESSION['prefs']['theme'];?>/img/trash.png' title='delete' alt='delete' /></a>
<!-- ADD TAG FORM -->
<img src='themes/<?php echo $_SESSION['prefs']['theme'];?>/img/tags.gif' alt='' /> <h4>Tags</h4><span class='smallgray'> (click a tag to remove it)</span><br />
<div class='tags'>
<span id='tags_div'>
<?php
$sql = "SELECT id, tag FROM experiments_tags WHERE item_id = ".$id;
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
<!-- BEGIN EDITXP FORM -->
<form id="editXP" name="editXP" method="post" action="editXP-exec.php" enctype='multipart/form-data'>
<input name='item_id' type='hidden' value='<?php echo $id;?>' />

<h4>Date</h4><span class='smallgray'> (date format : YYMMDD)</span><br />
<!-- TODO if firefox has support for it: type = date -->
<img src='themes/<?php echo $_SESSION['prefs']['theme'];?>/img/calendar.png' title='date' alt='Date :' /><input name='date' id='datepicker' size='6' type='text' value='<?php echo $data['date'];?>' />

<span class='align_right'>
<h4>Status</h4>
<!-- Status get selected by default -->
<?php
$status = $data['status'];
?>
      <select id="status_form" name="status">
<option id='option_running' value="running">Running</option>
<option id='option_success' value="success">Success</option>
<option id='option_redo' value="redo">Need to be redone</option>
<option id='option_fail' value="fail">Fail</option>
</select>
</span>
<br />
<br />
<h4>Title</h4><br />
      <textarea id='title_txtarea' name='title' rows="1" cols="80"><?php if(empty($_SESSION['errors'])){
          echo stripslashes($data['title']);
      } else {
          echo stripslashes($_SESSION['new_title']);
      } ?></textarea>

<br />
<h4>Experiment</h4>
<br />
<textarea id='body_area' class='mceditable' name='body' rows="15" cols="80">
    <?php echo stripslashes($data['body']);?>
</textarea>

<!-- SUBMIT BUTTON -->
<div class='center' id='saveButton'>
    <button type='submit'>
        <img src='themes/<?php echo $_SESSION['prefs']['theme'];?>/img/submit.png' name='Submit' value='Submit' />
    <p>SAVE</p>
    </button>
</div>
</form><!-- end editXP form -->

<?php
// FILE UPLOAD
require_once('inc/file_upload.php');
// DISPLAY FILES
require_once('inc/display_file.php');
?>

<hr class='flourishes'>

<h4>Linked items</h4>
<div id='links_div'>
<?php
// DISPLAY LINKED ITEMS
$sql = "SELECT link_id, id FROM experiments_links WHERE item_id = ".$id;
$req = $bdd->prepare($sql);
$req->execute();
// Check there is at least one link to display
if ($req->rowcount() != 0) {
    echo "<ul>";
    while ($links = $req->fetch()) {
        // SQL to get title
        $linksql = "SELECT id, title, type FROM items WHERE id = :link_id";
        $linkreq = $bdd->prepare($linksql);
        $linkreq->execute(array(
            'link_id' => $links['link_id']
        ));
        $linkdata = $linkreq->fetch();
        $name = get_item_info_from_id($linkdata['type'], 'name');
        echo "<li>- [".$name."] - <a href='database.php?mode=view&id=".$linkdata['id']."'>".stripslashes($linkdata['title'])."</a>";
        echo "<a onclick='delete_link(".$links['id'].", ".$id.")'>
        <img src='themes/".$_SESSION['prefs']['theme']."/img/trash.png' title='delete' alt='delete' /></a></li>";
    } // end while
    echo "</ul>";
} else { // end if link exist
    echo "<br />";
}
?>
</div>
<p class='inline'>Add a link</p>
<input id='linkinput' size='60' type="text" name="link" placeholder="from the database" />

</section>

<script>
// JAVASCRIPT
<?php
// KEYBOARD SHORTCUTS
echo "key('".$_SESSION['prefs']['shortcuts']['create']."', function(){location.href = 'create_item.php?type=exp'});";
echo "key('".$_SESSION['prefs']['shortcuts']['submit']."', function(){document.forms['editXP'].submit()});";
?>
// TAGS AUTOCOMPLETE
$(function() {
		var availableTags = [
<?php // get all user's tag for autocomplete
$sql = "SELECT DISTINCT tag FROM experiments_tags WHERE userid = :userid ORDER BY id DESC LIMIT 500";
$getalltags = $bdd->prepare($sql);
$getalltags->execute(array(
    'userid' => $_SESSION['userid']
));
while ($tag = $getalltags->fetch()){
    echo "'".$tag[0]."',";
}?>
		];
		$( "#addtaginput" ).autocomplete({
			source: availableTags
		});
	});
// DELETE TAG
function delete_tag(tag_id, item_id) {
    var you_sure = confirm('Delete this tag ?');
    if (you_sure == true) {
        var jqxhr = $.post('delete_tag.php', {
            id: tag_id,
            item_id: item_id,
            type: 'exp'
        }).done(function () {
            $("#tags_div").load("experiments.php?mode=edit&id=" + item_id + " #tags_div");
        })
    }
    return false;
}

// ADD TAG
function addTagOnEnter(e) { // the argument here is the event (needed to detect which key is pressed)
    var keynum;
    if (e.which) {
        keynum = e.which;
    }
    if (keynum == 13) { // if the key that was pressed was Enter (ascii code 13)
        // get tag
        var tag = $('#addtaginput').attr('value');
        // POST request
        var jqxhr = $.post('add_tag.php', {
            tag: tag,
            item_id: <?php echo $id; ?> , type: 'exp'
        })
        // reload the tags list
        .done(function () {
            $("#tags_div").load("experiments.php?mode=edit&id=<?php echo $id;?> #tags_div");
            // clear input field
            $("#addtaginput").val("");
            return false;
        })
    } // end if key is enter
}
// LINKS AUTOCOMPLETE
$(function() {
		var availableLinks = [
<?php // get all links for autocomplete
$sql = "SELECT title, id, type FROM items";
$getalllinks = $bdd->prepare($sql);
$getalllinks->execute();
while ($link = $getalllinks->fetch()){
    // html_entity_decode is needed to convert the quotes
    // str_replace to remove ' because it messes everything up
    $name = get_item_info_from_id($link['type'], 'name');
    echo "'".$link['id']." - ".$name." - ".str_replace("'", "", html_entity_decode(substr($link[0], 0, 60), ENT_QUOTES))."',";
}?>
		];
		$( "#linkinput" ).autocomplete({
			source: availableLinks
		});
	});
// DELETE LINK
function delete_link(id, item_id) {
    var you_sure = confirm('Delete this link ?');
    if (you_sure == true) {
        var jqxhr = $.post('delete_link.php', {
            id: id,
            item_id : item_id
        }).done(function () {
            $("#links_div").load("experiments.php?mode=edit&id=" + item_id + " #links_div");
        })
    }
    return false;
}

function addLinkOnEnter(e) { // the argument here is the event (needed to detect which key is pressed)
    var keynum;
    if (e.which) {
        keynum = e.which;
    }
    if (keynum == 13) { // if the key that was pressed was Enter (ascii code 13)
        // get link
        var link_id = decodeURIComponent($('#linkinput').attr('value'));
        // fix for user pressing enter with no input
        if (link_id.length > 0) {
            // parseint will get the id, and not the rest (in case there is number in title)
            link_id = parseInt(link_id, 10);
            console.log(isNaN(link_id));
            if (isNaN(link_id) != true) {
                // POST request
                var jqxhr = $.post('add_link.php', {
                    link_id: link_id,
                    item_id: <?php echo $id; ?>
                })
                // reload the link list
                .done(function () {
                    $("#links_div").load("experiments.php?mode=edit&id=<?php echo $id;?> #links_div");
                    // clear input field
                    $("#linkinput").val("");
                    return false;
                })
            } // end if input is bad
        } // end if input < 0
    } // end if key is enter
}

// READY ? GO !!
$(document).ready(function() {
    // javascript to put the selected on status option, because with php, browser cache the value of previous edited XP
    var status = "<?php echo $status;?>";
    switch(status) {
    case 'running' :
        $("#option_running").attr('selected', true);
        break;
    case 'success' :
        $("#option_success").attr('selected', true);
        break;
    case 'redo' :
        $("#option_redo").attr('selected', true);
        break;
    case 'fail' :
        $("#option_fail").attr('selected', true);
        break;
    default :
        $("#option_running").attr('selected', true);
    }

    // fix for the ' and "
    title = "<?php echo $data['title']; ?>".replace(/\&#39;/g, "'").replace(/\&#34;/g, "\"");
    document.title = title;
    // DATEPICKER
    $( "#datepicker" ).datepicker({dateFormat: 'ymmdd'});
    // SELECT ALL TXT WHEN FOCUS ON TITLE INPUT
    $("#title").focus(function(){
        $("#title").select();
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
                url: "editXP-autosave.php",
                data: {
                id : <?php echo $id;?>,
                // we need this to get the updated content
                body : tinymce.activeEditor.getContent()
                }
            });
        }
    });
    // ADD TAG JS
    // listen keypress, add tag when it's enter
    jQuery('#addtaginput').keypress(function (e) {
        addTagOnEnter(e);
    });
    // ADD LINK JS
    // listen keypress, add link when it's enter
    jQuery('#linkinput').keypress(function (e) {
        addLinkOnEnter(e);
    });
});
</script>
