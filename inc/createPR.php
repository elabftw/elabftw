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
<h2>ADD NEW PROTOCOL</h2>
<!-- begin createXP form -->
<section class='item'>
<form name="createPR" method="post" action="createPR-exec.php" enctype="multipart/form-data">
<img src='themes/<?php echo $_SESSION['prefs']['theme'];?>/img/tags.gif' alt='' />
<h4>Tags</h4><span class='smallgray'> (separated by spaces)</span><br />
      <textarea placeholder='immunofluorescence antibody actin microtubules' name='tags' id='tags' rows="1" cols="50"></textarea>
<br />
<br />
<h4>Title</h4>
      <textarea id='title' name='title' rows="1" cols="80"><?php if(!empty($_SESSION['errors'])){echo $_SESSION['title'];} ?></textarea>
<br />
<br />
<h4>Protocol</h4>
      <textarea id='title' name='body' rows="15" cols="80"><?php if(!empty($_SESSION['errors'])){echo $_SESSION['body'];} ?></textarea>
<?php
// FILE UPLOAD
require_once('inc/file_upload.php');
?>
</div>

</div>
<!-- SUBMIT BUTTON -->
<div class='center' id='submitdiv'>
<input type='image' src='themes/<?php echo $_SESSION['prefs']['theme'];?>/img/submit.png' name='Submit' value='Submit' onClick="this.form.submit();" />
</div>
</form>
<!-- end createPR form -->

<?php
// KEYBOARD SHORTCUTS
echo "<script type='text/javascript'>
document.getElementById('tags').focus();
key('".$_SESSION['prefs']['shortcuts']['submit']."', function(){document.forms['createXP'].submit()});
</script>";

// unset session variables
unset($_SESSION['errors']);
unset($_SESSION['title']);
unset($_SESSION['body']);
?>
