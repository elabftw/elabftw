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
// inc/viewXP.php
// ID
if (isset($_GET['id']) && !empty($_GET['id']) && is_pos_int($_GET['id'])) {
    $id = $_GET['id'];
} else {
    $message = "The id parameter in the URL isn't a valid experiment ID.";
    display_message('error', $message);
    require_once 'inc/footer.php';
    die();
}

// SQL for viewXP
$sql = "SELECT experiments.id AS expid,
    experiments.*,
    status.color,
    status.name
    FROM experiments
    LEFT JOIN status ON (experiments.status = status.id)
    WHERE experiments.id = :id";
$req = $pdo->prepare($sql);
$req->bindParam(':id', $id, PDO::PARAM_INT);
$req->execute();
// got results ?
$row_count = $req->rowCount();
if ($row_count === 0) {
    $message = 'Nothing to show with this ID.';
    display_message('error', $message);
    require_once 'inc/footer.php';
    die();
}

$data = $req->fetch();

// Check id is owned by connected user to present comment div if not
if ($data['userid'] != $_SESSION['userid']) {
    // Can the user see this experiment which is not his ?
    if ($data['visibility'] == 'user') {
        $message = "<strong>Access forbidden:</strong> the visibility setting of this experiment is set to 'owner only'.";
        display_message('error', $message);
        require_once 'inc/footer.php';
        exit();
    } else {
        // get who owns the experiment
        $sql = 'SELECT firstname, lastname FROM users WHERE userid = :userid';
        $get_owner = $pdo->prepare($sql);
        $get_owner->bindParam(':userid', $data['userid']);
        $get_owner->execute();
        $owner = $get_owner->fetch();
        $message = "<strong>Read-only mode:</strong> this experiment is owned by ".$owner['firstname']." ".$owner['lastname'].".";
        display_message('info', $message);
    }
}



// Display experiment
?>
    <section class="item" style='border: 1px solid #<?php echo $data['color'];?>'>
<?php
echo "<img src='themes/".$_SESSION['prefs']['theme']."/img/calendar.png' title='date' alt='Date :' /><span class='date'> ".$data['date']."</span><br />
    <a href='experiments.php?mode=edit&id=".$data['expid']."'><img src='themes/".$_SESSION['prefs']['theme']."/img/edit.png' title='edit' alt='edit' /></a> 
<a href='duplicate_item.php?id=".$data['expid']."&type=exp'><img src='themes/".$_SESSION['prefs']['theme']."/img/duplicate.png' title='duplicate experiment' alt='duplicate' /></a> 
<a href='make_pdf.php?id=".$data['expid']."&type=experiments'><img src='themes/".$_SESSION['prefs']['theme']."/img/pdf.png' title='make a pdf' alt='pdf' /></a> 
<a href='javascript:window.print()'><img src='themes/".$_SESSION['prefs']['theme']."/img/print.png' title='Print this page' alt='Print' /></a> 
<a href='make_zip.php?id=".$data['expid']."&type=exp'><img src='themes/".$_SESSION['prefs']['theme']."/img/zip.png' title='make a zip archive' alt='zip' /></a> ";
// lock
if ($data['locked'] == 0) {
    echo "<a href='lock.php?id=".$data['expid']."&action=lock&type=experiments'><img src='themes/".$_SESSION['prefs']['theme']."/img/unlock.png' title='lock experiment' alt='lock' /></a>";
} else { // experiment is locked
    echo "<a href='lock.php?id=".$data['expid']."&action=unlock&type=experiments'><img src='themes/".$_SESSION['prefs']['theme']."/img/lock.png' title='unlock experiment' alt='unlock' /></a>";
}

// TAGS
show_tags($id, 'experiments_tags');
// TITLE : click on it to go to edit mode
?>
<div OnClick="document.location='experiments.php?mode=edit&id=<?php echo $data['expid'];?>'" class='title'>
    <?php echo stripslashes($data['title']);?>
    <span class='align_right' id='status'>(<?php echo $data['name'];?>)<span>
</div>
<?php
// BODY (show only if not empty, click on it to edit
if ($data['body'] != '') {
    ?>
    <div OnClick="document.location='experiments.php?mode=edit&id=<?php echo $data['expid'];?>'" class='txt'><?php echo stripslashes($data['body']);?></div>
    <?php
}
echo "<br />";

