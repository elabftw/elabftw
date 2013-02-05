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
?>
<!DOCTYPE HTML>
<html>
<head>
<meta http-equiv="Content-type" content="text/html;charset=UTF-8" />
<link rel="icon" type="image/ico" href="img/favicon.ico" />
<?php
// Random title
$ftw_arr = array();
// Lots of 'For The World' so the other ones appear more rarely
for($i=0; $i<200;$i++){
$ftw_arr[] = 'For The World';
}
// Now the fun ones
$ftw_arr[] = 'For Those Wondering';
$ftw_arr[] = 'For The Worms';
$ftw_arr[] = 'Forever Two Wheels';
$ftw_arr[] = 'Free The Wookies';
$ftw_arr[] = 'Forward The Word';
$ftw_arr[] = 'Forever Together Whenever';
$ftw_arr[] = 'Face The World';
$ftw_arr[] = 'Forget The World';
$ftw_arr[] = 'Free To Watch';
$ftw_arr[] = 'Feed The World';
$ftw_arr[] = 'Feel The Wind';
$ftw_arr[] = 'Feel The Wrath';
$ftw_arr[] = 'Fight To Win';
$ftw_arr[] = 'Find The Waldo';
$ftw_arr[] = 'Finding The Way';
$ftw_arr[] = 'Flying Training Wing';
$ftw_arr[] = 'Follow The Way';
$ftw_arr[] = 'For The Wii';
$ftw_arr[] = 'For The Win';
$ftw_arr[] = 'For The Wolf';
$ftw_arr[] = 'Free The Weed';
$ftw_arr[] = 'Free The Whales';
$ftw_arr[] = 'From The Wilderness';
$ftw_arr[] = 'Freedom To Work';
$ftw_arr[] = 'For The Warriors';
$ftw_arr[] = 'Full Time Workers';
$ftw_arr[] = 'Fabricated To Win';
$ftw_arr[] = 'Furiously Taunted Wookies';
$ftw_arr[] = 'Flash The Watch';
shuffle($ftw_arr);
$ftw = $ftw_arr[0]; 

echo "<title>".(isset($page_title)?$page_title:"Lab manager")." - eLab ".$ftw."</title>"?>
<meta name="author" content="Nicolas CARPi" />
<!-- CSS -->
<link rel="stylesheet" media="all" href="../css/main.css" />
<link id='maincss' rel='stylesheet' media='all' href='../themes/default/style.css' />
<link rel="stylesheet" media="all" href="../css/jquery-ui.css" />
<!-- JAVASCRIPT -->
<script src="../js/jquery-1.7.2.min.js"></script>
<script src="../js/jquery-ui-1.8.18.custom.min.js"></script>
</head>

<body>
<section id="container">
<!-- JAVASCRIPT -->
<script src="../js/jquery-1.7.2.min.js"></script>
<script src="../js/jquery-ui-1.8.18.custom.min.js"></script>
<?php // Page generation time
$time = microtime();
$time = explode(' ', $time);
$time = $time[1] + $time[0];
$start = $time;
$page_title='Install';

$ok = "<span style='color:green'>OK</span>";
$fail = "<span style='color:red'>FAIL</span>";

echo "<section class='item'>";
echo "<h2>Welcome the the install of eLabFTW</h2>";
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
<!-- WIP

<br />
[°] Ability to send emails...
<br />
<form name="setRootPassword" method="post" action="install-exec.php">
<input type='text' placeholder='enter email address' name='install_email' />
<input type='submit' value='send email' />
</form>


[°] Set root (administrator) password...
<form name="setRootPassword" method="post" action="install-exec.php">
    <input name="password" type="password" id="password" /><br />
    confirm
    <br />
    <input name="cpassword" type="password" id="cpassword" /><br />
    complexity : <span id="complexity">0%</span><br />
        <input type="submit" name="Submit" value="Set password" />
</form>
-->
<?php
/*
$sql = "SELECT * FROM users WHERE username = 'root'";
$req = $bdd->prepare($sql);
$req->execute();
$test = $req->fetch();
if($test['password'] == '8c744dc6b145df85c03655a678657bf3096ed7b6acd76d2bb27914069f544b07ad164ddf759db02d6bd6542fa4041a04b16060431cbc55d6814f12b048f43240') {
    echo "<h2>All good !</h2>";
    echo "<p><a href='../index.php'>Start working !</a></p>";
} else {
    echo "Please set root password";
}
 */
    echo "<h2>All good :)</h2>";
    echo "<h2><a href='../index.php'>Start working !</a></h2>";
?>
</section>
<script src="../js/jquery.complexify.min.js"></script>
<script>
$(document).ready(function() {
    // password complexity
    $("#password").complexify({}, function (valid, complexity){
        if (complexity < 30) {
            $('#complexity').css({'color':'red'});
        } else {
            $('#complexity').css({'color':'green'});
        }
        $("#complexity").html(Math.round(complexity) + '%');
    });
});
</script>
<?php require_once('inc/footer.php'); ?>
