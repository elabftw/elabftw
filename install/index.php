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
require_once('../inc/functions.php');
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
<link rel="stylesheet" media="all" href="../css/jquery-ui-1.10.3.custom.min.css" />
<style>
/* little gray text */
.install_hint {
    color:gray;
    font-size:12px;
    display:inline;
}
/* form validation */
.parsley-error {
    color:red;
    background-color:yellow;
}
.parsley-error-list {
    color:red;
    font-weight:bold;
}
</style>

<!-- JAVASCRIPT -->
<script src="../js/jquery-2.0.3.min.js"></script>
<script src="js/jquery-ui-1.10.3.custom.min.js"></script>
<!-- Form validation client-side -->
<script src="../js/parsley.min.js"></script>
</head>

<body>
<section id="container">
<!-- JAVASCRIPT -->
<?php // Page generation time
$time = microtime();
$time = explode(' ', $time);
$time = $time[1] + $time[0];
$start = $time;
$page_title='Install';

$ok = "<span style='color:green'>OK</span>";
$fail = "<span style='color:red'>FAIL</span>";

function custom_die() {
    echo "
    <br />
    <br />
    </section>
    <br />
    <br />
    <footer>
    <p>Thanks for using eLabFTW :)</p>
    </footer>
    </body>
    </html>";
    die();
}
?>

<section class='item'>
<center><img src='../img/logo.png' alt='elabftw' title='elabftw' /></center>
<h2>Welcome the the install of eLabFTW</h2>

<?php
// Check if there is already a config file, die if yes.

if(file_exists('../admin/config.php')) {
    $message = 'It looks like eLabFTW is already installed. Delete the config file if you wish to reinstall it.';
    display_message('error', $message);
    custom_die();
}
?>

<h4>Preliminary checks :</h4>
<br />
<br />
<?php
// UPLOADS DIR
if (is_writable('../uploads') && is_writable('../uploads/export') && is_writable('../uploads/tmp')) {
    $message = 'The <em>uploads/</em> folder and its subdirectories are here and I can write to it.';
    display_message('info', $message);
} else {
    // create the folders
    mkdir('../uploads');
    mkdir('../uploads/export');
    mkdir('../uploads/tmp');
    // check the folders
    if (is_writable('../uploads') && is_writable('../uploads/export') && is_writable('../uploads/tmp')) {
        $message = "The <em>uploads/</em> folder and its subdirectories were created successfully.";
        display_message('info', $message);
    } else { // failed at creating the folder
        $message = "Faild creating <em>uploads/</em> directory. 
            You need to do it manually. 
            <a style='color:blue; font-style:underline;' href='https://github.com/NicolasCARPi/elabftw/wiki/Troubleshooting#failed-creating-uploads-directory-'>Click here to discover how.</a>";
        display_message('error', $message);
        custom_die();
    }
}

// CHECK ssl extension
if (extension_loaded("openssl")) {
    $message = 'The <em>openssl</em> extension is loaded.';
    display_message('info', $message);
} else {
    $message = "The <em>openssl</em> extension is <strong>NOT</strong> loaded.
            <a style='color:blue; font-style:underline;' href='https://github.com/NicolasCARPi/elabftw/wiki/Troubleshooting#the-openssl-extension-is-not-loaded'>Click here to read how to fix this.</a>";
    display_message('error', $message);
    custom_die();
}

// CHECK gd extension
if (extension_loaded("gd")) {
    $message = 'The <em>gd</em> extension is loaded.';
    display_message('info', $message);
} else {
    $message = "The <em>gd</em> extension is <strong>NOT</strong> loaded.
            <a style='color:blue; font-style:underline;' href='https://github.com/NicolasCARPi/elabftw/wiki/Troubleshooting#the-gd-extension-is-not-loaded'>Click here to read how to fix this.</a>";
    display_message('error', $message);
    custom_die();
}
?>

<br />
<br />
<h4>Configuration :</h4>
<br />
<br />

