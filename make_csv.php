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
$page_title = _('Export to spreadsheet');
$selected_menu = null;
require_once 'inc/head.php';
require_once 'inc/info_box.php';

try {
    $csv = new \Elabftw\Elabftw\MakeCsv($_GET['id'], $_GET['type']);
} catch (Exception $e) {
    echo $e->getMessage();
    exit;
}

// PAGE BEGIN
echo "<div class='well' style='margin-top:20px'>";
echo "<p>" . _('Your CSV file is ready:') . "<br>
        <a href='app/download.php?type=csv&f=" . basename($csv->getFilePath()) . "&name=export.elabftw.csv' target='_blank'>
        <img src='img/download.png' alt='download' /> export.elabftw.csv</a>
        <span class='filesize'>(" . format_bytes(filesize($csv->getFilePath())) . ")</span></p>";
echo "</div>";
require_once 'inc/footer.php';
