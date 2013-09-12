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

// this is the lines in the csv file
$list = array();

// Switch exp/items
if ($_GET['type'] === 'exp') {
    $list[] = array('id', 'date', 'title', 'status', 'elabid');
    $table = 'experiments';
} elseif ($_GET['type'] === 'items') {
    $list[] = array('id', 'date', 'type', 'title', 'rating');
    $table = 'items';
} else {
    die('bad type');
}
// Check id is valid and assign it to $id
if(isset($_GET['id']) && !empty($_GET['id'])) {
    $id_arr = explode(" ", $_GET['id']);
    foreach($id_arr as $id) {
        // MAIN LOOP
        ////////////////
        // SQL
        //$sql = "SELECT * FROM :table WHERE id = :id";
        $sql = "SELECT * FROM $table WHERE id = $id";
        $req = $bdd->prepare($sql);
        $req->execute();
        /*
        $req->execute(array(
            'table' => $table,
            'id' => $id
        ));
         */
        $csv_data = $req->fetch();

        if ($table === 'experiments') {
                $list[] = array($csv_data['id'], $csv_data['date'], $csv_data['title'], $csv_data['status'], $csv_data['elabid']);
        } else { // items
                $list[] = array($csv_data['id'], $csv_data['date'], $csv_data['type'], $csv_data['title'], $csv_data['rating']);
        }
    }
} else {
    die('No id to export :/');
}


// make CSV file
$filename = hash("sha512", uniqid(rand(), true));
$filepath = 'uploads/'.$filename;

$fp = fopen($filepath, 'w+');
// utf8 headers
fwrite($fp,"\xEF\xBB\xBF");

foreach ($list as $fields) {
        fputcsv($fp, $fields);
}

fclose($fp);

// PAGE BEGIN
echo "<div class='item'>";
    // Get zip size
    $filesize = filesize($filepath);
    echo "<p>Download CSV file <span class='filesize'>(".format_bytes($filesize).")</span> :<br />
        <img src='themes/".$_SESSION['prefs']['theme']."/img/download.png' alt='download' /> 
        <a href='download.php?f=".$filepath."&name=elabftw-export.csv' target='_blank'>elabftw-export.csv</a></p>";
echo "</div>";
require_once('inc/footer.php');

