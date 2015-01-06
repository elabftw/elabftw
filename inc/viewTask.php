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
// inc/viewDB.php
// ID
if (isset($_GET['id']) && !empty($_GET['id']) && is_pos_int($_GET['id'])) {
    $id = $_GET['id'];
} else {
    display_message('error', _("The id parameter is not valid!"));
    require_once 'inc/footer.php';
    exit;
}

// SQL FOR VIEWDB
$sql = "SELECT * FROM tasks WHERE id = :id";
$req = $pdo->prepare($sql);
$req->bindParam(':id', $id, PDO::PARAM_INT);
$req->execute();
// got results ?
$row_count = $req->rowCount();
if ($row_count === 0) {
    display_message('error', _('Nothing to show with this ID.'));
    require_once 'inc/footer.php';
    exit;
}

$data = $req->fetch();

$sql = "SELECT * FROM users WHERE userid = :userid";
$req = $pdo->prepare($sql);
$req->bindParam(':userid', $data['assignedUser']);
$req->execute();

$assignedUser = $req->fetch();

$sql = "SELECT * FROM users WHERE userid = :userid";
$req = $pdo->prepare($sql);
$req->bindParam(':userid', $data['creator'], PDO::PARAM_INT);
$req->execute();

$creator = $req->fetch();

?>
<!-- begin item view -->
<section class="box">

<span class='date_view'><img src='img/calendar.png' title='date' alt='Date :' /> <?php echo format_date($data['datetime']);?></span><br>
<?php
//show_stars($data['rating']);
// buttons
echo "<a href='tasks.php?mode=edit&id=".$data['id']."'><img src='img/pen-blue.png' title='edit' alt='edit' /></a>
<a href='javascript:window.print()'><img src='img/print.png' title='Print this page' alt='Print' /></a>";

// _('Tags')
show_tags($id, 'items_tags');
// TITLE : click on it to go to edit mode
?>
<div OnClick=\"document.location='tasks.php?mode=edit&id=".$data['id']."'\" class='title_view'>
<span style='color:black'>
  <?php
  if($data['status'] === '1')
  {
    echo '<span class="align_right"><img src="img/check.png" /></span> ';
  }
  echo $data['title'];?> </span>
</div>
<?php
// BODY (show only if not empty)
if ($data['description'] != '') {
    echo "<div OnClick=\"document.location='tasks.php?mode=edit&id=".$data['id']."'\" ";

    ?>
id='body_view' class='txt'><?php echo stripslashes($data['description'])?></div>
    <?php
}
// SHOW USER
echo _('Created by').' '.$creator['firstname']." ".$creator['lastname'];
echo "</section>";
// DISPLAY FILES
require_once 'inc/display_file.php';
?>
<script>
// change title
$(document).ready(function() {
    // fix for the ' and "
    title = "<?php echo $data['title']; ?>".replace(/\&#39;/g, "'").replace(/\&#34;/g, "\"");
    document.title = title;
});
</script>
