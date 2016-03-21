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
    display_message('ko', _("The id parameter is not valid!"));
    require_once 'inc/footer.php';
    exit;
}

$statusClass = new \Elabftw\Elabftw\Status();
$experimentsClass = new \Elabftw\Elabftw\Experiments($id);
$experimentsView = new \Elabftw\Elabftw\ExperimentsView();

$statusArr = $statusClass->read($_SESSION['team_id']);

try {
    $experiment = $experimentsClass->read($id);
} catch (Exception $e) {
    display_message('ko', $e->getMessage());
    require_once 'inc/footer.php';
    exit;
}

// Check id is owned by connected user
if ($experiment['userid'] != $_SESSION['userid']) {
    display_message('ko', _('<strong>Cannot edit:</strong> this experiment is not yours!'));
    require_once 'inc/footer.php';
    exit;
}

// Check for lock
if ($experiment['locked'] == 1) {
    display_message('ko', _('<strong>This item is locked.</strong> You cannot edit it.'));
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
            <select id="visibility_select" name="visibility" onchange="experimentsUpdateVisibility(<?php echo $id; ?>, this.value)">
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
        </div>

        <div class='col-md-4'>
            <img src='img/status.png' class='bot5px' alt='status' />
            <label for='status_select'><?php echo ngettext('Status', 'Status', 1); ?></label>
                <select id='status_select' name="status" onchange="experimentsUpdateStatus(<?php echo $id; ?>, this.value)">
<?php
// now display all possible values of status in select menu
foreach ($statusArr as $status) {
    echo "<option ";
    if ($experiment['status'] === $status['id']) {
        echo "selected ";
    }
    echo "value='" . $status['id'] . "'>" . $status['name'] . "</option>";
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
        <img src='img/link.png' class='bot5px' class='bot5px'> <h4 style='display:inline'><?php echo _('Linked items'); ?></h4><br>
        <span id='links_div'>
<?php
// SHOW LINKS
echo $experimentsView->showLinks($id, 'edit');
?>
        </span>
        <p class='inline'><?php echo _('Add a link'); ?></p>
        <input id='linkinput' size='60' type="text" name="link" placeholder="<?php echo _('from the database'); ?>" />
    </section>
    <span class='align_right'>
    <?php
    // get the list of revisions
    $sql = "SELECT COUNT(*) FROM experiments_revisions WHERE item_id = :item_id AND userid = :userid ORDER BY savedate DESC";
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
$sql = "SELECT DISTINCT tag, id FROM experiments_tags INNER JOIN users ON (experiments_tags.userid = users.userid) WHERE users.team = :team ORDER BY id DESC LIMIT 500";
$getalltags = $pdo->prepare($sql);
$getalltags->bindParam(':team', $_SESSION['team_id']);
$getalltags->execute();
$tag_list = "";
while ($tag = $getalltags->fetch()) {
    $tag_list .= "'" . $tag[0] . "',";
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
        var tag = $('#addtaginput').val();
        // POST request
        $.post('app/add.php', {
            tag: tag,
            item_id: <?php echo $id; ?>,
            type: 'exptag'
        })
        // reload the tags list
        .done(function () {
            $("#tags_div").load("experiments.php?mode=edit&id=<?php echo $id; ?> #tags_div");
            // clear input field
            $("#addtaginput").val("");
            return false;
        })
    } // end if key is enter
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
        source: [<?php echo getDbList('default'); ?>]
    });

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
            $.post("app/quicksave.php", {
                id : <?php echo $id; ?>,
                type : 'experiments',
                // we need this to get the updated content
                title : document.getElementById('title_input').value,
                date : document.getElementById('datepicker').value,
                body : tinymce.activeEditor.getContent()
            }).done(function(data) {
                if (data == 1) {
                    notif("<?php echo _('Saved'); ?>", "ok");
                } else {
                    notif("<?php echo _('Something went wrong! :('); ?>", "ko");
                }
            });
        },
        // keyboard shortcut to insert today's date at cursor in editor
        setup : function(editor) {
            editor.addShortcut("ctrl+shift+d", "add date at cursor", function() { addDateOnCursor(); });
        },
        mentions: {
            source: [<?php echo getDbList('mention'); ?>],
            delimiter: '#'
        },
        language : '<?php echo $_SESSION['prefs']['lang']; ?>',
        style_formats_merge: true,
        style_formats: [
            {
                title: 'Image Left',
                selector: 'img',
                styles: {
                    'float': 'left',
                    'margin': '0 10px 0 10px'
                }
             },
             {
                 title: 'Image Right',
                 selector: 'img',
                 styles: {
                     'float': 'right',
                     'margin': '0 0 10px 10px'
                 }
             }
        ]
    });

    // ADD TAG JS
    // listen keypress, add tag when it's enter
    $('#addtaginput').keypress(function (e) {
        addTagOnEnter(e);
    });
    // ADD LINK JS
    // listen keypress, add link when it's enter
    $('#linkinput').keypress(function (e) {
        experimentsCreateLink(e, <?php echo $id; ?>);
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
