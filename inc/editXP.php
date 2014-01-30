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
    $message = "<strong>Cannot edit:</strong> the id parameter is not valid !";
    display_message('error', $message);
    require_once('inc/footer.php');
    exit();
}

// SQL for editXP
$sql = "SELECT * FROM experiments WHERE id = ".$id;
$req = $bdd->prepare($sql);
$req->execute();
$data = $req->fetch();

// Check id is owned by connected user
if ($data['userid'] != $_SESSION['userid']) {
    $message = "<strong>Cannot edit:</strong> this experiment is not yours !";
    display_message('error', $message);
    require_once('inc/footer.php');
    exit();
}

// Check for lock
if ($data['locked'] == 1) {
    $message = "<strong>This item is locked.</strong> You cannot edit it.";
    display_message('error', $message);
    require_once('inc/footer.php');
    exit();
}

// BEGIN CONTENT
?>
<section id='view_xp_item' class='item <?php echo $data['status'];?>'>
<img class='align_right' src='themes/<?php echo $_SESSION['prefs']['theme'];?>/img/trash.png' title='delete' alt='delete' onClick="deleteThis('<?php echo $id;?>','exp')" />
<!-- ADD TAG FORM -->
<img src='themes/<?php echo $_SESSION['prefs']['theme'];?>/img/tags.png' alt='tags' /> <h4>Tags</h4><span class='smallgray'> (click a tag to remove it)</span><br />
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

<h4>Date</h4><span class='smallgray'> (date format : YYYYMMDD)</span><br />
<!-- TODO if firefox has support for it: type = date -->
<img src='themes/<?php echo $_SESSION['prefs']['theme'];?>/img/calendar.png' title='date' alt='Date :' /> <input name='date' id='datepicker' size='8' type='text' value='<?php echo $data['date'];?>' />

<span class='align_right'>
<h4>Status</h4>
<!-- Status get selected by default -->
<?php
$status = $data['status'];
?>
    <select id="status_form" name="status" onchange="update_status(this.value)">
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
    <input type="submit" name="Submit" class='button' value="Save and go back" />
</div>
</form><!-- end editXP form -->

<?php
// FILE UPLOAD
require_once('inc/file_upload.php');
// DISPLAY FILES
require_once('inc/display_file.php');
?>

<hr class='flourishes'>

<img src='themes/<?php echo $_SESSION['prefs']['theme'];?>/img/link.png'> <h4 style='display:inline'>Linked items</h4>
<div id='links_div'>
<?php
// DISPLAY LINKED ITEMS
$sql = "SELECT * FROM experiments_links LEFT JOIN items ON (experiments_links.link_id = items.id) 
    WHERE experiments_links.item_id = :id";
