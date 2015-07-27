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
/* auth + connect + functions*/
if (!isset($_SESSION)) {
    session_start();
}

// check that the config file is here and readable
if (is_readable('config.php')) {
    require_once 'config.php';
} elseif (is_readable('../config.php')) {
    // we might be called from app folder
    require_once '../config.php';
} else {
    die("No readable config file found. Make sure the server has permissions to read it. Try :<br />
        <hr>
        chmod 644 config.php
        <hr>
        Or if eLabFTW is not yet installed, head to the <a href='install'>install folder</a><br>
        Or if you just did a git pull, run php update.php");
}

// check for maintenance mode
if (file_exists(ELAB_ROOT . 'maintenance')) {
    die('Maintenance mode is enabled. Check back later.');
}

require_once ELAB_ROOT . 'vendor/autoload.php';

// SQL CONNECT
try {
    $connector = new \Elabftw\Elabftw\Db();
    $pdo = $connector->connect();
} catch (Exception $e) {
    die('Error connecting to the database : ' . $e->getMessage());
}
// END SQL CONNECT

// require common stuff
require_once ELAB_ROOT . 'inc/functions.php';
require_once ELAB_ROOT . 'inc/locale.php';

// run the update script if we have the wrong schema version
$update = new \Elabftw\Elabftw\Update();

if (get_config('schema') < $update::REQUIRED_SCHEMA) {
    try {
        $_SESSION['infos'] = $update->runUpdateScript();
    } catch (Exception $e) {
        $_SESSION['errors'] = $e->getMessage();
    }
}

$user = new \Elabftw\Elabftw\User();

// pages where you don't need to be logged in
$nologin_arr = array('login.php', 'login-exec.php', 'register.php', 'register-exec.php', 'change-pass.php', 'app/reset.php');

if (!isset($_SESSION['auth']) && !in_array(basename($_SERVER['SCRIPT_FILENAME']), $nologin_arr)) {
    // try to login with the cookie
    if (!$user->loginWithCookie()) {
        // maybe we clicked an email link and we want to be redirected to the page upon successful login
        // so we store the url in a cookie expiring in 5 minutes to redirect to it after login
        if ((new \Elabftw\Elabftw\Tools)->usingSsl()) {
            $protocol = 'https';
        } else {
            $protocol = 'http';
        }
        $host = $_SERVER['HTTP_HOST'];
        $script = $_SERVER['SCRIPT_NAME'];
        $params = '?' . $_SERVER['QUERY_STRING'];
        $url = $protocol . '://' . $host . $script . $params;
        // remove trailing ? if there was no query string
        $url = rtrim($url, '?');

        setcookie('redirect', $url, time() + 300, '/', null, true, true);

        header('location: app/logout.php');
        exit;
    }
}
