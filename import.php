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
$page_title = 'Import CSV';
require_once 'inc/head.php';
require_once 'inc/common.php';
require_once 'inc/menu.php';

$row = 0;
$inserted = 0;
$column = array();

// file upload block
// show select of type
// SQL to get items names
$sql = "SELECT * FROM items_types";
$req = $pdo->prepare($sql);
$req->execute();
?>
<p style='text-align:justify'>This page will allow you to import a .csv (Excel spreadsheet) file into the database.
Firt you need to open your (.xls/.xlsx) file in Excel or Libreoffice and save it as .csv.
In order to have a good import, the first column should be the title. The rest of the columns will be imported in the body. You can make a tiny import of 3 lines to see if everything works before you import a big file.
<b>You should make a backup of your database before importing thousands of items !</b></p>

<label for='item_selector'>1. Select a type of item to import to :</label>
<select id='item_selector' onchange='goNext(this.value)'><option value=''>--------</option>
<?php
while ($items_types = $req->fetch()) {
    echo "<option value='".$items_types['id']."' name='type' ";
    echo ">".$items_types['name']."</option>";
}
?>
</select><br>
<div id='import_block'>
<form enctype="multipart/form-data" action="import.php" method="POST">
    <label for='uploader'>2. Select a CSV file to import :</label>
    <input id='uploader' name="csvfile" type="file" />
    <br>
    <br>
    <div class='center'>
        <button type="submit" class='button' value="Upload">Import CSV</button>
    </div>
</form>
</div>
<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // open the file
    $handle = fopen($_FILES['csvfile']['tmp_name'], 'r');
    if ($handle == false) {
        die('Could not open the file.');
    }

    // get what type we want
    if (isset($_COOKIE['itemType']) && is_pos_int($_COOKIE['itemType'])) {
        $type = $_COOKIE['itemType'];
    }
    // loop the lines
    while ($data = fgetcsv($handle, 0, ",")) {
        $num = count($data);
        // get the column names (first line)
        if($row == 0) {
            for($i=0;$i < $num;$i++) {
                $column[] = $data[$i];
            }
            $row++;
            continue;
        }
        $row++;

        $title = $data[0];
        $body = '';
        $j = 0;
        foreach($data as $line) {
            $body .= "<p><b>".$column[$j]." :</b> ".$line.'</p>';
            $j++;
        }

        // SQL for importing
        $sql = "INSERT INTO items(team, title, date, body, userid, type) VALUES(:team, :title, :date, :body, :userid, :type)";
        $req = $pdo->prepare($sql);
        $result = $req->execute(array(
            'team' => $_SESSION['team_id'],
            'title' => $title,
            'date' => kdate(),
            'body' => $body,
            'userid' => $_SESSION['userid'],
            'type' => $type
        ));
        if ($result) {
            $inserted++;
        }
    }
    fclose($handle);
    echo "<script alert('"$inserted." items imported.')</script>";
}
?>
<script>
function goNext(x) {
    if(x == '') {
        return;
    }
    document.cookie = 'itemType='+x;
    $('#import_block').show();
}
$(document).ready(function() {
    $('#import_block').hide();
});
</script>
<?php
require_once 'inc/footer.php';
