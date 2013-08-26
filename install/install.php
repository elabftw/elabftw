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
// install/install.php -- to set root password
$pdo_options[PDO::ATTR_ERRMODE] = PDO::ERRMODE_EXCEPTION;
$ini_arr = parse_ini_file('../admin/config.ini');
$bdd = new PDO('mysql:host='.$ini_arr['db_host'].';dbname='.$ini_arr['db_name'], $ini_arr['db_user'], $ini_arr['db_password'], $pdo_options);
// Check if root password is already changed
$sql = "SELECT password FROM users WHERE username = 'root'";
$req = $bdd->prepare($sql);
$req->execute();
$test = $req->fetch();
if($test['password'] != '8c744dc6b145df85c03655a678657bf3096ed7b6acd76d2bb27914069f544b07ad164ddf759db02d6bd6542fa4041a04b16060431cbc55d6814f12b048f43240') {
    die('root password already set');
} else { // set root password
    $password = filter_var($_POST['pass'], FILTER_SANITIZE_STRING);
    // Create salt
    $salt = hash("sha512", uniqid(rand(), true));
    // Create hash
    $passwordHash = hash("sha512", $salt.$password);
    $sql = "UPDATE users SET password = :password, salt = :salt WHERE username = 'root'";
    $req = $bdd->prepare($sql);
    $result = $req->execute(array(
        'password' => $passwordHash,
        'salt' => $salt
    ));
}

