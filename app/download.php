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
namespace Elabftw\Elabftw;

use Exception;

// we disable errors to avoid having notice and warning polluting our file
error_reporting(E_ERROR);
require_once '../inc/common.php';

try {
    // Check for LONG_NAME
    if (!isset($_GET['f']) || empty($_GET['f'])) {
        throw new Exception('Missing parameter for download');
    }
    // Nullbyte hack fix
    if (strpos($_GET['f'], "\0") === true) {
        throw new Exception('Null byte detected');
    }

    // Remove any path info to avoid hacking by adding relative path, etc.
    $long_filename = basename($_GET['f']);

    // REAL_NAME
    if (!isset($_GET['name']) || empty($_GET['name'])) {
        $filename = $long_filename;
    } else {
        // we redo a check for filename
        // IMPORTANT
        // the replacing char needs to be a dot, so we keep the file extension at the end!
        $filename = preg_replace('/[^A-Za-z0-9]/', '.', $_GET['name']);
        if ($filename === '') {
            $filename = 'unnamed_file';
        }
    }

    // SET FILE PATH
    // the zip archives will be in the tmp folder
    if (isset($_GET['type']) && ($_GET['type'] === 'zip' || $_GET['type'] === 'csv')) {
        $file_path = ELAB_ROOT . 'uploads/tmp/' . $long_filename;
    } else {
        $file_path = ELAB_ROOT . 'uploads/' . $long_filename;
    }



    // MIME
    $mtype = "application/force-download";

    if (function_exists('mime_content_type')) {
        $mtype = mime_content_type($file_path);
    } elseif (function_exists('finfo_file')) {
        $finfo = finfo_open(FILEINFO_MIME); // return mime type
        $mtype = finfo_file($finfo, $file_path);
        finfo_close($finfo);
    }

    // Make sure program execution doesn't time out
    // Set maximum script execution time in seconds (0 means no limit)
    set_time_limit(0);

    // file size in bytes
    $fsize = filesize($file_path);

    // HEADERS
    header("Pragma: public");
    header("Expires: 0");
    header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
    header("Cache-Control: public");
    header("Content-Description: File Transfer");
    header("Content-Type: " . $mtype);
    header("Content-Disposition: attachment; filename=" . $filename);
    header("Content-Transfer-Encoding: binary");
    header("Content-Length: " . $fsize);

    // DOWNLOAD
    $file = @fopen($file_path, "rb");
    if ($file) {
        while (!feof($file)) {
            echo fread($file, 1024 * 8);
            flush();
            if (connection_status() != 0) {
                fclose($file);
            }
        }
        fclose($file);
    }

} catch (Exception $e) {
    $Logs = new Logs();
    $Logs->create('Error', $_SESSION['userid'], $e->getMessage());
    header('Location: ../experiments.php');
}
