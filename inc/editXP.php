<?php
/**
 * inc/editXP.php
 *
 * @author Nicolas CARPi <nicolas.carpi@curie.fr>
 * @copyright 2012 Nicolas CARPi
 * @see http://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

// ID
if (isset($_GET['id']) && !empty($_GET['id']) && is_pos_int($_GET['id'])) {
    $id = $_GET['id'];
} else {
    display_message('error', _("The id parameter is not valid!"));
    require_once 'inc/footer.php';
    exit;
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
    display_message('error', _('<strong>Cannot edit:</strong> this experiment is not yours!'));
    require_once 'inc/footer.php';
    exit;
}

// Check for lock
if ($experiment['locked'] == 1) {
    display_message('error', _('<strong>This item is locked.</strong> You cannot edit it.'));
    require_once 'inc/footer.php';
    exit;
}

// BEGIN CONTENT
?>
<link rel="stylesheet" media="all" href="css/autocomplete.css" />
<script src="js/tinymce/tinymce.min.js"></script>
<menu class='border'><a href='experiments.php?mode=show'><img src='img/arrow-left-blue.png' class='bot5px' alt='' /> <?php echo _('Back to experiments listing'); ?></a></menu>

<section class='box' id='main_section' style='border-left: 6px solid #<?php echo $experiment['color']; ?>'>
    <img class='align_right' src='img/big-trash.png' title='delete' alt='delete' onClick="deleteThis('<?php echo $id; ?>','exp', 'experiments.php')" />

    <?php
    // TAGS
    echo displayTags('experiments', $id);
    ?>

    <!-- BEGIN EDITXP FORM -->
    <form id="editXP" method="post" action="app/editXP-exec.php" enctype='multipart/form-data'>
    <input name='item_id' type='hidden' value='<?php echo $id; ?>' />

    <div class='row'>

        <div class='col-md-4'>
            <img src='img/calendar.png' class='bot5px' title='date' alt='calendar' />
            <label for='datepicker'><?php echo _('Date'); ?></label>
            <!-- TODO if firefox has support for it: type = date -->
            <input name='date' id='datepicker' size='8' type='text' value='<?php echo $experiment['date']; ?>' />
        </div>

        <div class='col-md-4'>
            <img src='img/eye.png' class='bot5px' alt='visibility' />
            <label for='visibility_select'><?php echo _('Visibility'); ?></label>
            <select id="visibility_select" name="visibility" onchange="update_visibility(this.value)">
                <option value="organization" <?php if ($experiment['visibility'] === 'organization') {
        echo "selected";
    }?>><?php echo _('Everyone with an account'); ?></option>
                <option value="team" <?php if ($experiment['visibility'] === 'team') {
        echo "selected";
    }?>><?php echo _('Only the team'); ?></option>
                <option value="user" <?php if ($experiment['visibility'] === 'user') {
        echo "selected";
    }
    ?>><?php echo _('Only me'); ?></option>
    <?php
    $sql = "SELECT id, name FROM team_groups WHERE team = :team";
    $req = $pdo->prepare($sql);
    $req->bindParam(':team', $_SESSION['team_id']);
    $req->execute();
    while ($team_groups = $req->fetch()) {
        echo "<option value='" . $team_groups['id'] . "'";
        if ($experiment['visibility'] === $team_groups['id']) {
            echo " selected";
        }
        echo ">Only " . $team_groups['name'] . "</option>";
    }
    ?>
            </select>
            <span id='visibility_msg_div'><?php echo _('Updated!'); ?></span>
        </div>

        <div class='col-md-4'>
            <img src='img/status.png' class='bot5px' alt='status' />
            <label for='status_select'><?php echo ngettext('Status', 'Status', 1); ?></label>
            <script>
            // this array is used by updateStatus() to get the color of new status
            var status_arr = Array();
            </script>

            <?php
            // put all available status in array
            $status_arr = array();
            // SQL to get all the status of the team
            $sql = 'SELECT id, name, color FROM status WHERE team = :team ORDER BY ordering ASC';
            $req = $pdo->prepare($sql);
            $req->execute(array(
                'team' => $_SESSION['team_id']
            ));

            while ($status = $req->fetch()) {
                $status_arr[$status['id']] = $status['name'];
                // get also a JS array for update_status() that needs the color to set the border immediately
                echo "<script>
                    status_arr['".$status['id'] . "'] =  '" . $status['color'] . "';
                    </script>";
            }
            ?>
            <select id='status_select' name="status" onchange="updateStatus(this.value)">
            <?php
            // now display all possible values of status in select menu
            foreach ($status_arr as $key => $value) {
                echo "<option ";
                if ($experiment['status'] == $key) {
                    echo "selected ";
                }
                echo "value='" . $key . "'>" . $value . "</option>";
            }
            ?>
            </select>
        </div>

    </div>

    <h4><?php echo _('Title'); ?></h4>
    <input id='title_input' name='title' rows="1" value="<?php echo stripslashes($experiment['title']); ?>" required />
    <h4><?php echo ngettext('Experiment', 'Experiments', 1); ?></h4>
    <textarea id='body_area' class='mceditable' name='body' rows="15" cols="80">
        <?php echo stripslashes($experiment['body']); ?>
    </textarea>

    <!-- SUBMIT BUTTON -->
    <div id='saveButton'>
        <button type="submit" name="Submit" class='button'><?php echo _('Save and go back'); ?></button>
    </div>
    </form><!-- end editXP form -->

    <!-- LINKED ITEMS -->
    <section>
        <img src='img/link.png' class='bot5px' class='bot5px'> <h4 style='display:inline'><?php echo _('Linked items'); ?></h4>
        <span id='links_div'>
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
                    echo "<li>- [" . $links['name'] . "] - <a href='database.php?mode=view&id=" . $links['itemid'] . "'>" .
                        stripslashes($links['title']) . "</a>";
                    echo "<a onclick='delete_link(" . $links['linkid'] . ", " . $id . ")'>
                    <img src='img/small-trash.png' title='delete' alt='delete' /></a></li>";
                } // end while
                echo "</ul>";
            } else { // end if link exist
                echo "<br />";
            }
            ?>
        </span>
        <p class='inline'><?php echo _('Add a link'); ?></p>
        <input id='linkinput' size='60' type="text" name="link" placeholder="<?php echo _('from the database'); ?>" />
    </section>
    <span class='align_right'>
    <?php
    // get the list of revisions
    $sql = "SELECT COUNT(id) FROM experiments_revisions WHERE item_id = :item_id AND userid = :userid ORDER BY savedate DESC";
    $req = $pdo->prepare($sql);
    $req->execute(array(
        'item_id' => $id,
        'userid' => $_SESSION['userid']
    ));
    $rev_count = $req->fetch();
    $count = intval($rev_count[0]);
    if ($count > 0) {
        echo $count . " " . ngettext('revision available.', 'revisions available.', $count) . " <a href='revision.php?type=experiments&item_id=" . $id . "'>" . _('Show history') . "</a>";
    }
    ?>
    </span>

</section>
<?php
if ($_SESSION['prefs']['chem_editor']) {
    ?>
        <div class='box chemdoodle'>
            <h3><?php echo _('Molecule drawer'); ?></h3>
            <div class='center'>
                <script>
                    var sketcher = new ChemDoodle.SketcherCanvas('sketcher', 550, 300, {oneMolecule:true});
                </script>
            </div>
    </div>
    <?php
}
// FILE UPLOAD
require_once 'inc/file_upload.php';
// DISPLAY FILES
require_once 'inc/display_file.php';
?>

<?php
// TAG AUTOCOMPLETE
$sql = "SELECT DISTINCT tag FROM experiments_tags WHERE userid = :userid ORDER BY id DESC LIMIT 500";
$getalltags = $pdo->prepare($sql);
$getalltags->bindParam(':userid', $_SESSION['userid']);
$getalltags->execute();
$tag_list = "";
while ($tag = $getalltags->fetch()) {
    $tag_list .= "'" . $tag[0] . "',";
}

// LINK AUTOCOMPLETE
$link_list = "";
$tinymce_list = "";
$sql = "SELECT items_types.name,
items.id AS itemid,
items.* FROM items
LEFT JOIN items_types
ON items.type = items_types.id
WHERE items.team = :team";
$getalllinks = $pdo->prepare($sql);
$getalllinks->bindParam(':team', $_SESSION['team_id'], PDO::PARAM_INT);
if ($getalllinks->execute()) {

    while ($link = $getalllinks->fetch()) {
        $link_type = $link['name'];
        // html_entity_decode is needed to convert the quotes
        // str_replace to remove ' because it messes everything up
        $link_name = str_replace("'", "", html_entity_decode(substr($link['title'], 0, 60), ENT_QUOTES));
        // remove also the % (see issue #62)
        $link_name = str_replace("%", "", $link_name);
        $link_list .= "'" . $link['itemid'] . " - " . $link_type . " - " . $link_name . "',";
        $tinymce_list .= "{ name : \"<a href='database.php?mode=view&id=" . $link['itemid'] . "'>" . $link_name . "</a>\"},";
    }
}
?>
<script>
// DELETE TAG
function delete_tag(tag_id, item_id) {
    var you_sure = confirm('<?php echo _('Delete this?'); ?>');
    if (you_sure == true) {
        $.post('app/delete.php', {
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
        $.post('app/add.php', {
            tag: tag,
            item_id: <?php echo $id; ?>,
            type: 'exptag'
        })
        // reload the tags list
        .success(function () {
            $("#tags_div").load("experiments.php?mode=edit&id=<?php echo $id; ?> #tags_div");
            // clear input field
            $("#addtaginput").val("");
            return false;
        })
    } // end if key is enter
}

// DELETE LINK
function delete_link(id, item_id) {
    var you_sure = confirm('<?php echo _('Delete this?'); ?>');
    if (you_sure == true) {
        $.post('app/delete.php', {
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
                $.post('app/add.php', {
                    type: 'link',
                    link_id: link_id,
                    item_id: <?php echo $id; ?>
                })
                // reload the link list
                .done(function () {
                    $("#links_div").load("experiments.php?mode=edit&id=<?php echo $id; ?> #links_div");
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
    $.post("app/quicksave.php", {
        id : <?php echo $id; ?>,
        status : status
        // change the color of the item border
    }).done(function() {
        // we first remove any status class
        $("#main_section").css('border', null);
        // and we add our new border color
        // first : get what is the color of the new status
        var css = '6px solid #' + status_arr[status];
        $("#main_section").css('border-left', css);
    });
}

// This function is activated with the select element and send a post request to quicksave.php
function update_visibility(visibility) {
    $.post("app/quicksave.php", {
        id : <?php echo $id; ?>,
        visibility : visibility
    }).done(function() {
        // once it's update we show a message for some time before making it disappear
        $("#visibility_msg_div").show(0, function() {
            setTimeout(function() {
                $("#visibility_msg_div").hide(500);
            }, 1500)
        });
    });
}

// READY ? GO !!
$(document).ready(function() {
    // KEYBOARD SHORTCUTS
    key('<?php echo $_SESSION['prefs']['shortcuts']['create']; ?>', function(){location.href = 'app/create_item.php?type=exp'});
    key('<?php echo $_SESSION['prefs']['shortcuts']['submit']; ?>', function(){document.forms['editXP'].submit()});

    // autocomplete the tags
    $("#addtaginput").autocomplete({
        source: [<?php echo $tag_list; ?>]
    });

    // autocomplete the links
    $( "#linkinput" ).autocomplete({
        source: [<?php echo $link_list; ?>]
    });
    // hide the little 'Updated !' message
    $('#visibility_msg_div').hide();

    // fix for the ' and "
    title = "<?php echo $experiment['title']; ?>".replace(/\&#39;/g, "'").replace(/\&#34;/g, "\"");
    document.title = title;
    // DATEPICKER
    $( "#datepicker" ).datepicker({dateFormat: 'yymmdd'});
    // If the title is 'Untitled', clear it on focus
    $("#title_input").focus(function(){
        if ($(this).val() === 'Untitled') {
            $("#title_input").val('');
        }
    });
    // EDITOR
    tinymce.init({
        mode : "specific_textareas",
        editor_selector : "mceditable",
        content_css : "css/tinymce.css",
        plugins : "table textcolor searchreplace code fullscreen insertdatetime paste charmap save image link pagebreak mention",
        pagebreak_separator: "<pagebreak>",
        toolbar1: "undo redo | bold italic underline | fontsizeselect | alignleft aligncenter alignright alignjustify | superscript subscript | bullist numlist outdent indent | forecolor backcolor | charmap | image link | save",
        removed_menuitems : "newdocument",
        // save button :
        save_onsavecallback: function() {
            $.ajax({
                type: "POST",
                url: "app/quicksave.php",
                data: {
                id : <?php echo $id; ?>,
                type : 'experiments',
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
        mentions: {
            source: [<?php echo $tinymce_list; ?>],
            delimiter: '#'
        },
        language : '<?php echo $_SESSION['prefs']['lang']; ?>'
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
          return '"._('Do you want to navigate away from this page? Unsaved changes will be lost!') . "';
    };";
    }
?>
});
</script>
