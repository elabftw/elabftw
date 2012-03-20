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
<a name='jc'></a><section class='item'>
<h3 class='trigger'>JOURNAL CLUB</h3>
<div class='toggle_container'>
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
<h4>Rules :</h4><br />
<p>
Pick a recent paper (no more than 6 months old) somewhat close to your project or lab area of interest (for instance cell polarity,
microfluidics or a development paper in C. elegans is OK, evolution of gold fish maybe not).  You should try to think what would be
interesting for the group and what you want to share with the group --like some important advance relevant to your own project that
would be good for the group.<br />

Each presentation will be 8 minutes, with 3 minutes for questions, all of it controlled thoroughly with a timer!!<br />

<br />
It should include:<br />

- Title, main authors, Location, Journal<br />


- Background:  focus on the primary problem addressed.<br />

What was not known before?  What is important about this paper? What is the question/or the new technique? Why did you pick it?<br />

- Results:  focus on a couple of the most important findings/approaches<br />
You won't have time to go through all the results.<br />
To save time it is advised to prepare a ppt with 1-2 figures or movie but you may also draw on the board.<br />

- Discussion:  your evaluation; was this a good, believable, significant paper?  any limitations? brought up new ideas for your
project?
</p>
</div>
</div>
</section>
