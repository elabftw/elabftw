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

// TODO
$id = $_GET['id'];
// BEGIN CONTENT

// FILE UPLOAD
require_once 'inc/file_upload.php';
// DISPLAY FILES
//require_once 'inc/display_file.php';
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
    //title = "<?php //echo $experiment['title']; ?>".replace(/\&#39;/g, "'").replace(/\&#34;/g, "\"");
    //document.title = title;
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
