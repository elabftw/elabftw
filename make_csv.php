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
require_once('inc/common.php');
require_once('inc/head.php');
$page_title='Make CSV';
require_once('inc/menu.php');
require_once('inc/info_box.php');

$list = array();
$list[] = array('id', 'date', 'type', 'title', 'rating');
// SQL
$sql = "SELECT * FROM items";
$req = $bdd->prepare($sql);
$req->execute();

while ($items = $req->fetch()) {
    $list[] = array($items['id'], $items['date'], $items['type'], $items['title'], $items['rating']);
}

$fp = fopen($ini_arr['upload_dir'].'database-export.csv', 'w+');
// utf8 headers
fwrite($fp,"\xEF\xBB\xBF");

foreach ($list as $fields) {
        fputcsv($fp, $fields);
}

fclose($fp);

    // Get zip size
    $filesize = filesize($ini_arr['upload_dir'].'database-export.csv');
    echo "<p>Download CSV file <span class='filesize'>(".format_bytes($filesize).")</span> :<br />
        <img src='themes/".$_SESSION['prefs']['theme']."/img/download.png' alt='' /> <a href='download.php?f=database-export.csv&name=osef.csv' target='_blank'>osef.csv</a></p>";
require_once('inc/footer.php');
