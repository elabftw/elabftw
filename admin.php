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
/* admin.php - for administration of the elab */
require_once('inc/common.php');
if ($_SESSION['is_admin'] != 1) {die('You are not admin !');}
$page_title = 'Admin Panel';
require_once('inc/head.php');
require_once('inc/menu.php');
require_once('inc/info_box.php');
?>
<script type="text/javascript" src="js/tiny_mce/tiny_mce.js"></script>
<?php

// SQL to get all unvalidated users
$sql = "SELECT userid, lastname, firstname, email FROM users WHERE validated = 0";
$req = $bdd->prepare($sql);
$req->execute();
$count = $req->rowCount();
// only show the frame if there is some users to validate
if ($count > 0) {
    echo "
<section class='fail'>
<h3>USERS WAITING FOR VALIDATION</h3>";
echo "<form method='post' action='admin-exec.php'><ul>";
while ($data = $req->fetch()) {
    echo "<li><input type='checkbox' name='validate[]' value='".$data['userid']."'> ".$data['firstname']." ".$data['lastname']." (".$data['email'].")</li>";
}
echo "</ul><input type='submit' name='submit' value='Validate users' /></form>";
echo "</section>";
}
?>

<section class='item'>
<h3>TEAM MEMBERS</h3>
<?php
// TODO different colors for different groups
// SQL to get all users
$sql = "SELECT userid, lastname, firstname, email FROM users WHERE validated = 1";
$req = $bdd->prepare($sql);
$req->execute();
echo "<form method='post' action='admin-exec.php'><ul>";
while ($data = $req->fetch()) {
    echo "<li>".$data['firstname']." ".$data['lastname']." (".$data['email'].") :: "; //switch to html because of JS ?>
    <a href='admin-exec.php?deluser=<?php echo $data['userid'];?>' onClick="return confirm('Delete this user ?\n WARNING this will delete forever ALL the user\'s data, including files and experiments !!!!');">delete</a> 
<?php echo "<a href='admin-exec.php?edituser=".$data['userid']."'>edit</a></li>";
}
echo "</section>";
?>
<section class='item'>
<h3>NEW PLASMIDS DEFAULT TEMPLATE</h3>
<?php // SQL TO GET TEMPLATES
$sql = "SELECT id, body FROM plasmids_templates WHERE id = 1";
$req = $bdd->prepare($sql);
$req->execute();
$data = $req->fetch();
?>
    <form action='admin-exec.php' method='post'>
    <input type='hidden' name='pla_tpl' />
    <textarea class='mceditable' name='body' /><?php echo $data['body'];?></textarea>
    <div id='submitDiv'><input type="submit" name="Submit" class='submitbutton' value="Save changes" /></div>
    </form>
</section>

<script type='text/javascript'>
tinyMCE.init({
    theme : "advanced",
    mode : "specific_textareas",
    editor_selector : "mceditable",
    content_css : "css/tinymce.css",
    theme_advanced_font_sizes: "10px,12px,13px,14px,16px,18px,20px",
    font_size_style_values : "10px,12px,13px,14px,16px,18px,20px"
});
</script>
<?php
require_once('inc/footer.php') ?>
