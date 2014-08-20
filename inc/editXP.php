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
// formkey stuff
//require_once('lib/classes/formkey.class.php');
//$formKey = new formKey();
?>
<script src="js/tinymce/tinymce.min.js"></script>
<?php
// ID
if (isset($_GET['id']) && !empty($_GET['id']) && is_pos_int($_GET['id'])) {
    $id = $_GET['id'];
} else {
    $message = "<strong>Cannot edit:</strong> the id parameter is not valid !";
    display_message('error', $message);
    require_once 'inc/footer.php';
    exit();
}

// SQL for editXP
$sql = "SELECT experiments.*, status.color FROM experiments LEFT JOIN status ON experiments.status = status.id
    WHERE experiments.id = :id ";
$req = $pdo->prepare($sql);
$req->bindParam(':id', $id, PDO::PARAM_INT);
$req->execute();
$experiment = $req->fetch();

// Check id is owned by connected user
if ($experiment['userid'] != $_SESSION['userid']) {
    $message = "<strong>Cannot edit:</strong> this experiment is not yours !";
    display_message('error', $message);
    require_once 'inc/footer.php';
    exit();
}

// Check for lock
if ($experiment['locked'] == 1) {
    $message = "<strong>This item is locked.</strong> You cannot edit it.";
    display_message('error', $message);
    require_once 'inc/footer.php';
    exit();
}

// BEGIN CONTENT
?>
<span class='backdiv'><a href='experiments.php?mode=show'><img src='img/arrow-left-blue.png' alt='' /> back to experiments listing</a></span>
<section class='padding item' style='border-left: 6px solid #<?php echo $experiment['color'];?>'>
<img class='align_right' src='img/big-trash.png' title='delete' alt='delete' onClick="deleteThis('<?php echo $id;?>','exp', 'experiments.php')" />
<!-- ADD TAG FORM -->
<img src='img/tags.png' alt='tags' /> <h4>Tags</h4><span class='smallgray'> (click a tag to remove it)</span><br />
<div class='tags'>
<span id='tags_div'>
<?php
$sql = "SELECT id, tag FROM experiments_tags WHERE item_id = ".$id;
$tagreq = $pdo->prepare($sql);
$tagreq->execute();
// DISPLAY TAGS
while ($tags = $tagreq->fetch()) {
    echo "<span class='tag'><a onclick='delete_tag(".$tags['id'].",".$id.")'>";
    echo stripslashes($tags['tag'])."</a></span>";
} //end while tags
?>
</span>
<input type="text" name="tag" id="addtaginput" placeholder="Add a tag" />
</div>
<!-- END ADD TAG -->
<!-- BEGIN EDITXP FORM -->
<form id="editXP" name="editXP" method="post" action="editXP-exec.php" enctype='multipart/form-data'>
<!-- form key -->
<?php // $formKey->output_formkey(); ?>
<input name='item_id' type='hidden' value='<?php echo $id;?>' />

<div class='three-columns'>
    <div class='column-left'>
        <img src='img/calendar.png' title='date' alt='calendar' /> <h4>Date</h4><br>
        <!-- TODO if firefox has support for it: type = date -->
        <input name='date' id='datepicker' size='8' type='text' value='<?php echo $experiment['date'];?>' />
    </div>

    <div class='column-right'>
        <img src='img/eye.png' alt='visibility' />
        <h4>Visibility</h4><br>
        <!-- visibility get selected by default -->
        <?php
        $visibility = $experiment['visibility'];
        ?>
        <select id="visibility_form" name="visibility" onchange="update_visibility(this.value)">
            <option id='option_team' value="team">Only the team</option>
            <option id='option_user' value="user">Only me</option>
        </select>
        <span id='visibility_msg_div'>Updated !</span>
    </div>

    <div class='column-center'>
        <img src='img/status.png' alt='status' /> <h4>Status</h4><br>
        <script>
        // this array is used by updateStatus() to get the color of new status
        var status_arr = Array();
        </script>

        <?php
        // put all available status in array
        $status_arr = array();
        // SQL to get all the status of the team
        $sql = 'SELECT id, name, color FROM status WHERE team = :team';
        $req = $pdo->prepare($sql);
        $req->execute(array(
            'team' => $_SESSION['team_id']
        ));

        while ($status = $req->fetch()) {
            
            $status_arr[$status['id']] = $status['name'];
            // get also a JS array for update_status() that needs the color to set the border immediately
            echo "<script>
                status_arr['".$status['id']."'] =  '".$status['color']."';
                </script>";
        }
        ?>
        <select name="status" onchange="updateStatus(this.value)">
        <?php
        // now display all possible values of status in select menu
        foreach ($status_arr as $key => $value) {
            echo "<option ";
            if ($experiment['status'] == $key) {
                echo "selected ";
            }
            echo "value='$key'>$value</option>";
        }
        ?>
        </select>
    </div>

</div>

<br />
<br />
<h4>Title</h4><br />
<input id='title_input' name='title' rows="1" value="
<?php
if (empty($_SESSION['errors'])) {
    echo stripslashes($experiment['title']);
} else {
    echo stripslashes($_SESSION['new_title']);
}
?>
" required />