<!-- MAIN FORM -->
<form data-validate='parsley' action='install-exec.php' method='post'>
<fieldset>
<legend><strong>Generalities</strong></legend>
<label for='lab_name'>The name of your lab:</label><br />
<input id='lab_name' name='lab_name' type='text' />
<br /><br />

<label for='admin_validate'>Disable new accounts:</label><br />
<input id='admin_validate' name='admin_validate' type='checkbox' checked='checked' />
<p class='install_hint'>(the admin can validate new users in the admin panel)</p>
<br /><br />
</fieldset>
<br />

<fieldset>
<legend><strong>MySQL</strong></legend>

<label for='db_host'>The host for mysql database:</label><br />
<input id='db_host' name='db_host' type='text' value='localhost' />
<p class='install_hint'>(you can safely leave 'localhost' here)</p>
<br /><br />

<label for='db_name'>The name of the database:</label><br />
<input id='db_name' name='db_name' type='text' value='elabftw' />
<p class='install_hint'>(should be 'elabftw' if you followed the README file)</p>
<br /><br />

<label for='db_user'>The username that will connect to the MySQL server:</label><br />
<input id='db_user' name='db_user' type='text' value='elabftw' />
<p class='install_hint'>(should be 'elabftw' if you followed the README file)</p>
<br /><br />

<label for='db_password'>The password associated</label><br />
<input id='db_password' name='db_password' type='password' />
<p class='install_hint'>(should be a very complicated one that you won't have to remember)</p>
<br /><br />
</fieldset>
<br />


<fieldset>
<legend><strong>Admin user creation</strong></legend>
<p>
<label for="firstname">Firstname:</label><br />
<input name="firstname" type="text" id="firstname" data-trigger="change" data-required="true" />
</p>
<p>
<label for="lastname">Lastname:</label><br />
<input name="lastname" type="text" id="lastname" data-trigger="change" data-required="true" />
</p>
<p>
<label for="username">Username:</label><br />
<input name="username" type="text" id="username" data-trigger="change" data-required="true" />
</p>
<p>
<label for="email">Email:</label><br />
<input name="email" type="email" id="email" data-trigger="change" data-required="true" data-type="email" />
</p>
<p>
<label for="password">Password:</label><br />
<input name="password" type="password" id="password" data-trigger="change" data-minlength="4" />
</p>
<p>
<label for="cpassword">Confirm password:</label><br />
<input name="cpassword" type="password" id="cpassword" data-trigger="change" data-equalto="#password" data-error-message="The passwords do not match !" />
</p>
Password complexity (for your information) : <span id="complexity">0%</span><br /><br />
<input type="submit" name="Submit" class='submit' value="Install eLabFTW" />
</fieldset>
</form>
</section>
<!-- end register form -->
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
<footer>
<p>Thanks for using eLabFTW :)</p>
</footer>
</section>
</body>
</html>

<?php
/*
// TRY TO CONNECT TO DATABASE
echo "<br />";
echo "[°] Connection to database...";
try
{
    $pdo_options[PDO::ATTR_ERRMODE] = PDO::ERRMODE_EXCEPTION;
    $bdd = new PDO('mysql:host='.DB_HOST.';dbname='.DB_NAME, DB_USER, DB_PASSWORD, $pdo_options);
}
catch(Exception $e)
{
    die('Error : '.$e->getMessage());
}
// check if user imported the structure
$sql = "SHOW TABLES LIKE 'users'";
$req = $bdd->prepare($sql);
$req->execute();
$res = $req->rowCount();
// users table is here
if ($res) {
    echo $ok;
} else { // no structure here
    die($fail. " You need to import the file install/elabftw.sql in your database !");
}
// END SQL CONNECT 

// CHECK PATH
echo "<br />";
echo "[°] Checking the path...";
// remove /install/index.php from the path
$should_be_path = substr(realpath(__FILE__), 0, -18);
if(PATH != $should_be_path) {
    die($fail." : Path is not the same ! Change its value in admin/config.php to <strong>".$should_be_path."</strong>");
} else {
    echo $ok;
}
// END PATH CHECK

/*
// CHECK ssl extension
    // TODO check if the emails work
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
 */

// Make an admin user
?>
