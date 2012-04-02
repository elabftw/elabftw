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
<?php
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
<input name='item_id' type='hidden' value='<? echo $id;?>' />
<h4>Date</h4><span class='smallgray'> (date format : YYMMDD)</span><br />
<img src='themes/<?php echo $_SESSION['prefs']['theme'];?>/img/calendar.png' title='date' alt='Date :' /><input name='date' id='datepicker' size='6' type='text' value='<?php echo $data['date'];?>' /><br />
<br /><h4>Title</h4><br />
      <textarea id='title' name='title' rows="1" cols="80"><?php if(empty($_SESSION['errors'])){
          echo stripslashes($data['title']);
      } else {
          echo stripslashes($_SESSION['new_title']);
      } ?></textarea>
<br /><br /><h4>Experiment</h4>
<span class='trigger smallgray'>(click here to show templates)</span>
<div class='toggle_container'><ul>
<? // SQL to get user's templates
$sql = "SELECT id, name FROM experiments_templates WHERE userid = ".$_SESSION['userid'];
$tplreq = $bdd->prepare($sql);
$tplreq->execute();
while ($tpl = $tplreq->fetch()) {
    echo "<li class='inline'><span class='templates' onclick='loadTpl(".$tpl['id'].")'>".$tpl['name']."</span></li> ";
}
?>
</ul></div><br />
<textarea id='body_textarea' name='body' rows="15" cols="80"><?php if(empty($_SESSION['errors'])){
    echo stripslashes($data['body']);
    } else {
    echo stripslashes($_SESSION['new_body']);
    } ?>
</textarea>
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
</form><!-- end editXP form -->
</section>

<script type='text/javascript'>
// JAVASCRIPT
<?php
// KEYBOARD SHORTCUTS
echo "key('".$_SESSION['prefs']['shortcuts']['create']."', function(){location.href = 'experiments.php?mode=create'});";
echo "key('".$_SESSION['prefs']['shortcuts']['submit']."', function(){document.forms['editXP'].submit()});";
?>
// TAGS AUTOCOMPLETE
$(function() {
		var availableTags = [
<?php // get all user's tag for autocomplete
$sql = "SELECT DISTINCT tag FROM experiments_tags WHERE userid = ".$_SESSION['userid']." LIMIT 50";
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
// ADD TAG JS
// listen keypress, add tag when it's enter
jQuery('#addtaginput').keypress(function (e) {
    addTagOnEnter(e);
});

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
// DATEPICKER
$(function() {
    $( "#datepicker" ).datepicker({dateFormat: 'ymmdd'});
});
// LOAD TEMPLATE JS
function loadTpl(id) {
    var request = $.ajax({
        url: 'load_tpl.php',
        type: "GET",
        data: {
            tpl_id: id
        },
        dataType: "text"
    });
    // add template in the body and stripslashes
    request.done(function (data) {
        $('#body_textarea').append(data.split("\\").join(""));
    request.fail(function (data) {
        alert('Template loading failed :/');
    });
    return false;
    });
}
// TOGGLE DIV
$(document).ready(function(){
	$(".toggle_container").hide();
	$("span.trigger").click(function(){
		$(this).toggleClass("active").next().slideToggle("slow");
	});
});
</script>
