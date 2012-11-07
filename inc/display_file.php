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
// What type of item we are displaying the files of ?
$type_arr = explode('.', basename($_SERVER['PHP_SELF']));
$type = $type_arr[0];
// Check that the item we view has attached files
$sql = "SELECT * FROM uploads WHERE item_id = :id AND type = :type"; 
$req = $bdd->prepare($sql);
$req->execute(array(
    'id' => $id,
    'type' => $type
));
$count = $req->rowCount();
if($count > 0){
    echo "<section id='filesdiv'><h3>ATTACHED FILES</h3>";
    while ($uploads_data = $req->fetch()){
        echo "<div class='filesdiv'>";
        ?>
            <a class='align_right' href='delete_file.php?id=<?php echo $uploads_data['id'];?>&type=<?php echo $uploads_data['type'];?>&item_id=<?php echo $uploads_data['item_id'];?>' onClick="return confirm('Delete this file ?');"><img src='themes/<?php echo $_SESSION['prefs']['theme'];?>/img/trash.png' title='delete' alt='delete' /></a>
        <?php
        // Get file extension to display thumbnail if it's an image
        $ext = get_ext($uploads_data['real_name']);
        if ($ext === 'jpg' || $ext === 'jpeg' || $ext === 'JPG' || $ext === 'png' || $ext === 'gif'){
            $filepath = 'uploads/'.$uploads_data['long_name'];
            $filesize = filesize('uploads/'.$uploads_data['long_name']);
            $thumbpath = 'uploads/'.$uploads_data['long_name'].'_th.'.$ext;
            // Make thumbnail only if it isn't done already and if size < 2 Mbytes
            if(!file_exists($thumbpath) && $filesize <= 2000000){
                make_thumb($filepath,$ext,$thumbpath,150);
            }
            echo "<div class='center'>";
            echo "<img src='".$thumbpath."' alt='' /></div>";
        }
        echo "<img src='themes/".$_SESSION['prefs']['theme']."/img/attached_file.png' alt='' /> <a href='download.php?id=".$uploads_data['id']."&f=".$uploads_data['long_name']."&name=".$uploads_data['real_name']."' target='_blank'>".$uploads_data['real_name']."</a>
        <span class='filesize'> (".format_bytes(filesize('uploads/'.$uploads_data['long_name'])).")</span><br />";
        echo "<img src='themes/".$_SESSION['prefs']['theme']."/img/comments.png' alt='comment' /> <p class='editable' id='comment_".$uploads_data['id']."'>".stripslashes($uploads_data['comment'])."</p></div>";
    } // end while
    echo "</section>";
} // end if count > 0
// END DISPLAY FILES
?>
<!-- to edit file comments using jquery jeditable plugin -->
<script>
 $(document).ready(function() {
     $('.editable').editable('editinplace.php', { 
         tooltip : 'Click to edit',
             indicator : 'Saving...',
         id   : 'id',
         submit : 'Save',
         cancel : 'Cancel',
         style : 'display:inline',
         name : 'content'

     });
 });
    </script>
