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
$ftw = 'INSTALL - eLabFTW'; 

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


// UPLOADS DIR
echo "<br />";
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

// EXPORT DIR
echo "<br />";
echo "[°] Create uploads/export directory...";
if (!is_dir("../uploads/export")){
   if  (mkdir("../uploads/export", 0777)){
    echo $ok;
    }else{
        // TODO link to the FAQ
        die($fail." : Failed creating <em>uploads/export</em> directory. Do it manually and chmod 777 it.");
    }
}else{
    echo $ok;
}

// TRY TO CONNECT TO DATABASE
echo "<br />";
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
// check if user imported the structure
$sql = "SHOW TABLES LIKE 'users'";
$req = $bdd->prepare($sql);
$req->execute();
$res = $req->rowCount();
// users table is here
if ($res) {
    $sql = "SELECT * FROM users";
    $req = $bdd->prepare($sql);
    $result = $req->execute();
    $test = $req->fetch();
    if($test['userid']) {
        echo $ok;
    } else {
        die($fail);
    }
} else { // no structure here
    die($fail. " You need to import the file install/elabftw.sql in your database !");
}
// END SQL CONNECT

// CHECK PATH
echo "<br />";
echo "[°] Checking the path...";
// remove /install/index.php from the path
$should_be_path = substr(realpath(__FILE__), 0, -18);
if($ini_arr['path'] != $should_be_path) {
    die($fail." : Path is not the same ! Change its value in admin/config.ini to ".$should_be_path);
} else {
    echo $ok;
}
// END PATH CHECK

// CHECK ssl extension
echo "<br />";
echo "[°] Checking for ssl extension (to send emails)...";
if (!extension_loaded("openssl")) {
    die($fail." : Edit the php config file to enable this extension.");
} else {
    echo $ok;
}

// CHECK ssl extension
echo "<br />";
echo "[°] Sending test email to test@yopmail.com...";
require_once('../lib/swift_required.php');
$message = Swift_Message::newInstance()
// Give the message a subject
->setSubject('[eLabFTW] Email test successful !')
// Set the From address with an associative array
->setFrom(array('elabftw.net@gmail.com' => 'eLabFTW.net'))
// Set the To addresses with an associative array
->setTo(array('test@yopmail.com' => 'Dori'))
// Give it a body
->setBody('\o/');
$transport = Swift_SmtpTransport::newInstance($ini_arr['smtp_address'], $ini_arr['smtp_port'], $ini_arr['smtp_encryption'])
->setUsername($ini_arr['smtp_username'])
->setPassword($ini_arr['smtp_password']);
$mailer = Swift_Mailer::newInstance($transport);
$result = $mailer->send($message);
if ($result) {
    echo $ok;
} else {
    echo $fail." : Couldn't send email. Check your SMTP settings !";
}


// Check if root password need to be set (should be yes after fresh install)
echo "<span id='set_pass_div'>";
$sql = "SELECT * FROM users WHERE username = 'root'";
$req = $bdd->prepare($sql);
$req->execute();
$test = $req->fetch();
if($test['password'] != '8c744dc6b145df85c03655a678657bf3096ed7b6acd76d2bb27914069f544b07ad164ddf759db02d6bd6542fa4041a04b16060431cbc55d6814f12b048f43240') {
    echo "<h2>All good !</h2>";
    echo "<h2><a href='../index.php'>Start working !</a></h2>";
} else {
?>
    <!-- SET ROOT PASSWORD -->
    <br />
    [°] Set root (administrator) password...<br />
    [-] Password : 
    <input name="password" type="password" id="password" /><br />
    [-] Confirm : 
    <input name="cpassword" type="password" id="cpassword" class='inline' /><br />
    [-] Complexity : <span id="complexity">0%</span><br />
    [°] <a href='#' onClick="setRootPassword()">Set root password</a>
<?php
}
echo "</span>";
?>

</section>
<script src="../js/jquery.complexify.min.js"></script>
<script>
function setRootPassword(){
    var pass = $('#password').attr('value');
    // POST request
        var jqxhr = $.post('install.php', {
            pass:pass
        })
        // reload the tags list
        .success(function() {$("#set_pass_div").load("index.php #set_pass_div");
    // clear input field
    $("#password").val("");
    return false;
        })
}
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
<footer>
</footer>
</body>
</html>
