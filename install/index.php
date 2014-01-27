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
<link rel="icon" type="image/ico" href="../img/favicon.ico" />
<title>eLabFTW - INSTALL</title>
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
<script src="../js/jquery-2.1.0.min.js"></script>
<script src="../js/jquery-ui-1.10.3.custom.min.js"></script>
<!-- Form validation client-side -->
<script src="../js/parsley.min.js"></script>
</head>

<body>
<section id="container">
<?php
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
<h2>Welcome to the install of eLabFTW</h2>

<?php
// Check if there is already a config file

if(file_exists('../admin/config.php')) {
    // ok there is a config file, but maybe it's a fresh install, so redirect to the register page
    // check that the config file is here and readable
    if (!is_readable('../admin/config.php')) {
        $message = "No readable config file found. Make sure the server has permissions to read it. Try :<br />
            chmod 644 admin/config.php<br />";
        display_message('error', $message);
        custom_die();
    }

    // check if there are users registered
    require_once('../admin/config.php');
    try
    {
        $pdo_options[PDO::ATTR_ERRMODE] = PDO::ERRMODE_EXCEPTION;
        $bdd = new PDO('mysql:host='.DB_HOST.';dbname='.DB_NAME, DB_USER, DB_PASSWORD, $pdo_options);
    }
    catch(Exception $e)
    {
        die('Error : '.$e->getMessage());
    }
    $sql = "SELECT * FROM users";
    $req = $bdd->prepare($sql);
    $req->execute();
    $users_count = $req->rowCount();
    // redirect to register page if no users are in the database
    if ($users_count === 0) {
        header('Location: ../register.php');
    } else {
        $message = 'It looks like eLabFTW is already installed. Delete the config file if you wish to reinstall it.';
        display_message('error', $message);
        custom_die();
    }
}
?>

<h4>Preliminary checks</h4>
<br />
<br />
<?php
// CHECK WE ARE WITH HTTPS
if (!isset($_SERVER['HTTPS'])) {
    $message = "Please enable HTTPS on the server and access eLabFTW through HTTPS.";
    display_message('error', $message);
    custom_die();
}

// CHECK PHP version
if (!function_exists('version_compare') || version_compare(PHP_VERSION, '5.3', '<')) {
    $message = "Your version of PHP isn't recent enough. Please update your php version to at least 5.3";
    display_message('error', $message);
    custom_die();
} else {
    $message = "Your version of PHP is recent enough.";
    display_message('info', $message);
}

// Check for hash function
if (!function_exists('hash')) {
    $message = "You don't have the hash function. On Freebsd it's in /usr/ports/security/php5-hash.";
    display_message('error', $message);
    custom_die();
}


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
<h4>Configuration</h4>
<br />
<br />

<!-- MAIN FORM -->
<form data-validate='parsley' action='install.php' method='post'>
<fieldset>
<legend><strong>Generalities</strong></legend>
<p>This part deals with some configuration aspects of your eLabFTW installation.</p>

<p>
<label for='lab_name'>The name of your lab:</label><br />
<input id='lab_name' name='lab_name' type='text' />
<span class='install_hint'>(will be visible in the footer)</span>
</p>

<p>
<label for='admin_validate'>New accounts need validation:</label><br />
<input id='admin_validate' name='admin_validate' type='checkbox' checked='checked' />
<span class='install_hint'>(the admin can validate new users in the admin panel)</span>
</p>

<p>
<label for='link_name'>Name of the custom link in the menu:</label><br />
<input id='link_name' name='link_name' type='text' value='Wiki' />
<span class='install_hint'>(this link is visible in the main menu, it can be anything)</span>
</p>

<p>
<label for='link_href'>URL of the custom link:</label><br />
<input id='link_href' name='link_href' type='text' value='https://github.com/NicolasCARPi/elabftw/wiki' />
<span class='install_hint'>(the default URL is the wiki of eLabFTW, but you should put your own wiki)</span>
</p>

