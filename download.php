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
require_once('inc/common.php');

// Check type
if (isset($_GET['type']) && $_GET['type'] == 'zip') {
    $type = 'zip';
} else {
    $type = '';
}

// LONG_NAME
if (!isset($_GET['f']) || empty($_GET['f'])) {
  die('What are you doing, Dave ?');
}
// Nullbyte hack fix
if (strpos($_GET['f'], "\0") != false) die('What are you doing, Dave ?');
// Remove any path info to avoid hacking by adding relative path, etc.
$long_filename = basename($_GET['f']);

// REAL_NAME
if (!isset($_GET['name']) || empty($_GET['name'])) {
    $filename = $long_filename;
} else {
    // we redo a check for filename
    $filename = preg_replace('/[^A-Za-z0-9]/', '.', $_GET['name']);
    if ($filename === '') {
        $filename = 'unnamed_file';
    }
}

// FILE PATH
if ($type == 'zip') {
    $file_path = 'uploads/export/'.$long_filename;
} else {
    $file_path = 'uploads/'.$long_filename;
}

// MIME
if (function_exists('mime_content_type')) {
    $mtype = mime_content_type($file_path);
} else if (function_exists('finfo_file')) {
    $finfo = finfo_open(FILEINFO_MIME); // return mime type
    $mtype = finfo_file($finfo, $file_path);
    finfo_close($finfo);
}
if ($mtype == '') {
    $mtype = "application/force-download";
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
header("Content-Type: $mtype");
header("Content-Disposition: attachment; filename=\"$filename\"");
header("Content-Transfer-Encoding: binary");
header("Content-Length: " . $fsize);

// DOWNLOAD
// @readfile($file_path);
$file = @fopen($file_path,"rb");
if ($file) {
  while(!feof($file)) {
    print(fread($file, 1024*8));
    flush();
    if (connection_status()!=0) {
      @fclose($file);
      die();
    }
  }
  @fclose($file);
}