<br />
<h4>Experiment</h4>
<br />
<textarea id='body_area' class='mceditable' name='body' rows="15" cols="80">
    <?php echo stripslashes($experiment['body']);?>
</textarea>

<!-- SUBMIT BUTTON -->
<div id='saveButton'>
    <button type="submit" name="Submit" class='button'>SAVE & GO BACK</button>
</div>
</form><!-- end editXP form -->

<?php
// FILE UPLOAD
require_once 'inc/file_upload.php';
// DISPLAY FILES
require_once 'inc/display_file.php';
?>

<br><br>
<section>
<img src='img/link.png'> <h4 style='display:inline'>Linked items</h4>
<div id='links_div'>
<?php
// DISPLAY LINKED ITEMS
$sql = "SELECT items.id AS itemid,
    experiments_links.id AS linkid,
    experiments_links.*,
    items.*,
    items_types.*
    FROM experiments_links
    LEFT JOIN items ON (experiments_links.link_id = items.id)
    LEFT JOIN items_types ON (items.type = items_types.id)
    WHERE experiments_links.item_id = :id";
$req = $pdo->prepare($sql);
$req->bindParam(':id', $id, PDO::PARAM_INT);
$req->execute();
// Check there is at least one link to display
if ($req->rowcount() > 0) {
    echo "<ul>";
    while ($links = $req->fetch()) {
        echo "<li>- [".$links['name']."] - <a href='database.php?mode=view&id=".$links['itemid']."'>".
            stripslashes($links['title'])."</a>";
        echo "<a onclick='delete_link(".$links['linkid'].", ".$id.")'>
        <img src='img/small-trash.png' title='delete' alt='delete' /></a></li>";
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

<br /><br />

<span class='align_right'>
<?php
// get the list of revisions
$sql = "SELECT COUNT(id) FROM experiments_revisions WHERE exp_id = :exp_id AND userid = :userid ORDER BY savedate DESC";
$req = $pdo->prepare($sql);
$req->execute(array(
    'exp_id' => $id,
    'userid' => $_SESSION['userid']
));
$rev_count = $req->fetch();
$count = intval($rev_count[0]);
if ($count > 0) {
    if ($count === 1) {
        $s = '';
    } else {
        $s = 's';
    }
    echo $count." revision".$s." available. <a href='revision.php?exp_id=".$id."'>Show history</a>";
}
?>

</section>

<script>
// JAVASCRIPT
// TAGS AUTOCOMPLETE
$(function() {
		var availableTags = [
<?php // get all user's tag for autocomplete
$sql = "SELECT DISTINCT tag FROM experiments_tags WHERE userid = :userid ORDER BY id DESC LIMIT 500";
$getalltags = $pdo->prepare($sql);
$getalltags->execute(array(
    'userid' => $_SESSION['userid']
));
while ($tag = $getalltags->fetch()) {
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
$sql = "SELECT items_types.name,
items.id AS itemid,
items.team AS itemteam,
items.* FROM items
LEFT JOIN items_types
ON items.type = items_types.id";
$getalllinks = $pdo->prepare($sql);
$res = $getalllinks->execute(array(
        'team' => $_SESSION['team_id']
    ));


while ($link = $getalllinks->fetch()) {
    // we show the item only if it is from the team
    if ($link['itemteam'] == $_SESSION['team_id']) {
        // html_entity_decode is needed to convert the quotes
        // str_replace to remove ' because it messes everything up
        $name = $link['name'];
        echo "'".$link['itemid']." - ".$name." - ".str_replace("'", "", html_entity_decode(substr($link['title'], 0, 60), ENT_QUOTES))."',";
    }
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
function updateStatus(status) {
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
                $("#view_xp_item").css('border', null);
                // and we add our new border color
                // first : get what is the color of the new status
                var css = '1px solid #' + status_arr[status];
                $("#view_xp_item").css('border', css);
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
    // KEYBOARD SHORTCUTS
    key('<?php echo $_SESSION['prefs']['shortcuts']['create'];?>', function(){location.href = 'create_item.php?type=exp'});
    key('<?php echo $_SESSION['prefs']['shortcuts']['submit'];?>', function(){document.forms['editXP'].submit()});

    // hide the little 'Updated !' message
    $('#visibility_msg_div').hide();

    // fix for the ' and "
    title = "<?php echo $experiment['title']; ?>".replace(/\&#39;/g, "'").replace(/\&#34;/g, "\"");
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
        plugins : "table textcolor searchreplace code fullscreen insertdatetime paste charmap save image link",
        toolbar1: "undo redo | bold italic underline | fontsizeselect | alignleft aligncenter alignright alignjustify | superscript subscript | bullist numlist outdent indent | forecolor backcolor | charmap | image link | save",
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

    // ask the user if he really wants to navigate out of the page
<?php
    if (isset($_SESSION['prefs']['close_warning']) && $_SESSION['prefs']['close_warning'] === 1) {
        echo "
    window.onbeforeunload = function (e) {
          e = e || window.event;
          return 'Do you want to navigate away from this page ? Unsaved changes will be lost !';
    };";
    }
?>
});
</script>

