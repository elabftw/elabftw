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

// open the file
$handle = fopen('strains.csv', 'r');
if ($handle == false) {
    die('Could not open the file.');
}
// show select of type
// SQL to get items names
$sql = "SELECT * FROM items_types";
$req = $pdo->prepare($sql);
$req->execute();
echo "<b>The import will start right after you selected the item type, so be careful, and do it once ! ;)</b><br>";
echo "<b>You should make a backup of your database before importing thousands of items !</b><br><br>";

echo "Select a type of item to import to :<select onchange=go_url(this.value)><option value=''>--------</option>";
while ($items_types = $req->fetch()) {
    echo "<option value='import.php?go=1&type=".$items_types['id']."' name='type' ";
    echo ">".$items_types['name']."</option>";
}
echo "</select>";

if (isset($_GET['type']) && is_pos_int($_GET['type'])) {
    $type = $_GET['type'];
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

    /*
    echo '<h3>'.$title.'</h3>';
    echo $body;
    echo '<hr>';
     */

    
    if (isset($_GET['go']) && $_GET['go'] == 1) {
        $sql = "INSERT INTO items(title, date, body, userid, type) VALUES(:title, :date, :body, :userid, :type)";
        $req = $pdo->prepare($sql);
        $result = $req->execute(array(
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
}
echo "<br>".$inserted." items imported.";
fclose($handle);

?>
<script>
function go_url(x) {
    if(x == '') {
        return;
    }
    location = x;
}
</script>
<?php
require_once 'inc/footer.php';
