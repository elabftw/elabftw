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
session_start();
// TODO delete this block in a few updates
if (file_exists('admin/config.ini')) {
    $ini_arr = parse_ini_file('admin/config.ini');
    die("Please run the update script ! (it will transfer info from admin/config.ini to admin/config.php and delete the ini file)<br />
        <strong>cd ".$ini_arr['path']." && php update.php</strong><br />
        If you are on a mac, instead of 'php', do /Applications/MAMP/bin/php/php5.4.4/bin/php.<br />
            Otherwise it might not work.");
}

require_once('admin/config.php');
require_once('inc/functions.php');
// SQL CONNECT
try
{
    $pdo_options[PDO::ATTR_ERRMODE] = PDO::ERRMODE_EXCEPTION;
    $bdd = new PDO('mysql:host='.DB_HOST.';dbname='.DB_NAME, DB_USER, DB_PASSWORD, $pdo_options);
}
catch(Exception $e)
{
    die('Error : '.$e->getMessage());
}
// END SQL CONNECT

// AUTH
if (isset($_SESSION['auth'])){ // if user is auth, we check the cookie
    if (!isset($_COOKIE['path']) || ($_COOKIE['path'] != PATH) || ($_SESSION['path'] != PATH)) { // no cookie for this domain
        session_destroy(); // kill session
        $msg_arr = array();
        $msg_arr[] = 'You are not logged in !';
        $_SESSION['errors'] = $msg_arr;
        header('Location: login.php');
    } 
} else { // user is not auth with php sessions 
    if (isset($_COOKIE['token']) && (strlen($_COOKIE['token']) == 32)) {
    // If user has a cookie; check cookie is valid
    $token = filter_var($_COOKIE['token'], FILTER_SANITIZE_STRING);
    // Get token from SQL
    $sql = "SELECT * FROM users WHERE token = :token";
    $result = $bdd->prepare($sql);
    $result->execute(array(
    'token' => $token
    ));
    $data = $result->fetch();
    $numrows = $result->rowCount();
    // Check cookie path vs. real install path
    if (($numrows == 1) && (PATH == $_COOKIE['path'])) { // token is valid
        session_regenerate_id();
        $_SESSION['auth'] = 1;
        // fix for cookies problem
        $_SESSION['path'] = PATH;
        $_SESSION['userid'] = $data['userid'];
        // Used in the menu
        $_SESSION['username'] = $data['username'];
        $_SESSION['is_admin'] = $data['is_admin'];
        // PREFS
        $_SESSION['prefs'] = array('theme' => $data['theme'], 
        'display' => $data['display'], 
        'order' => $data['order_by'], 
        'sort' => $data['sort_by'], 
        'limit' => $data['limit_nb'], 
        'shortcuts' => array('create' => $data['sc_create'], 'edit' => $data['sc_edit'], 'submit' => $data['sc_submit'], 'todo' => $data['sc_todo']));
        session_write_close();
    } else { // no token found in database
        $msg_arr = array();
        $msg_arr[] = 'You are not logged in !';
        $_SESSION['errors'] = $msg_arr;
        header("location: login.php");
    }
    } else { // no cookie
        $msg_arr = array();
        $msg_arr[] = 'You are not logged in !';
        $_SESSION['errors'] = $msg_arr;
        header("location: login.php");
    }
}

