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
require_once('inc/common.php');
$page_title= 'TEAM'; 
require_once('inc/head.php');
require_once('inc/menu.php');
?>
<div id='accordion'>
<h3><a href='#team'>TEAM MEMBERS</a></h3>
<div>
<?php // SQL to get members info
$sql = "SELECT * FROM users WHERE validated = 1";
$req = $bdd->prepare($sql);
$req->execute();
echo "<ul>";
while ($data = $req->fetch()) {
    echo "<li><img src='img/profile.png' alt='' /> ";
    echo "<a href='mailto:".$data['email']."'>".$data['firstname']." ".$data['lastname']."</a>";
        if (!empty($data['phone'])) { 
        echo " <img src='themes/".$_SESSION['prefs']['theme']."/img/phone.png' alt='Phone :' title='phone' /> ".$data['phone'];
        } 
        if (!empty($data['cellphone'])) { 
        echo " <img src='themes/".$_SESSION['prefs']['theme']."/img/cellphone.png' alt='Cellphone :' title='Cellphone' /> ".$data['cellphone']; 
        }
        if (!empty($data['website'])) { 
        echo " <img src='themes/".$_SESSION['prefs']['theme']."/img/website.png' alt='website :' title='website' /> <a href='".$data['website']."'>www</a>"; 
        }
        if (!empty($data['skype'])) { 
        echo " <img src='themes/".$_SESSION['prefs']['theme']."/img/skype.png' alt='skype :' title='skype' /> ".$data['skype'];
        } 
    echo "</li>";
}
echo "</ul>";
?>
</div>
<h3><a href='#labmeetings'>LABMEETINGS</a></h3>
<div>
<h4>Past labmeetings :</h4><br />
<?php
// SQL to get past labmeetings files
$sql = "SELECT * FROM uploads WHERE type = 'lm'";
$req = $bdd->prepare($sql);
$req->execute();
while ($data = $req->fetch()) {
        echo "<div class='filesdiv'>";
        ?>
            <a class='align_right' href='delete_file.php?id=<?php echo $data['id'];?>&type=<?php echo $data['type'];?>' onClick="return confirm('Delete this file ?');"><img src='themes/<?php echo $_SESSION['prefs']['theme'];?>/img/trash.png' title='delete' alt='delete' /></a>
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
         name : 'content'
     });
 });
    </script>
<form name="addLM" method="post" action="addLM-exec.php" enctype="multipart/form-data">
<?php
require('inc/file_upload_nojs.php');
?>
</form>

<p><a href='http://wiki-bio6.curie.fr/wiki/index.php/Piel_Lab_inner_working#Lab_meetings' target='_blank'>Relevant wiki link</a></p>
<p class='center'><img src='img/labmeetings-2012.png' alt='labmeetings' title='labmeetings 2012' /></p>
</div>

<h3><a href='#journal'>JOURNAL CLUB</a></h3>
<div>
<a href='#' onClick='window.open("jclub_rules.php", "window", "menubar=0,resizable=1,width=1000,height=460");' id='show_jc_rules'>Show rules</a><br />
<h4>Past journal clubs :</h4><br />
<?php
// SQL to get past journal clubs
$sql = "SELECT * FROM uploads WHERE type = 'jc' ORDER BY date DESC";
$req = $bdd->prepare($sql);
$req->execute();
while ($data = $req->fetch()) {
        echo "<div class='filesdiv'>";
        ?>
            <a class='align_right' href='delete_file.php?id=<?php echo $data['id'];?>&type=<?php echo $data['type'];?>' onClick="return confirm('Delete this file ?');"><img src='themes/<?php echo $_SESSION['prefs']['theme'];?>/img/trash.png' title='delete' alt='delete' /></a>
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
         name : 'content'
     });
 });
    </script>
<form name="addJC" method="post" action="addJC-exec.php" enctype="multipart/form-data">
<?php
require('inc/file_upload_nojs.php');
?>
</form>

<?php
/*
//////////////////////////////////////////
// List labmembers with journal points and last_jc
////////////////////////////////////////////
echo "<p>Current stats :</p><ul>";

// SQL
$sql = "SELECT firstname, lastname, journal, last_jc FROM users ORDER BY last_jc desc";
$req = $bdd->prepare($sql);
$req->execute();
while ($data = $req->fetch()) {
    echo "<li><span class='strongblue'>".$data['firstname']." ".$data['lastname']."</span> already presented ".$data['journal']. " journals (last was on ".$data['last_jc'].")</li>";
}
echo "</ul>";

// Begin switch between already choose nb or not
if(!isset($_POST['nb'])) {
    echo "<h3>Choose the number of participants :</h3>";
?>
<form name="jcForm" method="post" action="team.php">
<select name="nb">
<option value="1">1</option>
<option value="2">2</option>
<option value="3">3</option>
<option value="4" selected>4</option>
<option value="5">5</option>
<option value="6">6</option>
</select> speaker(s)<br /><br />
<input type="submit" name="Submit" value="Who's next ?" />
</form>
<?php
} else {

//////////////////////////////////////
// Code for displaying next members //
// Only between 0 and 20 members
$int_options = array("options"=>
    array("min_range"=>0, "max_range"=>20));
// Check that nb is an int and assign it to $_SESSION because we need it for the increment page
if(filter_var($_POST['nb'], FILTER_VALIDATE_INT, $int_options)) {
        $_SESSION['S_jcnb'] = intval($_POST['nb']);
        session_write_close();
}
// SQL
$req = $bdd->query("SELECT * FROM `users` ORDER BY `users`.`last_jc` ASC LIMIT 0 , ".$_SESSION['S_jcnb']."");
echo "<a name='pres'></a><p>The next Journal Club will be presented by :</p><ul>";
while ($datas = $req->fetch()) {
    echo "<li><span class='strong'>". $datas['username'] . "</span></li>";
}
echo "</ul>";
// Link for incrementing journal values //
echo "<p>This journal club has passed : <a href='jc-exec.php?inc=1'>increment</a></p>";
}
 */
?>
</div>
</div>
</div>

<?php require_once('inc/footer.php');?>

<script type="text/javascript">
// ACCORDION
$(function() {
    $( "#accordion" ).accordion({ 
        autoHeight: false,
        //animated: 'bounceslide',
        animated: 'slide',
        collapsible: true,
        active: false
    });
});
</script>
