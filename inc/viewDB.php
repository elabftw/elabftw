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
$sql = "SELECT items.id AS itemid,
    experiments_links.id AS linkid,
    experiments_links.*,
    items.*,
    items_types.*,
    users.lastname,
    users.firstname
    FROM items
    LEFT JOIN experiments_links ON (experiments_links.link_id = items.id)
    LEFT JOIN items_types ON (items.type = items_types.id)
    LEFT JOIN users ON (items.userid = users.userid)
    WHERE items.id = :id";
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

?>
<!-- begin item view -->
<section class="box">

<span class='date_view'><img src='img/calendar.png' title='date' alt='Date :' /> <?php echo format_date($data['date']);?></span><br>
<?php
show_stars($data['rating']);
// buttons
echo "<a href='database.php?mode=edit&id=".$data['itemid']."'><img src='img/pen-blue.png' title='edit' alt='edit' /></a> 
<a href='duplicate_item.php?id=".$data['itemid']."&type=db'><img src='img/duplicate.png' title='duplicate item' alt='duplicate' /></a> 
<a href='make_pdf.php?id=".$data['itemid']."&type=items'><img src='img/pdf.png' title='make a pdf' alt='pdf' /></a> 
<a href='javascript:window.print()'><img src='img/print.png' title='Print this page' alt='Print' /></a> 
<a href='make_zip.php?id=".$data['itemid']."&type=items'><img src='img/zip.png' title='make a zip archive' alt='zip' /></a>
<a href='experiments.php?mode=show&related=".$data['itemid']."'><img src='img/link.png' alt='Linked experiments' title='Linked experiments' /></a> ";
// lock
if ($data['locked'] == 0) {
    echo "<a href='lock.php?id=".$data['itemid']."&action=lock&type=items'><img src='img/unlock.png' title='lock item' alt='lock' /></a>";
} else { // item is locked
    echo "<a href='lock.php?id=".$data['itemid']."&action=unlock&type=items'><img src='img/lock-gray.png' title='unlock item' alt='unlock' /></a>";
}
// _('Tags')
show_tags($id, 'items_tags');
// TITLE : click on it to go to edit mode
?>
<div
<?php
if($data['locked'] == 0) {
    echo " OnClick=\"document.location='database.php?mode=edit&id=".$data['itemid']."'\" ";
}
?>
class='title_view'>
<span style='color:#<?php echo $data['bgcolor'];?>'><?php echo $data['name'];?> </span>
    <?php echo stripslashes($data['title']);?>
</div>
<?php
// BODY (show only if not empty)
if ($data['body'] != '') {
    ?>
    <div
    <?php
    if($data['locked'] == 0) {
        echo " OnClick=\"document.location='database.php?mode=edit&id=".$data['itemid']."'\" ";
    }
    ?>
id='body_view' class='txt'><?php echo stripslashes($data['body'])?></div>
    <?php
}
// SHOW USER
echo _('Last modified by').' '.$data['firstname']." ".$data['lastname'].' '.ON.' '.format_date($data['date']);
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
