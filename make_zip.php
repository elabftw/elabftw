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
require_once ELAB_ROOT . 'inc/locale.php';
$page_title = _('Make zip archive');
$selected_menu = null;
require_once ELAB_ROOT . 'inc/head.php';
require_once ELAB_ROOT . 'inc/info_box.php';
require_once ELAB_ROOT . 'vendor/autoload.php';
// Test if there is zip
if (!class_exists('ZipArchive')) {
    die("<p>You are missing the ZipArchive class in php. Uncomment the line extension=zip.so in /etc/php/php.ini.</p>");
}

// init some var
$zdate = '';
$clean_title = '';
$files_to_delete = array();

// Switch exp/items just for the table to search in sql requests
if ($_GET['type'] === 'experiments') {
    $table = 'experiments';
} elseif ($_GET['type'] === 'items') {
    $table = 'items';
} else {
    die(_("The type parameter is not valid."));
}

// CREATE URL
$url = 'https://' . $_SERVER['SERVER_NAME'] . ':' . $_SERVER['SERVER_PORT'] . $_SERVER['PHP_SELF'];

// Check id is valid and assign it to $id
if (isset($_GET['id']) && !empty($_GET['id'])) {
    $id_arr = explode(" ", $_GET['id']);
    // BEGIN ZIP
    $zipfile = 'uploads/export/' . hash("sha512", uniqid(rand(), true)) . ".zip";

    $zip = new ZipArchive;
    $res = $zip->open($zipfile, ZipArchive::CREATE);
    if ($res === true) {
        foreach ($id_arr as $id) {
            // MAIN LOOP
            ////////////////

            // SQL to get info on the item we are zipping
            if ($table == 'experiments') {
                $sql = "SELECT * FROM experiments WHERE id = :id LIMIT 1";
                $req = $pdo->prepare($sql);
                $req->bindParam(':id', $id, PDO::PARAM_INT);
                $req->execute();
                $zipped = $req->fetch();
                if ($zipped['userid'] != $_SESSION['userid']) {
                    die('Not your experiment!');
                }

            } else {
                $sql = "SELECT items.*,
                    items_types.name AS items_typesname
                    FROM items
                    LEFT JOIN items_types ON (items.type = items_types.id)
                    WHERE items.id = :id LIMIT 1";
                $req = $pdo->prepare($sql);
                $req->bindParam(':id', $id, PDO::PARAM_INT);
                $req->execute();
                $zipped = $req->fetch();
                if ($zipped['team'] != $_SESSION['team_id']) {
                    die('Not an item of your team!');
                }
            }

            $title = stripslashes($zipped['title']);
            // make a title without special char for folder inside .zip
            $clean_title = preg_replace('/[^A-Za-z0-9]/', '_', $title);
            $zdate = $zipped['date'];
            // name of the folder
            // folder begin with date for experiments
            if ($table == 'experiments') {
                $folder = $zdate . "-" . $clean_title;
            } else { // items
                $itemtype = $zipped['items_typesname'];
                $folder = $itemtype . " - " . $clean_title;
            }
            $body = stripslashes($zipped['body']);
            $req->closeCursor();

            // SQL to get firstname + lastname
            $sql = "SELECT firstname,lastname FROM users WHERE userid = " . $_SESSION['userid'];
            $req = $pdo->prepare($sql);
            $req->execute();
            $users = $req->fetch();
                $firstname = $users['firstname'];
                $lastname = $users['lastname'];
            // SQL to get tags
            $sql = "SELECT tag FROM " . $table . "_tags WHERE item_id = $id";
            $req = $pdo->prepare($sql);
            $req->execute();
            $tags = null;
            while ($data = $req->fetch()) {
                $tags .= stripslashes($data['tag']) . ' ';
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
                for ($i = 0; $i < $filenb; $i++) {
                    // add files to archive
                    $zip->addFile('uploads/' . $long_name[$i], $folder . "/" . $real_name[$i]);
                }
            }

            // add PDF to archive
            $pdf = new \Elabftw\Elabftw\MakePdf($id, $table);
            $mpdf = new mPDF();

            $mpdf->SetAuthor($pdf->author);
            $mpdf->SetTitle($pdf->title);
            $mpdf->SetSubject('eLabFTW pdf');
            $mpdf->SetKeywords($pdf->tags);
            $mpdf->SetCreator('www.elabftw.net');
            $mpdf->WriteHTML($pdf->content);
            $mpdf->Output($pdf->getPath(), 'F');
            $zip->addFile($pdf->getPath(), $folder . '/' . $pdf->getFileName());
            // add CSV file to archive
            $csvpath = make_unique_csv($id, $table);
            $zip->addFile($csvpath, $folder . "/" . $clean_title . ".csv");

            // add the export.txt file that is helpful for importing
            // first line is title, rest is body
            $txt = $title . "\n" . $body;
            // fix utf8
            $txt = utf8_encode($txt);
            $txtpath = 'uploads/export/txt-' . uniqid();
            $tf = fopen($txtpath, 'w+');
            fwrite($tf, $txt);
            fclose($tf);
            $zip->addFile($txtpath, $folder . "/export.txt");
            // add the path of the files to be deleted in the files_to_delete array
            // (csv, MANIFEST and pdf)
            $files_to_delete[] = $csvpath;
            $files_to_delete[] = $pdf->getPath();
            $files_to_delete[] = $txtpath;

        } // end foreach

        // add the MANIFEST file that lists the files in archive
        $manifest = "";
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $manifest .= $zip->getNameIndex($i) . "\n";
        }
        // add info about the creator + timestamp
        $manifest .= "\nZip archive created by " . $users['firstname'] . " " . $users['lastname'] . " on " . date('Y.m.d') . " at " . date('H:i:s') . ".\n";
        $manifest .= "~~~\neLabFTW - Free open source lab manager - http://www.elabftw.net\n";
        // fix utf8
        $manifest = utf8_encode($manifest);
        $manifest = "\xEF\xBB\xBF" . $manifest;
        $manifestpath = 'uploads/export/manifest-' . uniqid();
        $tf = fopen($manifestpath, 'w+');
        fwrite($tf, $manifest);
        fclose($tf);
        $zip->addFile($manifestpath, "MANIFEST");
        $files_to_delete[] = $manifestpath;

        // close the archive
        $zip->close();

        // now clean up
        // we need to do that after $zip->close() or it doesn't work
        foreach ($files_to_delete as $file) {
            unlink($file);
        }

        // PAGE BEGIN
        echo "<div class='well' style='margin-top:20px'>";
        // set the name of the archive
        // add the title if there is only one item
        if (count($id_arr) === 1) {
            $zipname = $zdate . "-" . $clean_title;
        } else {
            $zipname = kdate();
        }
        // Display download link (with attribute type=zip for download.php)
        echo "<p>" . _('Your ZIP archive is ready:') . "<br>
            <img src='img/download.png' alt='download' /> 
            <a href='app/download.php?f=".$zipfile . "&name=" . $zipname . ".elabftw.zip&type=zip' target='_blank'>
            ".$zipname . ".elabftw.zip</a>
            <span class='filesize'>(".format_bytes(filesize($zipfile)) . ")</span></p>";
    } else {
        echo sprintf(_("There was an unexpected problem! Please %sopen an issue on GitHub%s if you think this is a bug."), "<a href='https://github.com/elabftw/elabftw/issues/'>", "</a>");
    }
    echo "</div>";
    require_once 'inc/footer.php';
} else {
    die(_("The id parameter is not valid!"));
}
