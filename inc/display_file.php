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
echo "<section id='filesdiv'>";
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
    echo "<h3>ATTACHED FILES</h3>";
    while ($uploads_data = $req->fetch()) {
        echo "<div class='filesdiv'>";
        // show the delete button only in edit mode, not in view mode
        if ($_GET['mode'] === 'edit') {
            echo "<a class='align_right' href='delete_file.php?id=".$uploads_data['id']."&type=".$uploads_data['type']."&item_id=".$uploads_data['item_id']."' onClick=\"return confirm('Delete this file ?');\">";
            echo "<img src='img/trash.png' title='delete' alt='delete' /></a>";
        } // end if it is in edit mode

        // THUMBNAIL GENERATION
        // check first for the GD extension
        if (extension_loaded('gd')) {
            // Get file extension to display thumbnail if it's a valid extension
            $ext = get_ext($uploads_data['real_name']);
            if ($ext === 'jpg' ||
                $ext === 'jpeg' ||
                $ext === 'JPG' ||
                $ext === 'JPEG' ||
                $ext === 'png' ||
                $ext === 'gif') {

                $filepath = 'uploads/'.$uploads_data['long_name'];
                $filesize = filesize('uploads/'.$uploads_data['long_name']);
                $thumbpath = 'uploads/'.$uploads_data['long_name'].'_th.'.$ext;
                // Make thumbnail only if it isn't done already and if size < 2 Mbytes
                if (!file_exists($thumbpath) && $filesize <= 2000000) {
                    make_thumb($filepath, $ext, $thumbpath, 150);
                }
                // only display the thumbnail if the file is here
                if (file_exists($thumbpath)) {
                    echo "<div class='center'>";
                    // we add rel='gallery' to the images for fancybox to display it as an album (possibility to go next/previous)
                    echo "<a href='uploads/".$uploads_data['long_name']."' class='fancybox' rel='gallery' ";
                    if ($uploads_data['comment'] != 'Click to add a comment') {
                        echo "title='".$uploads_data['comment']."'";
                    }
                    echo "><img src='".$thumbpath."' width='150' alt='thumbnail' /></a></div>";
                }
            } // end if extension is valid
        } // end gd here
        // END THUMBNAIL GENERATION
        echo "<img src='img/attached_file.png' alt='' /> <a href='download.php?f=".$uploads_data['long_name']."&name=".$uploads_data['real_name']."' target='_blank'>".$uploads_data['real_name']."</a>
        <span class='filesize'> (".format_bytes(filesize('uploads/'.$uploads_data['long_name'])).")</span><br />";
        // if we are in view mode, we don't show the comment
        // this is to avoid showing 'Click to add a comment' where in fact you can't click to add a comment because
        // your are in view mode
        switch ($_GET['mode']) {
        case 'view':
            if ($uploads_data['comment'] != 'Click to add a comment') {
                // show non editable comment
                echo "<img src='img/comments.png' alt='comment' />
                    <p id='filecomment_".$uploads_data['id']."'>".stripslashes($uploads_data['comment'])."</p>";
            }
            break;
        case 'edit':
            // show editable comment whatever is the comment
            echo "<img src='img/comments.png' alt='comment' />
                <p class='editable' id='filecomment_".$uploads_data['id']."'>".
                stripslashes($uploads_data['comment'])."</p>";
            break;
        default:
            die();
        }
        echo "</div>";
    } // end while
} // end if count > 0
// END DISPLAY FILES
?>
</section>

<?php
// we only want the file comment div to be editable on edit mode, not view mode
if ($_GET['mode'] === 'edit') {
    ?>
    <script>
    $('section#filesdiv').on("mouseover", ".editable", function(){
        $('section#filesdiv p.editable').editable('editinplace.php', {
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
    /*
        txtImage: '<?php
        if (!empty($uploads_data['real_name'])) {
           echo $uploads_data['real_name'];
        } else {
           echo '';
        };?>'
     */
});
</script>

