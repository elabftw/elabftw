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
    require_once 'inc/functions.php';
} elseif (is_readable('../config.php')) {
    // we might be called from app folder
    require_once '../config.php';
    require_once '../inc/functions.php';
} else {

    die("No readable config file found. Make sure the server has permissions to read it. Try :<br />
        <hr>
        chmod 644 config.php
        <hr>
        Or if eLabFTW is not yet installed, head to the <a href='install'>install folder</a><br>
        Or if you just did a git pull, run php update.php");
}
// SQL CONNECT
try {
    $pdo_options = array();
    $pdo_options[PDO::ATTR_ERRMODE] = PDO::ERRMODE_EXCEPTION;
    $pdo_options[PDO::ATTR_PERSISTENT] = true;
    $pdo = new PDO('mysql:host='.DB_HOST.';dbname='.DB_NAME, DB_USER, DB_PASSWORD, $pdo_options);
} catch (Exception $e) {
    die('Error connecting to the database : '.$e->getMessage());
}
// END SQL CONNECT

// AUTH
if (!isset($_SESSION['auth'])) { // user is not auth with php sessions
    if (isset($_COOKIE['token']) && (strlen($_COOKIE['token']) == 32)) {
        // If user has a cookie; check cookie is valid
        $token = filter_var($_COOKIE['token'], FILTER_SANITIZE_STRING);
        // Get token from SQL
        $sql = "SELECT * FROM users WHERE token = :token LIMIT 1";
        $result = $pdo->prepare($sql);
        $result->execute(array(
            'token' => $token
        ));
        $users = $result->fetch();
        if ($result->rowCount() == 1) { // token is valid
            session_regenerate_id();
            $_SESSION['auth'] = 1;
            $_SESSION['userid'] = $users['userid'];
            $_SESSION['team_id'] = $users['team'];
            // Used in the menu
            $_SESSION['username'] = $users['username'];
            // load permissions
            $perm_sql = "SELECT * FROM groups WHERE group_id = :group_id LIMIT 1";
            $perm_req = $pdo->prepare($perm_sql);
            $perm_req->bindParam(':group_id', $users['usergroup']);
            $perm_req->execute();
            $group = $perm_req->fetch(PDO::FETCH_ASSOC);

            $_SESSION['is_admin'] = $group['is_admin'];
            $_SESSION['is_sysadmin'] = $group['is_sysadmin'];
            // PREFS
            $_SESSION['prefs'] = array(
            'display' => $users['display'],
            'order' => $users['order_by'],
            'sort' => $users['sort_by'],
            'limit' => $users['limit_nb'],
            'close_warning' => intval($users['close_warning']),
            'shortcuts' => array(
                'create' => $users['sc_create'],
                'edit' => $users['sc_edit'],
                'submit' => $users['sc_submit'],
                'todo' => $users['sc_todo']),
            'lang' => $users['lang']);
            session_write_close();
        } else { // no token found in database
            header("location: login.php");
            exit;
        }
    } else { // no cookie
        header('location: login.php');
        exit;
    }
}
