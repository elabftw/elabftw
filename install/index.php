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
/* install/index.php to get an installation up and running */
session_start();

$ok = "<span style='color:green'>OK</span>";
$fail = "<span style='color:red'>FAIL</span>";

echo "<html><body><h1>Welcome the the install of eLabFTW</h1>";
// INI
echo "[°] Check for admin/config.ini file...";
if(file_exists('../admin/config.ini')) {
    $ini_arr = parse_ini_file('../admin/config.ini');
    if ($ini_arr['lab_name'] == 'YOURLABNAME') {
        die($fail." : Please edit admin/config.ini");
    }
    echo $ok;
} else {
        die($fail." : Please copy admin/config-example.ini to admin/config.ini and edit it.");
}

echo "<br />";

// UPLOADS DIR
echo "[°] Create uploads/ directory...";
if (!is_dir("../uploads")){
   if  (mkdir("../uploads", 0777)){
    echo $ok;
    }else{
        // TODO link to the FAQ
        die($fail." : Failed creating <em>uploads/</em> directory. Do it manually and chmod 777 it.");
    }
}else{
    echo $ok;
}

echo "<br />";

// TRY TO CONNECT TO DATABASE
echo "[°] Connection to database...";
try
{
$pdo_options[PDO::ATTR_ERRMODE] = PDO::ERRMODE_EXCEPTION;
$bdd = new PDO('mysql:host='.$ini_arr['db_host'].';dbname='.$ini_arr['db_name'], $ini_arr['db_user'], $ini_arr['db_password'], $pdo_options);
}
catch(Exception $e)
{
    die($fail." : Could not connect to the database. ERROR : ".$e);
}
$sql = "SELECT * FROM users";
$req = $bdd->prepare($sql);
$req->execute();
$test = $req->fetch();
if($test['userid']) {
    echo $ok;
} else {
    die($fail);
}
// END SQL CONNECT

?>
<h2>All good !</h2>
<p><a href='../index.php'>Start working !</a></p>
</body></html>
