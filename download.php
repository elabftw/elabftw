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

// Check ID
if (isset($_GET['id']) && is_pos_int($_GET['id'])) {
    $id = $_GET['id'];
} else {
    die('I need a file ID !');
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
$file_path = 'uploads/'.$long_filename;


//// Allowed extensions list in format 'extension' => 'mime type'
//// If myme type is set to empty string then script will try to detect mime type 
//// itself, which would only work if you have Mimetype or Fileinfo extensions
//// installed on server.
//$allowed_ext = array (
//
//  // archives
//  'zip' => 'application/zip',
//
//  // documents
//  'pdf' => 'application/pdf',
//  'doc' => 'application/msword',
//  'xls' => 'application/vnd.ms-excel',
//  'ppt' => 'application/vnd.ms-powerpoint',
//
//  // executables
//  'exe' => 'application/octet-stream',
//
//  // images
//  'gif' => 'image/gif',
//  'png' => 'image/png',
//  'jpg' => 'image/jpeg',
//  'jpeg' => 'image/jpeg',
//  'tif' => 'image/tiff',
//  'tiff' => 'image/tiff',
//
//  // audio
//  'mp3' => 'audio/mpeg',
//  'wav' => 'audio/x-wav',
//
//  // video
//  'mpeg' => 'video/mpeg',
//  'mpg' => 'video/mpeg',
//  'mpe' => 'video/mpeg',
//  'mov' => 'video/quicktime',
//  'avi' => 'video/x-msvideo'
//);

// file extension
//$ext = get_ext($filename);

// check if allowed extension
//if (!array_key_exists($ext, $allowed_ext)) {
//  die("Not an allowed file type.");
//}

// get mime type
//if ($allowed_ext[$ext] == '') {
//  $mtype = '';
  // mime type is not set, get from server settings
  if (function_exists('mime_content_type')) {
    $mtype = mime_content_type($file_path);
  }
  else if (function_exists('finfo_file')) {
    $finfo = finfo_open(FILEINFO_MIME); // return mime type
    $mtype = finfo_file($finfo, $file_path);
    finfo_close($finfo);
  }
  if ($mtype == '') {
    $mtype = "application/force-download";
  }// else {
  // get mime type defined by admin
  //$mtype = $allowed_ext[$ext];
//}

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
?>