$req = $bdd->prepare($sql);
$req->execute(array(
    'id' => $id
));
// Check there is at least one link to display
if ($req->rowcount() > 0) {
    echo "<ul>";
    while ($links = $req->fetch()) {
        $type = get_item_info_from_id($links['type'], 'name');
        echo "<li>- [".$type."] - <a href='database.php?mode=view&id=".$links['id']."'>".stripslashes($links['title'])."</a>";
        echo "<a onclick='delete_link(".$links[0].", ".$id.")'>
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

<br /><br />
<img src='themes/<?php echo $_SESSION['prefs']['theme'];?>/img/visibility.png'> <h4 style='display:inline'>Visibility</h4>
<!-- visibility get selected by default -->
<?php
$visibility = $data['visibility'];
?>
    <select id="visibility_form" name="visibility" onchange="update_visibility(this.value)">
<option id='option_team' value="team">Only the team</option>
<option id='option_user' value="user">Only me</option>
</select>
<span id='visibility_msg_div'>Updated !</span>

</section>

<script>
// JAVASCRIPT
function deleteThis(id, type) {
    var you_sure = confirm('Delete this ?');
    if (you_sure == true) {
        $.post('delete.php', {
            id:id,
            type:type
        })
        // on success we go to experiments page
        .success(function() {
            document.cookie = 'info=Experiment deleted successfully !';
            window.location = "experiments.php";
        });
    } else {
        return false;
    }
}
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
        $.post('delete.php', {
            id: tag_id,
            item_id: item_id,
            type: 'exptag'
        }).success(function () {
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
        var tag = $('#addtaginput').val();
        // POST request
        $.post('add.php', {
            tag: tag,
            item_id: <?php echo $id; ?>,
            type: 'exptag'
        })
        // reload the tags list
        .success(function () {
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
// TODO put in deleteThis()
function delete_link(id, item_id) {
    var you_sure = confirm('Delete this link ?');
    if (you_sure == true) {
        $.post('delete.php', {
            type: 'link',
            id: id,
            item_id : item_id
        }).success(function () {
            $("#links_div").load("experiments.php?mode=edit&id=" + item_id + " #links_div");
        })
    }
    return false;
}

// ADD LINK
function addLinkOnEnter(e) { // the argument here is the event (needed to detect which key is pressed)
    var keynum;
    if (e.which) {
        keynum = e.which;
    }
    if (keynum == 13) { // if the key that was pressed was Enter (ascii code 13)
        // get link
        var link_id = decodeURIComponent($('#linkinput').val());
        // fix for user pressing enter with no input
        if (link_id.length > 0) {
            // parseint will get the id, and not the rest (in case there is number in title)
            link_id = parseInt(link_id, 10);
            if (isNaN(link_id) != true) {
                // POST request
                $.post('add.php', {
                    type: 'link',
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

// This function is activated with the select element and send a post request to quicksave.php
function update_status(status) {
            var jqxhr = $.ajax({
                type: "POST",
                url: "quicksave.php",
                data: {
                id : <?php echo $id;?>,
                status : status,
                }
                // change the color of the item border
            }).done(function() { 
                // we first remove any status class
                $("#view_xp_item").removeClass('running success redo fail');
                // and we add our new status class
                $("#view_xp_item").toggleClass(status);
            });
}

// This function is activated with the select element and send a post request to quicksave.php
function update_visibility(visibility) {
            var jqxhr = $.ajax({
                type: "POST",
                url: "quicksave.php",
                data: {
                id : <?php echo $id;?>,
                visibility : visibility,
                }
            }).done(function() {
                // once it's update we show a message for some time before making it disappear
                $("#visibility_msg_div").show(0, function() {
                    setTimeout(
                        function() {
                            $("#visibility_msg_div").hide(500);
                        }, 1500)

                });
            });
}


// READY ? GO !!
$(document).ready(function() {
    // hide the little 'Updated !' message
    $('#visibility_msg_div').hide();

    // javascript to put the selected on status option, because with php, browser cache the value of previous edited XP
    var status = "<?php echo $status;?>";
    switch(status) {
    case 'running' :
        $("#option_running").prop('selected', true);
        break;
    case 'success' :
        $("#option_success").prop('selected', true);
        break;
    case 'redo' :
        $("#option_redo").prop('selected', true);
        break;
    case 'fail' :
        $("#option_fail").prop('selected', true);
        break;
    default :
        $("#option_running").prop('selected', true);
    }
    // javascript to put the selected on visibility option, because with php, browser cache the value of previous edited XP
    var visibility = "<?php echo $visibility;?>";
    switch(visibility) {
    case 'team' :
        $("#option_team").prop('selected', true);
        break;
    case 'user' :
        $("#option_user").prop('selected', true);
        break;
    default :
        $("#option_team").prop('selected', true);
    }

    // fix for the ' and "
    title = "<?php echo $data['title']; ?>".replace(/\&#39;/g, "'").replace(/\&#34;/g, "\"");
    document.title = title;
    // DATEPICKER
    $( "#datepicker" ).datepicker({dateFormat: 'yymmdd'});
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
        toolbar1: "undo redo | bold italic underline | fontsizeselect | alignleft aligncenter alignright alignjustify | superscript subscript | bullist numlist outdent indent | forecolor backcolor | charmap | save",
        removed_menuitems : "newdocument",
        // save button :
        save_onsavecallback: function() {
            $.ajax({
                type: "POST",
                url: "quicksave.php",
                data: {
                id : <?php echo $id;?>,
                type : 'experiments',
                // we need this to get the updated content
                title : document.getElementById('title_txtarea').value,
                date : document.getElementById('datepicker').value,
                body : tinymce.activeEditor.getContent()
                }
            }).done(showSaved());
        },
        // keyboard shortcut to insert today's date at cursor in editor
        setup : function(editor) {
            editor.addShortcut("ctrl+shift+d", "add date at cursor", function() { addDateOnCursor(); });
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
    /*
     * commented out because it should only ask when the user didn't save
     *

    // ask the user if he really wants to navigate out of the page
    window.onbeforeunload = function (e) {
          e = e || window.event;
          return 'Do you want to navigate away from this page ? Unsaved changes will be lost !';
    };
    */
});
</script>

