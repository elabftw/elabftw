<?php
/********************************************************************************
*                                                                               *
*   Copyright 2012 Nicolas CARPi (nicolas.carpi@gmail.com)                      *
*   https://www.elabftw.net/                                                     *
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
use Defuse\Crypto\Key as Key;

try {
    /* install/index.php to get an installation up and running */
    session_start();
    require_once '../vendor/autoload.php';

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
        throw new Exception('Redirecting to install page');
    }

    // POST data
    if (isset($_POST['db_host']) && !empty($_POST['db_host'])) {
        $db_host = $_POST['db_host'];
    } else {
        throw new Exception('Bad POST data');
    }

    if (isset($_POST['db_name']) && !empty($_POST['db_name'])) {
        $db_name = $_POST['db_name'];
    } else {
        throw new Exception('Bad POST data');
    }

    if (isset($_POST['db_user']) && !empty($_POST['db_user'])) {
        $db_user = $_POST['db_user'];
    } else {
        throw new Exception('Bad POST data');
    }

    // the db pass can be empty on mac and windows install
    if (isset($_POST['db_password']) && !empty($_POST['db_password'])) {
        $db_password = $_POST['db_password'];
    }

    // BUILD CONFIG FILE

    // the new file to write to
    $config_file = '../config.php';
    $elab_root = substr(realpath(__FILE__), 0, -20) . '/';
    // make a new secret key
    $new_key = Key::createNewRandomKey();

    // what we will write in the file
    $config = "<?php
    define('DB_HOST', '" . $db_host . "');
    define('DB_NAME', '" . $db_name . "');
    define('DB_USER', '" . $db_user . "');
    define('DB_PASSWORD', '" . $db_password . "');
    define('ELAB_ROOT', '" . $elab_root . "');
    define('SECRET_KEY', '" . $new_key->saveToAsciiSafeString() . "');
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
        // redirect to install/index.php to import SQL structure
        header('Location: index.php');

    } else {
        header('Content-Type: text/x-delimtext; name="config.php"');
        header('Content-disposition: attachment; filename=config.php');
        echo $config;
    }
} catch (Exception $e) {
    echo displayMessage('Error: ' . $e->getMessage(), 'ko');
}
