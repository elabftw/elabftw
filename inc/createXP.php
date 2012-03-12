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
<h2>ADD NEW EXPERIMENT</h2>
<!-- begin createXP form -->
<section class='running'>
<form id="createXP" name="createXP" method="post" action="createXP-exec.php" enctype="multipart/form-data">
<img src='themes/<?php echo $_SESSION['prefs']['theme'];?>/img/tags.gif' alt='' />
<h4>Tags<h4><span class='smallgray'> (separated by spaces)</span><br />
      <textarea placeholder='spinning4 hela lifeact nocodazole siRNA Arp2/3' name='tags' id='tags' rows="1" cols="50"></textarea>
<br /><br />

<h4>Date</h4><span class='smallgray'> (date format : YYMMDD)</span><br />
<img src='themes/<?php echo $_SESSION['prefs']['theme'];?>/img/calendar.png' title='date' alt='Date :' /><input name='date' size='6' type='text' value='<?php echo kdate();?>' />

<br /><br />
<h4>Title</h4><br />
      <textarea id='title' name='title' rows="1" cols="80"><?php if(!empty($_SESSION['errors'])){echo $_SESSION['title'];} ?></textarea>
<br />
<br />
<h4>Experiment </h4>
<? // SQL to get user's templates
$sql = "SELECT id, name FROM experiments_templates WHERE userid = ".$_SESSION['userid'];
$req = $bdd->prepare($sql);
$req->execute();
echo "<select>";
echo "<option>Choose template</option>";
echo "<option disabled='disabled'>----------------</option>";
while ($data = $req->fetch()) {
    echo "<option class='template_link' id='".$data['id']."' value='".$data['name']."'>".$data['name']."</option>";
}
echo "</select>";
?>
<script type='text/javascript'>
$(".template_link").click(function() {
    $.get("load_tpl.php", {tpl_id: $(this).attr('id')}, 
        function(data) {
            $('#body_textarea').val(data);
        });    
    return false;
});
</script>

      <textarea id='body_textarea' name='body' rows="15" cols="80"><?php if(!empty($_SESSION['errors'])){echo $_SESSION['body'];} ?></textarea>
<br /><br />
<h4>Outcome</h4>
<select name="outcome">
<option value="running">Running</option>
<option value="success">Success</option>
<option value="redo">Need to be redone</option>
<option value="fail">Fail</option>
</select><br /><br />
<h4>Link to protocol</h4>
<select name="protocol">
<option value='None'>-- None --</option>
<?php
// SQL to get all protocols
$sql = "SELECT id, title FROM protocols ORDER BY title";
$req = $bdd->prepare($sql);
$req->execute();
while ($protdata = $req->fetch()) {
    // we limit the title size so it's not too large
    echo "<option value=".$protdata['id'].">".substr($protdata['title'], 0, 60)."</option>";
}
?>
</select><br /><br />

<?php
// FILE UPLOAD
require_once('inc/file_upload.php');
?>
</div>

</div>
<br />
<!-- SUBMIT BUTTON -->
<div class='center' id='submitdiv'>
<input type='image' src='themes/<?php echo $_SESSION['prefs']['theme'];?>/img/submit.png' name='Submit' value='Submit' onClick="this.form.submit();" />
</div>
</form>
<!-- end createXP form -->
</section>
<?php
// KEYBOARD SHORTCUTS
echo "<script type='text/javascript'>
document.getElementById('tags').focus();
key('".$_SESSION['prefs']['shortcuts']['submit']."', function(){document.forms['createXP'].submit()});
</script>";
// unset session variables
unset($_SESSION['errors']);
unset($_SESSION['title']);
unset($_SESSION['tags']);
unset($_SESSION['body']);
?>
