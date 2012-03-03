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
session_start();
require_once('inc/connect.php');
// if user is not auth
if(!isset($_SESSION['auth'])){
// If user has a cookie
    if(isset($_COOKIE['token'])){
    // Get token from SQL
    $sql = "SELECT * FROM users WHERE token = :token";
    $result = $bdd->prepare($sql);
    $result->execute(array(
    'token' => $_COOKIE['token']));
    $data = $result->fetch();
    $numrows = $result->rowCount();
    if ($numrows == 1){
        // Store userid in $_SESSION
        session_regenerate_id();
        $_SESSION['auth'] = 1;
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
            'shortcuts' => array('create' => $data['sc_create'], 'edit' => $data['sc_edit'], 'submit' => $data['sc_submit']));
        session_write_close();
        }else{ // no token found in database
            $msg_arr = array();
            $msg_arr[] = 'You are not logged in !';
            $_SESSION['errors'] = $msg_arr;
            header("location: login.php");
        }
    }else{ // no cookie
        $msg_arr = array();
        $msg_arr[] = 'You are not logged in !';
        $_SESSION['errors'] = $msg_arr;
        header("location: login.php");
    }
}
?>
