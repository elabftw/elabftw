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

if (isset($_POST['lab_name']) && !empty($_POST['lab_name'])) {
    // we need to remove double quotes
    $lab_name = str_replace('"', '', $_POST['lab_name']);
} else {
    $lab_name = 'elab';
}

if (isset($_POST['admin_validate']) && !empty($_POST['admin_validate'])) {
    if ($_POST['admin_validate'] == 'on') {
        $admin_validate = 1;
    }
} else {
        $admin_validate = 0;
}

if (isset($_POST['link_name']) && !empty($_POST['link_name'])) {
    $link_name = $_POST['link_name'];
}

if (isset($_POST['link_href']) && !empty($_POST['link_href'])) {
    $link_href = $_POST['link_href'];
}

if (isset($_POST['proxy']) && !empty($_POST['proxy'])) {
    $proxy = $_POST['proxy'];
} else {
    $proxy = '';
}

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

if (isset($_POST['smtp_address']) && !empty($_POST['smtp_address'])) {
    $smtp_address = $_POST['smtp_address'];
}

if (isset($_POST['smtp_port']) && !empty($_POST['smtp_port'])) {
    $smtp_port = $_POST['smtp_port'];
}

if (isset($_POST['smtp_encryption']) && !empty($_POST['smtp_encryption'])) {
    $smtp_encryption = $_POST['smtp_encryption'];
}

if (isset($_POST['smtp_username']) && !empty($_POST['smtp_username'])) {
    $smtp_username = $_POST['smtp_username'];
}

if (isset($_POST['smtp_password']) && !empty($_POST['smtp_password'])) {
    $smtp_password = $_POST['smtp_password'];
}

// PATH
// the path is needed for the cookies
// remove /install/install.php from the path
$path = substr(realpath(__FILE__), 0, -20);


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
* General settings
*/

// The name of the lab (shown in the footer)
define('LAB_NAME', \"".$lab_name."\");

// if set to 1, user account will need admin validation before being able to login
define('ADMIN_VALIDATE', ".$admin_validate.");

// the name of the custom link in menu
define('LINK_NAME', '".$link_name."');

// the URL of the custom link
define('LINK_HREF', '".$link_href."');

// the path of the install (absolute path) WITHOUT TRAILING SLASH
// on Windows it should be : 'C:<antislash>xampp<antislash>htdocs<antislash>elabftw'
// on GNU/Linux it might be : '/var/www/elabftw'
// on Mac OS X it might be : '/Applications/MAMP/htdocs'
define('PATH', '".$path."');

// change to true to activate debug mode
define('DEBUG', false);

// proxy setting (to get updates)
define('PROXY', '".$proxy."');


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


/*
* Email settings
* You can leave these settings for later, because for the moment, 
* they are only use when someone requests a new password.
* You can use a free gmail account for this, but you can also use your company's SMTP server.
*/

// SMTP server address
define('SMTP_ADDRESS', '".$smtp_address."');

// Port
define('SMTP_PORT', '".$smtp_port."');

// Can be 'tls' or 'ssl'
define('SMTP_ENCRYPTION', '".$smtp_encryption."');

// Username
define('SMTP_USERNAME', '".$smtp_username."');

// Password
define('SMTP_PASSWORD', '".$smtp_password."');

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

