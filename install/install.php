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
if (!isset($_SESSION)) { session_start(); }
require_once '../inc/functions.php';

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
// connect to DB
try
{
    $pdo_options[PDO::ATTR_ERRMODE] = PDO::ERRMODE_EXCEPTION;
    $pdo = new PDO('mysql:host='.$db_host.';dbname='.$db_name, $db_user, $db_password, $pdo_options);
}
catch(Exception $e)
{
    die('Error : '.$e->getMessage());
}
// Populate config table with default values

// remove /install/install.php from path
$path = substr(realpath(__FILE__), 0, -20);

$sql = "INSERT INTO config (conf_name, conf_value) VALUES
    ('lab_name', 'eLab'),
    ('path', '$path'),
    ('admin_validate', '0'),
    ('link_name', 'Wiki'),
    ('link_href', 'https://github.com/NicolasCARPi/elabftw/wiki'),
    ('smtp_address', '173.194.66.108'),
    ('smtp_port', '587'),
    ('smtp_encryption', 'tls'),
    ('smtp_username', 'username@gmail.com'),
    ('smtp_password', 'gmail password'),
    ('proxy', ''),
    ('debug', '0'),
    ('deletable_xp', '1'),
    ('login_tries', '5'),
    ('ban_time', '60');";
$req = $pdo->prepare($sql);
$req->execute();

// BUILD CONFIG FILE

// the new file to write to
$config_file = '../admin/config.php';
// what we will write
$config = "<?php
define('DB_HOST', '".$db_host."');
define('DB_NAME', '".$db_name."');
define('DB_USER', '".$db_user."');
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