<p>
<label for='proxy'>Proxy settings:</label><br />
<input id='proxy' name='proxy' type='text' />
<span class='install_hint'>(if you are behind a proxy, write it like this : http://proxy.example.com:3128)</span>
</p>

</fieldset>

<br />

<!-- MYSQL -->
<fieldset>
<legend><strong>MySQL</strong></legend>
<p>MySQL is the database that will store everything. eLabFTW need to connect to it with a username/password. This is <strong>NOT</strong> your account with which you'll use eLabFTW. If you followed the README you should have created a database <em>elabftw</em> with a user <em>elabftw</em> that have all the rights on it.</p>

<p>
<label for='db_host'>Host for mysql database:</label><br />
<input id='db_host' name='db_host' type='text' value='localhost' />
<span class='install_hint'>(you can safely leave 'localhost' here)</span>
</p>

<p>
<label for='db_name'>Name of the database:</label><br />
<input id='db_name' name='db_name' type='text' value='elabftw' />
<span class='install_hint'>(should be 'elabftw' if you followed the README file)</span>
</p>

<p>
<label for='db_user'>Username to connect to the MySQL server:</label><br />
<input id='db_user' name='db_user' type='text' value='elabftw' />
<span class='install_hint'>(should be 'elabftw' if you followed the README file)</span>
</p>

<p>
<label for='db_password'>Password:</label><br />
<input id='db_password' name='db_password' type='password' />
<span class='install_hint'>(should be a very complicated one that you won't have to remember)</span>
</p>

<div class='center' style='margin-top:8px'>
<button type='button' id='test_sql_button' class='button'>Test MySQL connection to continue</button>
</div>

</fieldset>

<br />

<!-- EMAIL -->
<section id='email_section'>
<fieldset>
<legend><strong>Email settings</strong></legend>
<p>This part is about the SMTP settings. eLabFTW will need to be able to send out emails for password resets.</p>

<p>
<label for='smtp_address'>Address of the smtp server:</label><br />
<input id='smtp_address' name='smtp_address' type='text' value='smtp.gmail.com' />
<span class='install_hint'>(you can use your company's SMTP server here)</span>
</p>

<p>
<label for='smtp_port'>SMTP port:</label><br />
<input id='smtp_port' name='smtp_port' type='text' value='587' />
<span class='install_hint'>(587 is the default port)</span>
</p>

<p>
<label for='smtp_encryption'>SMTP encryption:</label><br />
<input id='smtp_encryption' name='smtp_encryption' type='text' value='tls' />
<span class='install_hint'>(can be 'tls' or 'ssl', leave 'tls' if unsure)</span>
</p>

<p>
<label for='smtp_username'>Username to connect to the SMTP server:</label><br />
<input id='smtp_username' name='smtp_username' type='text' value='username@gmail.com' />
<span class='install_hint'>(you need to keep the @gmail.com if you use gmail's smtp)</span>
</p>

<p>
<label for='smtp_password'>Password:</label><br />
<input id='smtp_password' name='smtp_password' type='password' />
<span class='install_hint'>(this is the password for the SMTP account)</span>
</p>

<div class='center' style='margin-top:8px'>
<button type='button' id='test_email_button' class='button'>Test email parameters</button>
</div>

<div class='center' style='margin-top:8px'>
or 
<button type='button' id='skip_email_button' class='button'>Skip this step</button>
</div>

</fieldset>
</section>

<br />

<!-- FINAL SECTION -->
<section id='final_section'>
<p>When you click the button below, it will create the file <em>admin/config.php</em>. If it cannot create it (because the server doesn't have write permission to this folder), your browser will download it and you will need to put it in the folder <em>admin</em>.</p>
<p>To put this file on the server, you can use scp (don't write the '$') :</p>
<p class='code'>$ scp /path/to/config.php pi@12.34.56.78:/var/www/elabftw/admin</p>
<p>If you want to modify some parameters afterwards, just edit this file directly.</p>

<div class='center' style='margin-top:8px'>
    <input type="submit" name="Submit" class='button' value="INSTALL eLabFTW" />
</div>

<p>If the config.php file is in place, <button onclick='window.location.reload()'>reload this page</button></p>
<p>You will be redirected to the registration page, where you can get your admin account :)</p>
</section>

</form>

</section>

<footer>
    <p>Thanks for using eLabFTW :)</p>
</footer>
</section>

<script>
$(document).ready(function() {
    // hide the email part
    $('#email_section').hide();
    // hide the install button
    $('#final_section').hide();

    // sql test button
    $('#test_sql_button').click(function() {
        var mysql_host = $('#db_host').val();
        var mysql_name = $('#db_name').val();
        var mysql_user = $('#db_user').val();
        var mysql_password = $('#db_password').val();

        $.post('test.php', {
            mysql: 1,
            db_host: mysql_host,
            db_name: mysql_name,
            db_user: mysql_user,
            db_password: mysql_password
        }).done(function(test_result) {
            if (test_result == 1) {
                alert('MySQL connection was successful ! :)');
                $('#email_section').show();
                $('#test_sql_button').hide();
            } else {
                alert('The connection failed with this error : ' + test_result);
            }
        });
    });

    // email test button
    $('#test_email_button').click(function() {
        var email_address = $('#smtp_address').val();
        var email_port = $('#smtp_port').val();
        var email_encryption = $('#smtp_encryption').val();
        var email_username = $('#smtp_username').val();
        var email_password = $('#smtp_password').val();

        $.post('test.php', {
            email: 1,
            smtp_address: email_address,
            smtp_port: email_port,
            smtp_encryption: email_encryption,
            smtp_username: email_username,
            smtp_password: email_password
        }).done(function(test_result) {
            if (test_result == 1) {
                alert('Email was sent successfully (to elabftw-test@yopmail.com) :)');
                $('#final_section').show();
                $('#test_email_button').hide();
            } else {
                alert('The connection failed :/');
            }
        });
    });
    // skip email button
    $('#skip_email_button').click(function() {
        // show warning about resetting passwords not working without SMTP configured
        /* Not using the .dialog of jquery UI for now.
        $('#dialog_skip_email').dialog({
            buttons: [ { text: "Got it, I'll do it later.", click: function() { $( this ).dialog( "close" ); } } ],
            draggable: true,
            height: 500,
            modal: true
        });
         */
        alert('Resetting passwords functionnality won\'t be available until you configure correctly the email settings.');

        // we hide email because it was skipped
        $('#email_section').hide();
        // show last section
        $('#final_section').show();
    });
});
</script>
</body>
</html>

