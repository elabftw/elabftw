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
/* install/install.php to get an installation up and running */
session_start();
require_once('../inc/functions.php');

// Check if there is already a config file, redirect to index if yes.
if (file_exists('../admin/config.php')) {
    header('Location: ../install/index.php');
    die();
}

// POST data
if (isset($_POST['db_host']) && !empty($_POST['db_host'])) {
    $db_host = $_POST['db_host'];
}

if (isset($_POST['db_name']) && !empty($_POST['db_name'])) {
    $db_name = $_POST['db_name'];
}

if (isset($_POST['db_user']) && !empty($_POST['db_user'])) {
    $db_user = $_POST['db_user'];
}

if (isset($_POST['db_password']) && !empty($_POST['db_password'])) {
    $db_password = $_POST['db_password'];
}

// BUILD CONFIG FILE

// the new file to write to
$config_file = '../admin/config.php';
// what we will write
$config = "<?php
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
// admin/config.php -- main configuration file for eLabFTW

/*
* Database settings
*/

// Host (generally localhost)
define('DB_HOST', '".$db_host."');

// Name of the database
define('DB_NAME', '".$db_name."');

// SQL username
define('DB_USER', '".$db_user."');

// SQL Password (the one you chose in phpmyadmin)
define('DB_PASSWORD', '".$db_password."');

";

// we try to write content to file and propose the file for download if we can't write to it

if (file_put_contents($config_file, $config)) {
    $infos_arr = array();
    $infos_arr[] = 'Congratulations, you successfully installed eLabFTW, now you need to <strong>register</strong> your account (you will have admin rights).';
    $_SESSION['infos'] = $infos_arr;
    header('Location: ../register.php');

} else {
	header('Content-Type: text/x-delimtext; name="config.php"');
	header('Content-disposition: attachment; filename=config.php');
    echo $config;
    exit();
}
?>