// DISPLAY FILES
require_once 'inc/display_file.php';

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
$req->execute(array(
    'id' => $id
));
// Check there is at least one link to display
if ($req->rowcount() > 0) {
    echo "<hr class='flourishes'>";
    echo "<img src='themes/".$_SESSION['prefs']['theme']."/img/link.png'> <h4 style='display:inline'>Linked items</h4>
<div id='links_div'><ul>";
    while ($link = $req->fetch()) {
        // SQL to get title
        echo "<li>[".$link['name']."] - <a href='database.php?mode=view&id=".$link['itemid']."'>".
            stripslashes($link['title'])."</a></li>";
    } // end while
    echo "</ul>";
} else { // end if link exist
    echo "<br />";
}

// DISPLAY eLabID
echo "<p class='elabid'>Unique eLabID : ".$data['elabid'];
// DISPLAY visibility
echo "<br />Visibility : ".$data['visibility']."</p>";
echo "</section>";
// COMMENT BOX
?>
<!-- we need to add a container here so the reload function in the callback of .editable() doesn't mess things up -->
<section id='expcomment_container'>
<div id='expcomment' class='item'>
    <h3>Comments</h3>
    <p class='editable newexpcomment' id='newexpcomment_<?php echo $id;?>'>Click to add a comment.</p>
<?php

// check if there is something to display first
// get all comments, and infos on the commenter associated with this experiment
$sql = "SELECT * FROM experiments_comments LEFT JOIN users ON (experiments_comments.userid = users.userid) WHERE exp_id = :id ORDER BY experiments_comments.datetime DESC";
$req = $pdo->prepare($sql);
$req->execute(array(
    'id' => $id
));
if ($req->rowCount() > 0) {
    // there is comments to display
    while ($comments = $req->fetch()) {
        if (empty($comments['firstname'])) {
            $comments['firstname'] = '[deleted]';
        }
    echo "<div class='expcomment_box'>
    <img class='align_right' src='themes/".$_SESSION['prefs']['theme']."/img/trash.png' title='delete' alt='delete' onClick=\"deleteThisAndReload(".$comments['id'].",'expcomment')\" />";
     echo "<span class='smallgray'>On ".$comments['datetime']." ".$comments['firstname']." ".$comments['lastname']." wrote :</span><br />";
        echo "<p class='editable' id='expcomment_".$comments['id']."'>".$comments['comment']."</p></div>";
    }
}
?>
</div>
</section>

<script>
// DELETE EXP COMMENT
function deleteThisAndReload(id, type) {
    var you_sure = confirm('Delete this ?');
    if (you_sure == true) {
        $.post('delete.php', {
            id:id,
            type:type
        })
        // on success we reload the block
        .success(function() {
             $('#expcomment_container').load("experiments.php?mode=view&id=<?php echo $id;?> #expcomment");
        });
    } else {
        return false;
    }
}

function makeEditable() {
    // Experiment comment is editable
    $('div#expcomment').on("mouseover", ".editable", function(){
        $('div#expcomment p.editable').editable('editinplace.php', {
            tooltip : 'Click to edit',
            indicator : 'Saving...',
            id   : 'id',
            name : 'expcomment',
            submit : 'Save',
            cancel : 'Cancel',
            style : 'display:inline',
            callback : function() {
                // now we reload the comments part to show the comment we just submitted
                $('#expcomment_container').load("experiments.php?mode=view&id=<?php echo $id;?> #expcomment");
                // we reload the function so editable zones are editable again
                makeEditable();
            }
        })
    });
}


// READY ? GO !!
$(document).ready(function() {
    // change title
    // fix for the ' and "
    title = "<?php echo $data['title']; ?>".replace(/\&#39;/g, "'").replace(/\&#34;/g, "\"");
    document.title = title;
    // Keyboard shortcuts
    key('<?php echo $_SESSION['prefs']['shortcuts']['create'];?>', function(){location.href = 'create_item.php?type=exp'});
    key('<?php echo $_SESSION['prefs']['shortcuts']['edit'];?>', function(){location.href = 'experiments.php?mode=edit&id=<?php echo $id;?>'});
    // make editable
    setInterval(makeEditable, 50);
});
</script>

