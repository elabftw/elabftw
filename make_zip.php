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
require_once 'inc/common.php';
require_once 'inc/head.php';
$page_title = 'Make zip';
require_once 'inc/menu.php';
require_once 'inc/info_box.php';
// Test if there is zip
if (!class_exists('ZipArchive')) {
    die("<p>You are missing the ZipArchive class in php. Uncomment the line extension=zip.so in /etc/php/php.ini.</p>");
}

// Switch exp/items just for the table to search in sql requests
if ($_GET['type'] === 'experiments') {
    $table = 'experiments';
} elseif ($_GET['type'] === 'items') {
    $table = 'items';
} else {
    die('bad type');
}

// CREATE URL
$url = 'https://'.$_SERVER['SERVER_NAME'].':'.$_SERVER['SERVER_PORT'].$_SERVER['PHP_SELF'];

// Check id is valid and assign it to $id
if (isset($_GET['id']) && !empty($_GET['id'])) {
    $id_arr = explode(" ", $_GET['id']);
    // BEGIN ZIP
    // name of the downloadable file
    $zipname = kdate().".export.elabftw";

    $zipfile = 'uploads/export/'.$zipname."-".hash("sha512", uniqid(rand(), true)).".zip";

    $zip = new ZipArchive;
    $res = $zip->open($zipfile, ZipArchive::CREATE);
    if ($res === true) {
        foreach ($id_arr as $id) {
            // MAIN LOOP
            ////////////////

            // SQL to get info on the item we are zipping
            if ($table == 'experiments') {
                $sql = "SELECT * FROM experiments WHERE id = :id LIMIT 1";
            } else {
                $sql = "SELECT items.*,
                    items_types.name AS items_typesname
                    FROM items
                    LEFT JOIN items_types ON (items.type = items_types.id)
                    WHERE items.id = :id LIMIT 1";
            }
            $req = $pdo->prepare($sql);
            $req->bindParam(':id', $id, PDO::PARAM_INT);
            $req->execute();
            $zipped = $req->fetch();
            $title = stripslashes($zipped['title']);
            // make a title without special char for folder inside .zip
            $clean_title = preg_replace('/[^A-Za-z0-9]/', ' ', $title);
            $date = $zipped['date'];
            // name of the folder
            // folder begin with date for experiments
            if ($table == 'experiments') {
                $folder = $date."-".$clean_title;
            } else { // items
                $itemtype = $zipped['items_typesname'];
                $folder = $itemtype." - ".$clean_title;
            }
            $body = stripslashes($zipped['body']);
            $req->closeCursor();

            // SQL to get firstname + lastname
            $sql = "SELECT firstname,lastname FROM users WHERE userid = ".$_SESSION['userid'];
            $req = $pdo->prepare($sql);
            $req->execute();
            $users = $req->fetch();
                $firstname = $users['firstname'];
                $lastname = $users['lastname'];
            // SQL to get tags
            $sql = "SELECT tag FROM ".$table."_tags WHERE item_id = $id";
            $req = $pdo->prepare($sql);
            $req->execute();
            $tags = null;
            while ($data = $req->fetch()) {
                $tags .= stripslashes($data['tag']).' ';
            }

            // SQL to get filesattached (of the right type)
            $sql = "SELECT * FROM uploads WHERE item_id = :id AND type = :type";
            $req = $pdo->prepare($sql);
            $req->bindParam(':id', $id);
            $req->bindParam(':type', $table);
            $req->execute();
            $real_name = array();
            $long_name = array();
            $comment = array();
            while ($uploads = $req->fetch()) {
                $real_name[] = $uploads['real_name'];
                $long_name[] = $uploads['long_name'];
                $comment[] = $uploads['comment'];
            }

            // files attached ?
            $filenb = count($real_name);
            if ($filenb > 0) {
                for ($i=0; $i<$filenb; $i++) {
                    // add files to archive
                    $zip->addFile('uploads/'.$long_name[$i], $folder."/".$real_name[$i]);
                }
            }

            // add PDF to archive
            $pdfname = make_pdf($id, $table, 'uploads/export');
            $zip->addFile("uploads/export/".$pdfname, $folder."/".$pdfname);
            // add CSV file to archive
            $csvpath = make_unique_csv($id, $table);
            $zip->addFile($csvpath, $folder."/".$clean_title.".csv");
            // add the MANIFEST file that lists the files in archive
            $manifest = "";
            for ($i = 0; $i < $zip->numFiles; $i++) {
                $manifest .= $zip->getNameIndex($i)."\n";
            }
            $manifest = utf8_encode($manifest);
            $manifest = "\xEF\xBB\xBF".$manifest;
            $manifestpath = 'uploads/export/'.'manifest-'.uniqid();
            $tf = fopen($manifestpath, 'w+');
            fwrite($tf, $manifest);
            fclose($tf);
            $zip->addFile($manifestpath, $folder."/MANIFEST");

        } // end foreach
        // close the archive
        $zip->close();
        // cleanup
        unlink($manifestpath);
        unlink('uploads/export/'.$pdfname);
        unlink($csvpath);



        // PAGE BEGIN
        echo "<div class='item'>";
        // Get the title if there is only one experiment in the zip
        if (count($id_arr) === 1) {
            $zipname = $date."-".$clean_title;
        }
        // Display download link (with attribute type=zip for download.php)
        echo "<p>Your ZIP archive is ready :<br />
            <a href='download.php?f=".$zipfile."&name=".$zipname.".zip&type=zip' target='_blank'>
            <img src='themes/".$_SESSION['prefs']['theme']."/img/download.png' alt='download' /> 
            ".$zipname.".zip</a>
            <span class='filesize'>(".format_bytes(filesize($zipfile)).")</span></p>";
            echo "</div>";
    } else {
        echo 'Archive creation failed :(';
    }
    require_once 'inc/footer.php';
} else {
    die("The id parameter in the URL isn't a valid experiment ID");
}
