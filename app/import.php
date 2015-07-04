
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
require_once '../inc/common.php';

// CODE TO IMPORT CSV
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $_POST['type'] === 'csv') {
    $row = 0;
    $inserted = 0;
    $column = array();
    // open the file
    $handle = fopen($_FILES['csvfile']['tmp_name'], 'r');
    if ($handle == false) {
        die('Could not open the file.');
    }

    // get what type we want
    if (isset($_COOKIE['itemType']) && is_pos_int($_COOKIE['itemType'])) {
        $type = $_COOKIE['itemType'];
    } else {
        die('No cookies found');
    }
    // loop the lines
    while ($data = fgetcsv($handle, 0, ",")) {
        $num = count($data);
        // get the column names (first line)
        if ($row == 0) {
            for ($i = 0; $i < $num; $i++) {
                $column[] = $data[$i];
            }
            $row++;
            continue;
        }
        $row++;

        $title = $data[0];
        $body = '';
        $j = 0;
        foreach ($data as $line) {
            $body .= "<p><strong>" . $column[$j] . " :</strong> " . $line . '</p>';
            $j++;
        }
        // clean the body
        $body = str_replace('<p><strong> :</strong> </p>', '', $body);

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
    $msg_arr[] = $inserted . ' ' . _('items were imported successfully.');
    $_SESSION['infos'] = $msg_arr;
    header('Location: ../database.php');
    exit;
}
// END CODE TO IMPORT CSV

// CODE TO IMPORT ZIP
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $_POST['type'] === 'zip') {
    // it might take some time and we don't want to be cut in the middle, so set time_limit to ∞
    set_time_limit(0);
    // OPEN THE ARCHIVE
    $zip = new ZipArchive;
    if ($zip->open($_FILES['zipfile']['tmp_name']) && $zip->extractTo('../uploads/tmp/')) {
        // how many items do we have to import ?
        // we loop through all the entries
        for($i = 0;$i<20000;$i++) {
            // MANIFEST will always be the last entry
            if ($zip->getNameIndex($i) === 'MANIFEST') {
                break;
            }
            $dirs[] = dirname($zip->getNameIndex($i));
        }
        // we want to know how many unique item are in the zip
        $dirs = array_unique($dirs);

        // now for each folder, import the things
        foreach($dirs as $dir) {
            // we need to get title and body from the txt file
            $file = "../uploads/tmp/" . $dir . "/export.txt";
            $content = file_get_contents($file);
            $lines = explode("\n", $content);
            $title = $lines[0];
            $body = implode("\n", array_slice($lines, 1));

            // we need to attach files
            // TODO

            // get what type we want
            if (isset($_COOKIE['itemType']) && is_pos_int($_COOKIE['itemType'])) {
                $type = $_COOKIE['itemType'];
            } else {
                die('No cookies found');
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
            }
        $zip->close();

        if ($result) {
            $msg_arr[] = _("Zip imported successfully.");
            $_SESSION['infos'] = $msg_arr;
            header('Location: ../database.php');
        } else {
            $msg_arr[] = sprintf(_("There was an unexpected problem! Please %sopen an issue on GitHub%s if you think this is a bug.") . "<br>E#17", "<a href='https://github.com/elabftw/elabftw/issues/'>", "</a>");
            $_SESSION['errors'] = $msg_arr;
            header('Location: ../admin.php');
        }
    } else {
        $msg_arr[] = sprintf(_("There was an unexpected problem! Please %sopen an issue on GitHub%s if you think this is a bug.") . "<br>E#18", "<a href='https://github.com/elabftw/elabftw/issues/'>", "</a>");
        $_SESSION['errors'] = $msg_arr;
        header('Location: ../admin.php');
    }
}
// END CODE TO IMPORT ZIP
