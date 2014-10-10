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
if ($count > 0) {
    echo "<div class='box'>";
    if ($count === 1) {
        echo "<img src='img/attached.png' class='bot5px'> <h3 style='display:inline'>Attached file</h3>";
    } else {
        echo "<img src='img/attached.png' class='bot5px'> <h3 style='display:inline'>Attached files</h3>";
    }
        echo "<div class='row'>";
    while ($uploads_data = $req->fetch()) {
        echo "<div class='col-md-4 col-sm-6'>";
        echo "<div class='thumbnail'>";
        // show the delete button only in edit mode, not in view mode
        if ($_GET['mode'] === 'edit') {
            echo "<a class='align_right' href='delete_file.php?id=".$uploads_data['id']."&type=".$uploads_data['type']."&item_id=".$uploads_data['item_id']."' onClick=\"return confirm('Delete this file ?');\">";
            echo "<img src='img/small-trash.png' title='delete' alt='delete' /></a>";
        } // end if it is in edit mode

        // get file extension
        $ext = filter_var(get_ext($uploads_data['real_name']), FILTER_SANITIZE_STRING);
        $filepath = 'uploads/'.$uploads_data['long_name'];
        $filesize = filesize('uploads/'.$uploads_data['long_name']);
        $thumbpath = 'uploads/'.$uploads_data['long_name'].'_th.'.$ext;

        // list of image type we can deal with the GD lib
        $image_extensions = array('jpg', 'jpeg', 'JPG', 'JPEG', 'png', 'PNG', 'gif', 'GIF');
        // list of extensions with a corresponding img/thumb-*.png image
        $common_extensions = array('avi', 'csv', 'doc', 'docx', 'mov', 'pdf', 'ppt', 'rar', 'xls', 'xlsx', 'zip');

        // Make thumbnail only if it isn't done already and if size < 2 Mbytes and if it's an image
        if (!file_exists($thumbpath)
            && $filesize <= 2000000
            && in_array($ext, $image_extensions)) {
            make_thumb($filepath, $ext, $thumbpath, 100);
        }
        // only display the thumbnail if the file is here
        if (file_exists($thumbpath) && in_array($ext, $image_extensions)) {
            // we add rel='gallery' to the images for fancybox to display it as an album (possibility to go next/previous)
            echo "<a href='uploads/".$uploads_data['long_name']."' class='fancybox' rel='gallery' ";
            if ($uploads_data['comment'] != 'Click to add a comment') {
                echo "title='".$uploads_data['comment']."'";
            }
            echo "><img class='thumb' src='".$thumbpath."' alt='thumbnail' /></a>";
        } elseif (in_array($ext, $common_extensions)) {
            echo "<img class='thumb' src='img/thumb-$ext.png' alt='' />";
        } else { // uncommon extension without a nice image to display
            echo "<img class='thumb' src='img/thumb.png' alt='' />";
        }

        // now display the name + comment with icons
        echo "<div class='caption'><img src='img/attached.png' class='bot5px' alt='attached' /> ";
        echo "<a href='download.php?f=".$uploads_data['long_name']."&name=".$uploads_data['real_name']."' target='_blank'>".$uploads_data['real_name']."</a>";
        echo "<span class='smallgray' style='display:inline'> ".format_bytes(filesize('uploads/'.$uploads_data['long_name']))."</span><br>";
        // if we are in view mode, we don't show the comment if it's the default text
        // this is to avoid showing 'Click to add a comment' where in fact you can't click to add a comment because
        // your are in view mode
        $comment = "<img src='img/comment.png' class='bot5px' alt='comment' />
                    <p class='editable inline' id='filecomment_".$uploads_data['id']."'>".
                    stripslashes($uploads_data['comment'])."</p>";

        if ($_GET['mode'] === 'edit' || $uploads_data['comment'] != 'Click to add a comment') {
            echo $comment;
        }

        echo "</div></div></div>";
    } // end while
    echo "</div></div>";
} // end if count > 0
// END DISPLAY FILES
echo "</div>";

// we only want the file comment div to be editable on edit mode, not view mode
if ($_GET['mode'] === 'edit') {
    ?>
    <script>
    $('.thumbnail').on("mouseover", ".editable", function(){
        $('.thumbnail p.editable').editable('editinplace.php', {
         tooltip : 'Click to edit',
         indicator : 'Saving...',
         id   : 'id',
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
