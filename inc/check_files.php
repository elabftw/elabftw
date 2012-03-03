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
if (is_uploaded_file($_FILES['files']['tmp_name'][0])){
        $real_filenames = array();
        $long_filenames = array();
        $upload_directory = 'uploads/'; //set upload directory
        // count files uploaded
        $cnt = 0;
        for ($i = 0; $i < count($_FILES['files']['tmp_name']); $i++) {
            if (is_uploaded_file($_FILES['files']['tmp_name'][$i])){
                $cnt++;
            }
        }
        for ($i = 0; $i < $cnt; $i++) {
            if ($_FILES['files']['name'][$i] != ''){ //check if file field empty or not
                // Check file size
                if ($_FILES["files"]["size"][$i] > 20000000) {
                $errmsg_arr[] = "File is too big !";
                $errflag = true;
                }
                // Check for errors
                if ($_FILES["files"]["error"][$i] > 0) {
                $errmsg_arr[] = "Error in the file upload (<a href='http://www.php.net/manual/en/features.file-upload.errors.php'>error code : ".$_FILES['file']['error'][$i]."</a>)";
                $errflag = true;
                $cnt--;
                }
                // Create a clean filename : remplace all non letters/numbers by '.' (this way we don't lose the file extension)
                $real_filenames[] = preg_replace('/[^A-Za-z0-9]/', '.', $_FILES['files']['name'][$i]);
                // Create a unique long filename
                $long_filenames[] = hash("sha512", uniqid(rand(), TRUE));
            }
        } // end for each file loop
} // end if files are uploaded
