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
// Check that the item we view has attached files
$sql = "SELECT id, real_name, long_name, comment, item_id, userid, type FROM uploads WHERE item_id = ".$id;
$req = $bdd->prepare($sql);
$req->execute();
$count = $req->rowCount();
if($count > 0){
    echo "<section id='filesdiv'><h3>ATTACHED FILES</h3>";
    while ($data = $req->fetch()){
        echo "<div class='filesdiv'>";
        ?>
            <a class='align_right' href='delete_file.php?id=<?php echo $data['id'];?>&type=<?php echo $data['type'];?>&item_id=<?php echo $data['item_id'];?>' onClick="return confirm('Delete this file ?');"><img src='themes/<?php echo $_SESSION['prefs']['theme'];?>/img/trash.png' title='delete' alt='delete' /></a>
        <?php
        // Get file extension to display thumbnail if it's an image
        $ext = get_ext($data['real_name']);
        if ($ext === 'jpg' || $ext === 'jpeg' || $ext === 'JPG' || $ext === 'png' || $ext === 'gif'){
            $filepath = 'uploads/'.$data['long_name'];
            $filesize = filesize('uploads/'.$data['long_name']);
            $thumbpath = 'uploads/'.$data['long_name'].'_th.'.$ext;
            // Make thumbnail only if it isn't done already and if size < 2 Mbytes
            if(!file_exists($thumbpath) && $filesize <= 2000000){
                make_thumb($filepath,$ext,$thumbpath,150);
            }
            echo "<div class='center'>";
            echo "<img src='".$thumbpath."' alt='' /></div>";
        }
        echo "<img src='themes/".$_SESSION['prefs']['theme']."/img/attached_file.png' alt='' /> <a href='download.php?id=".$data['id']."&f=".$data['long_name']."&name=".$data['real_name']."' target='_blank'>".$data['real_name']."</a>
        <span class='filesize'> (".format_bytes(filesize('uploads/'.$data['long_name'])).")</span><br />";
        echo "<img src='themes/".$_SESSION['prefs']['theme']."/img/comments.png' alt='comment' /> <p class='editable' id='comment_".$data['id']."'>".stripslashes($data['comment'])."</p></div>";
    } // end while
    echo "</section>";
} // end if count > 0
// END DISPLAY FILES
?>
<!-- using jquery jeditable plugin -->
<script type='text/javascript'>
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
