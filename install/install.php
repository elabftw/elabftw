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
use PDO;

/* install/index.php to get an installation up and running */
session_start();
require_once '../vendor/autoload.php';
require_once '../inc/functions.php';

/* install/install.php to get an installation up and running */
/* this script will :
 * 1. read the sql infos given through POST
 * 2. import the SQL structure (+ default values)
 * 3. write the config file (or propose to download it)
 */

// we disable errors to avoid having notice and warning polluting our file
error_reporting(E_ERROR);

// Check if there is already a config file, redirect to index if yes.
if (file_exists('../config.php')) {
    header('Location: ../install/index.php');
    exit;
}

// POST data
if (isset($_POST['db_host']) && !empty($_POST['db_host'])) {
    $db_host = $_POST['db_host'];
} else {
    die('Bad POST data');
}

if (isset($_POST['db_name']) && !empty($_POST['db_name'])) {
    $db_name = $_POST['db_name'];
} else {
    die('Bad POST data');
}

if (isset($_POST['db_user']) && !empty($_POST['db_user'])) {
    $db_user = $_POST['db_user'];
} else {
    die('Bad POST data');
}

// the db pass can be empty on mac and windows install
if (isset($_POST['db_password']) && !empty($_POST['db_password'])) {
    $db_password = $_POST['db_password'];
}
// connect to DB
try {
    $pdo_options[PDO::ATTR_ERRMODE] = PDO::ERRMODE_EXCEPTION;
    $pdo = new PDO('mysql:host=' . $db_host . ';dbname=' . $db_name, $db_user, $db_password, $pdo_options);
} catch (Exception $e) {
    die('Error : ' . $e->getMessage());
}

// now import the structure
try {
    import_sql_structure();
} catch (Exception $e) {
    die('Error importing the SQL structure: ' . $e->getMessage());
}

// BUILD CONFIG FILE

// the new file to write to
$config_file = '../config.php';
$elab_root = substr(realpath(__FILE__), 0, -20) . '/';
// make a new secret key
try {
    $new_secret_key = \Defuse\Crypto\Crypto::CreateNewRandomKey();
} catch (Exception $e) {
    die($e->getMessage());
}

// what we will write in the file
$config = "<?php
define('DB_HOST', '" . $db_host . "');
define('DB_NAME', '" . $db_name . "');
define('DB_USER', '" . $db_user . "');
define('DB_PASSWORD', '" . $db_password . "');
define('ELAB_ROOT', '" . $elab_root . "');
define('SECRET_KEY', '" . \Defuse\Crypto\Crypto::binToHex($new_secret_key) . "');
";

// we try to write content to file and propose the file for download if we can't write to it
if (file_put_contents($config_file, $config)) {
    // it's cool, we managed to write the config file
    // let's put restricting permissions on it as discussed in #129
    if (is_writable($config_file)) {
        chmod($config_file, 0400);
    }
    $infos_arr = array();
    $infos_arr[] = 'Congratulations, you successfully installed eLabFTW, 
    now you need to <strong>register</strong> your account (you will have full admin rights).';
    $_SESSION['ok'] = $infos_arr;
    header('Location: ../register.php');

} else {
    header('Content-Type: text/x-delimtext; name="config.php"');
    header('Content-disposition: attachment; filename=config.php');
    echo $config;
}
