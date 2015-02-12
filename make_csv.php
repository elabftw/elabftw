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

// Switch exp/items
if ($_GET['type'] === 'experiments') {
    $list[] = array('id', 'date', 'title', 'content', 'status', 'elabid', 'url');
    $table = 'experiments';
} elseif ($_GET['type'] === 'items') {
    $list[] = array('id', 'date', 'type', 'title', 'description', 'rating', 'url');
    $table = 'items';
} else {
    die('bad type');
}
// Check id is valid and assign it to $id
if (isset($_GET['id']) && !empty($_GET['id'])) {
    $id_arr = explode(" ", $_GET['id']);
    foreach ($id_arr as $id) {
        // MAIN LOOP
        ////////////////
        // SQL
        if ($table === 'experiments') {
            $sql = "SELECT experiments.*,
                status.name AS statusname
                FROM experiments
                LEFT JOIN status ON (experiments.status = status.id)
                WHERE experiments.id = $id";
        } else {
            $sql = "SELECT items.*,
                items_types.name AS typename
                FROM items
                LEFT JOIN items_types ON (items.type = items_types.id)
                WHERE items.id = $id";
        }
            
        $req = $pdo->prepare($sql);
        $req->execute();
        $csv_data = $req->fetch();

        if ($table === 'experiments') {
            // now let's get the URL so we can have a nice link in the csv
            $url = 'https://'.$_SERVER['SERVER_NAME'].':'.$_SERVER['SERVER_PORT'].$_SERVER['PHP_SELF'];
            $url = str_replace('make_csv.php', 'experiments.php', $url);
            $url .= "?mode=view&id=".$csv_data['id'];
            $list[] = array(
                $csv_data['id'],
                $csv_data['date'],
                htmlspecialchars_decode($csv_data['title'], ENT_QUOTES | ENT_COMPAT),
                html_entity_decode(strip_tags(htmlspecialchars_decode($csv_data['body'], ENT_QUOTES | ENT_COMPAT))),
                htmlspecialchars_decode($csv_data['statusname'], ENT_QUOTES | ENT_COMPAT),
                $csv_data['elabid'],
                $url
            );

        } else { // items
            // now let's get the URL so we can have a nice link in the csv
            $url = 'https://'.$_SERVER['SERVER_NAME'].':'.$_SERVER['SERVER_PORT'].$_SERVER['PHP_SELF'];
            $url = str_replace('make_csv.php', 'database.php', $url);
            $url .= "?mode=view&id=".$csv_data['id'];
            $list[] = array(
                $csv_data['id'],
                $csv_data['date'],
                htmlspecialchars_decode($csv_data['typename'], ENT_QUOTES | ENT_COMPAT),
                htmlspecialchars_decode($csv_data['title'], ENT_QUOTES | ENT_COMPAT),
                html_entity_decode(strip_tags(htmlspecialchars_decode($csv_data['body'], ENT_QUOTES | ENT_COMPAT))),
                $csv_data['rating'],
                $url
            );
        }
    } // end foreach
} else {
    die(_("The id parameter is not valid!"));
}


// make CSV file
$filename = hash("sha512", uniqid(rand(), true));
$filepath = 'uploads/'.$filename;

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
echo "<p>"._('Your CSV file is ready:')."<br>
        <a href='app/download.php?f=".$filepath."&name=elabftw-export.csv' target='_blank'>
        <img src='img/download.png' alt='download' /> 
        elabftw-export.csv</a>
        <span class='filesize'>(".format_bytes($filesize).")</span></p>";
echo "</div>";
require_once 'inc/footer.php';
