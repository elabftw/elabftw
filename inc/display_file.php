<?php
/**
 * inc/display_file.php
 *
 * @author Nicolas CARPi <nicolas.carpi@curie.fr>
 * @copyright 2012 Nicolas CARPi
 * @see http://www.elabftw.net Official website
 * @license AGPL-3.0
 */
use \Elabftw\Elabftw\Tools as Tools;

echo "<div id='filesdiv'>";
// What type of item we are displaying the files of ?
$type_arr = explode('.', basename($_SERVER['PHP_SELF']));
$type = $type_arr[0];
if ($type === 'database') {
    $type = 'items';
}
// Check that the item we view has attached files
$sql = "SELECT * FROM uploads WHERE item_id = :id AND type = :type";
$req = $pdo->prepare($sql);
$req->execute(array(
    'id' => $id,
    'type' => $type
));
$count = $req->rowCount();
if ($count > 1) {
    $count = 2;
}
if ($count > 0) {
    echo "<div class='box'>";
    echo "<img src='img/attached.png' class='bot5px'> <h3 style='display:inline'>" . ngettext('Attached file', 'Attached files', $count) . "</h3>";
    echo "<div class='row'>";
    while ($uploads_data = $req->fetch()) {
        echo "<div class='col-md-4 col-sm-6'>";
        echo "<div class='thumbnail'>";
        // show the delete button only in edit mode, not in view mode
        if ($_GET['mode'] === 'edit') {
            echo "<a class='align_right' href='app/delete_file.php?id=" . $uploads_data['id'] . "&type=" . $uploads_data['type'] . "&item_id=" . $uploads_data['item_id'] . "' onClick=\"return confirm('Delete this file ?');\">";
            echo "<img src='img/small-trash.png' title='delete' alt='delete' /></a>";
        } // end if it is in edit mode

        // get file extension
        $ext = filter_var(Tools::getExt($uploads_data['real_name']), FILTER_SANITIZE_STRING);
        $filepath = 'uploads/' . $uploads_data['long_name'];
        $thumbpath = $filepath . '_th.jpg';

        // list of extensions with a corresponding img/thumb-*.png image
        $common_extensions = array('avi', 'csv', 'doc', 'docx', 'mov', 'pdf', 'ppt', 'rar', 'xls', 'xlsx', 'zip');

        // Make thumbnail only if it isn't done already
        if (!file_exists($thumbpath)) {
            make_thumb($filepath, $ext, $thumbpath, 100);
        }

        // only display the thumbnail if the file is here
        if (file_exists($thumbpath) && preg_match('/(jpg|jpeg|png|gif)$/i', $ext)) {
            // we add rel='gallery' to the images for fancybox to display it as an album (possibility to go next/previous)
            echo "<a href='uploads/" . $uploads_data['long_name'] . "' class='fancybox' rel='gallery' ";
            if ($uploads_data['comment'] != 'Click to add a comment') {
                echo "title='" . $uploads_data['comment'] . "'";
            }
            echo "><img class='thumb' src='" . $thumbpath . "' alt='thumbnail' /></a>";

        // not an image
        } elseif (in_array($ext, $common_extensions)) {
            echo "<img class='thumb' src='img/thumb-" . $ext . ".png' alt='' />";

        // special case for mol files
        } elseif ($ext === 'mol' && $_SESSION['prefs']['chem_editor'] && $_GET['mode'] === 'view') {
            // we need to escape \n in the mol file or we get unterminated string literal error in JS
            $mol = str_replace("\n", "\\n", file_get_contents(ELAB_ROOT . 'uploads/' . $uploads_data['long_name']));
            echo "<div class='center'><script>
                  showMol('" . $mol . "');
                  </script></div>";

        } else { // uncommon extension without a nice image to display
            echo "<img class='thumb' src='img/thumb.png' alt='' />";
        }

        // now display the name + comment with icons
        echo "<div class='caption'><img src='img/attached.png' class='bot5px' alt='attached' /> ";
        echo "<a href='app/download.php?f=" . $uploads_data['long_name'] . "&name=" . $uploads_data['real_name'] . "' target='_blank'>" . $uploads_data['real_name'] . "</a>";
        echo "<span class='smallgray' style='display:inline'> " . Tools::formatBytes(filesize('uploads/' . $uploads_data['long_name'])) . "</span><br>";
        // if we are in view mode, we don't show the comment if it's the default text
        // this is to avoid showing 'Click to add a comment' where in fact you can't click to add a comment because
        // your are in view mode
        $comment = "<img src='img/comment.png' class='bot5px' alt='comment' />
                    <p class='editable inline' id='filecomment_".$uploads_data['id'] . "'>" .
                    stripslashes($uploads_data['comment']) . "</p>";

        if ($_GET['mode'] === 'edit' || $uploads_data['comment'] != 'Click to add a comment') {
            echo $comment;
        }

        echo "</div></div></div>";
    } // end while
    echo "</div></div>";
} // end if there is at least one file uploaded
// END DISPLAY FILES
echo "</div>";

// we only want the file comment div to be editable on edit mode, not view mode
if ($_GET['mode'] === 'edit') {
    ?>
    <script>
    $('.thumbnail').on("mouseover", ".editable", function(){
        $('.thumbnail p.editable').editable('app/editinplace.php', {
         tooltip : 'Click to edit',
         indicator : 'Saving...',
         name : 'filecomment',
         submit : 'Save',
         cancel : 'Cancel',
         style : 'display:inline'

        });
    });
    </script>
    <?php
}
?>
<script src='js/fancybox/source/jquery.fancybox.pack.js'></script>
<link rel="stylesheet" href="js/fancybox/source/jquery.fancybox.css" type="text/css" media="screen" />
<script>
$(document).ready(function() {
    // we use fancybox to display thumbnails
    $('a.fancybox').fancybox();
});
</script>
