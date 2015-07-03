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
/* make_csv.php -- export database in spreadsheet file */
require_once 'inc/common.php';
require_once 'inc/locale.php';
$page_title = _('Export to spreadsheet');
$selected_menu = null;
require_once 'inc/head.php';
require_once 'inc/info_box.php';

// this is the lines in the csv file
$list = array();

// Here we populate the first row: it will be the column names
if ($_GET['type'] === 'experiments') {
    $list[] = array('id', 'date', 'title', 'content', 'status', 'elabid', 'url');
    $table = 'experiments';
} elseif ($_GET['type'] === 'items') {
    $list[] = array('title', 'description', 'id', 'date', 'type', 'rating', 'url');
    $table = 'items';
} else {
        die(sprintf(_("There was an unexpected problem! Please %sopen an issue on GitHub%s if you think this is a bug.") . "<br>Bad type", "<a href='https://github.com/elabftw/elabftw/issues/'>", "</a>"));
}
// loop through the id
if (isset($_GET['id']) && !empty($_GET['id'])) {
    $id_arr = explode(" ", $_GET['id']);

    foreach ($id_arr as $id) {
        // check the quality of id parameter here
        if (!is_pos_int($id)) {
            die(sprintf(_("There was an unexpected problem! Please %sopen an issue on GitHub%s if you think this is a bug.") . "<br>" . _("The id parameter is not valid!"), "<a href='https://github.com/elabftw/elabftw/issues/'>", "</a>"));
        }
        $list[] = make_unique_csv($id, $table, false);
    }

} else {
        die(sprintf(_("There was an unexpected problem! Please %sopen an issue on GitHub%s if you think this is a bug.") . "<br>" . _("The id parameter is not valid!"), "<a href='https://github.com/elabftw/elabftw/issues/'>", "</a>"));
}

// make CSV file
$filename = hash("sha512", uniqid(rand(), true));
$filepath = 'uploads/' . $filename;

$fp = fopen($filepath, 'w+');
// utf8 headers
fwrite($fp, "\xEF\xBB\xBF");

foreach ($list as $fields) {
        fputcsv($fp, $fields);
}

fclose($fp);

// PAGE BEGIN
echo "<div class='well' style='margin-top:20px'>";
    // Get csv file size
    $filesize = filesize($filepath);
echo "<p>" . _('Your CSV file is ready:') . "<br>
        <a href='app/download.php?f=" . $filepath . "&name=elabftw-export.csv' target='_blank'>
        <img src='img/download.png' alt='download' /> elabftw-export.csv</a>
        <span class='filesize'>(" . format_bytes($filesize) . ")</span></p>";
echo "</div>";
require_once 'inc/footer.php';
