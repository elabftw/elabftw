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
// elabftw update file. Access it after each git pull.

// check if it's run from cli or web; tell it must be called from web now
if (php_sapi_name() == 'cli' || empty($_SERVER['REMOTE_ADDR'])) {
    echo ">>> Please run the update script from your browser (enter update.php instead of experiments.php in the URL).\n";
    exit;
}

require_once 'inc/common.php';

// die if you are not sysadmin
if ($_SESSION['is_sysadmin'] != 1) {
    die(_('This section is out of your reach.'));
}

$die_msg = "There was a problem in the database update :/ Please report a bug : https://github.com/elabftw/elabftw/issues?state=open";

// START //

// 20150227 : add items_revisions
$sql = "SHOW TABLES";
$req = $pdo->prepare($sql);
$req->execute();
$table_is_here = false;
while ($show = $req->fetch()) {
    if (in_array('items_revisions', $show)) {
        $table_is_here = true;
    }
}

if (!$table_is_here) {
    q(
        "CREATE TABLE IF NOT EXISTS `items_revisions` (
        `id` int(10) unsigned NOT NULL AUTO_INCREMENT PRIMARY KEY,
        `item_id` int(10) unsigned NOT NULL,
        `body` text NOT NULL,
        `savedate` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
        `userid` int(11) NOT NULL
        ) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;"
    );
}

// 20150324 : adding secret key used to encrypt the SMTP password
// first we check if we can write the config file
if (!is_writable('config.php')) {

    // check that there is no secret key already
    if (!defined('SECRET_KEY')) {

        $msg_arr[] = "[ERROR] Please allow webserver to write config file, or add SECRET_KEY yourself to config.php. <a href='doc/_build/html/common-errors.html#add-the-secret-key'>Link to documentation</a>";
        $_SESSION['errors'] = $msg_arr;
        header('Location: sysconfig.php');
        exit;
    }

} elseif (is_writable('config.php') && !defined('SECRET_KEY')) {

    $crypto = new \Elabftw\Elabftw\Crypto();
    // add generated strings to config file
    // the IV is stored in hex
    $data_to_add = "\ndefine('SECRET_KEY', '" . $crypto->secretKey . "');\ndefine('IV', '" . bin2hex($crypto->iv) . "');\n";

    try {
        file_put_contents('config.php', $data_to_add, FILE_APPEND);
    } catch (Exception $e) {
        $msg_arr[] = "[ERROR] " . $e->getMessage();
        $_SESSION['errors'] = $msg_arr;
        header('Location: sysconfig.php');
        exit;
    }

    // ok so now we have a secret key, an IV and we want to convert our old cleartext SMTP password to an encrypted one
    $config_arr = array();

    // if there is a password in cleartext in the database, we encrypt it
    if (strlen(get_config('smtp_password')) > 0) {
        $config_arr['smtp_password'] = $crypto->encrypt(get_config('smtp_password'));
    }
    if (strlen(get_config('stamppass')) > 0) {
        $config_arr['stamppass'] = $crypto->encrypt(get_config('stamppass'));
    }

    try {
        update_config($config_arr);
    } catch (Exception $e) {
        $msg_arr[] = "[ERROR] " . $e->getMessage();
        $_SESSION['errors'] = $msg_arr;
        header('Location: sysconfig.php');
        exit;
    }

    // now we update the stamppass in the `teams` table
    // first get the list of teams with a stamppass
    $sql = "SELECT * FROM teams WHERE CHAR_LENGTH(stamppass) > 0";
    $req = $pdo->prepare($sql);
    $req->execute();
    while ($teams = $req->fetch()) {
        $enc_pass = $crypto->encrypt($teams['stamppass']);

        $sql2 = "UPDATE teams SET stamppass = :stamppass WHERE team_id = :id";
        $req2 = $pdo->prepare($sql2);
        $req2->bindParam(':stamppass', $enc_pass);
        $req2->bindParam(':id', $teams['team_id']);
        $req2->execute();
    }
}

// 20150325 : fix the items_tags not having a valid team_id
// first look if there are entries to fix
$sql = "SELECT * FROM items_tags WHERE team_id = 0";
$req = $pdo->prepare($sql);
$req->execute();
if ($req->rowCount() > 0) {
    while ($items_tags = $req->fetch()) {
        // now we must find in which team the item associated to the tag is, and update the record
        $sql2 = "SELECT team FROM items WHERE id = :item_id";
        $req2 = $pdo->prepare($sql2);
        $req2->bindParam(':item_id', $items_tags['item_id']);
        $req2->execute();
        $items = $req2->fetch();

        // update the record
        $sql3 = "UPDATE items_tags SET team_id = :team_id WHERE id = :id";
        $req3 = $pdo->prepare($sql3);
        $req3->bindParam(':team_id', $items['team']);
        $req3->bindParam(':id', $items_tags['id']);
        $req3->execute();
    }
}

// 20150304 : add rfc 3161 timestamping/generic timestamping providers

// add stampprovider, stampcert and stamphash to teams table
add_field('teams', 'stampprovider', "TEXT NULL DEFAULT NULL");
add_field('teams', 'stampcert', "TEXT NULL DEFAULT NULL");
add_field('teams', 'stamphash', "VARCHAR(10) NULL DEFAULT 'sha256'");

// check if stamppass and stamplogin are set globally but not stampprovider => old-style timestamping using Universign
$sql = "SELECT conf_name FROM config";
$req = $pdo->prepare($sql);
$req->execute();
$config_items = array();
$old_timestamping_global = false;
while ($show = $req->fetch()) {
    array_push($config_items, $show["conf_name"]);
}

if (in_array('stamplogin', $config_items) && in_array('stamppass', $config_items) && !in_array('stampprovider', $config_items)) {
    $old_timestamping_global = true;
}

// if Universign was used globally, add timestamping parameters
if ($old_timestamping_global) {
    $sql = "INSERT INTO config (conf_name, conf_value) VALUES ('stampprovider', 'https://ws.universign.eu/tsa'), ('stampcert', :certfile), ('stamphash', 'sha256')";
    $req = $pdo->prepare($sql);
    $res = $req->execute(array('certfile' => 'vendor/universign-tsa-root.pem'));
    if ($res) {
        $msg_arr[] = ">>> Added Universign.eu as global RFC 3161 TSA";
    } else {
        die($die_msg);
    }
}

// check if stamppass and stamplogin are set for teams but not stampprovider => old-style timestamping using Universign
$sql = "SELECT * FROM teams";
$req = $pdo->prepare($sql);
$req->execute();
$teams = array();
$old_timestamping_teams = false;
while ($show = $req->fetch()) {
    array_push($teams, $show);
}

// check for each team and set timestamping parameters if needed
foreach ($teams as $team) {
    if ($team['stamplogin'] !== '' && $team['stamppass'] !== '' && $team['stampprovider'] === '') {
        $old_timestamping_teams = true;
        $sql = "UPDATE teams SET stampprovider = 'https://ws.universign.eu/tsa/', stampcert = :certfile,
                stamphash = 'sha256' WHERE team_id = :id";
        $req = $pdo->prepare($sql);
        $res = $req->execute(array(
            'certfile' => 'vendor/universign-tsa-root.pem',
            'id' => $team['team_id']
            ));
        if ($res) {
            $msg_arr[] = ">>> Added Universign.eu as RFC 3161 TSA for team #" . $team['team_id'];
        } else {
            die($die_msg);
        }
    }
}


// if Universign.eu was not used, a database update might still be needed; check for that
if (!$old_timestamping_global) {
    // add stampprovider, stampcert and stamphash to configuration
    // check if we need to
    $sql = "SELECT COUNT(*) AS confcnt FROM config";
    $req = $pdo->prepare($sql);
    $req->execute();
    $confcnt = $req->fetch(PDO::FETCH_ASSOC);

    if ($confcnt['confcnt'] < 17) {
        $sql = "INSERT INTO config (conf_name, conf_value) VALUES ('stampprovider', null), ('stampcert', null), ('stamphash', 'sha256')";
        $req = $pdo->prepare($sql);
        $res = $req->execute();
        if ($res) {
            $msg_arr[] = ">>> Added global timestamping provider, certificate and hash algorithm";
        } else {
            die($die_msg);
        }
    }
}

// 20150401 Add mail method to database (SMTP/sendmail)
$sql = "SELECT COUNT(*) AS confcnt FROM config";
$req = $pdo->prepare($sql);
$req->execute();
$confcnt = $req->fetch(PDO::FETCH_ASSOC);

if ($confcnt['confcnt'] < 20) {
    $mail_method = 'sendmail';
    // check if an smtp server was set
    $sql = "SELECT * FROM config";
    $req = $pdo->prepare($sql);
    $req->execute();
    $config_items = [];
    while ($show = $req->fetch()) {
        array_push($config_items, $show);
    }

    if ($config_items['smtp_address'] !== '') {
        $mail_method = 'smtp';
        $smtp_username = filter_var($config_items['smtp_username'], FILTER_VALIDATE_EMAIL);
        // check if we can use the smtp_username as sender email address
        if ($smtp_username) {
            $from_email = $smtp_username;
        } else {
            // put a fake address so that it works right away
            $from_email = 'notconfigured@example.com';
        }
    }

    $sql = "INSERT INTO config (conf_name, conf_value) VALUES ('mail_method', '" . $mail_method . "'), ('sendmail_path', '/usr/bin/sendmail'), ('mail_from', '" . $from_email . "')";
    $req = $pdo->prepare($sql);
    $res = $req->execute();
    if (!$res) {
        die($die_msg);
    }
}

// 20150608 try to fix the experiments that were duplicated before the duplication bug was fixed in 72ef0bf
// the bug was that the status fetched was not the right one for teams with id ≠ 1
// so we need to find all the experiments with a wrong status, and fix them (by replacing it with the default status)
$sql = "SELECT id, status, team FROM experiments WHERE team > 1";
$req = $pdo->prepare($sql);
$req->execute();

while ($experiment = $req->fetch()) {
    // check that the status of the experiment is owned by the team of the experiment
    $get_status_list_sql = "SELECT id FROM status WHERE team = :team";
    $req2 = $pdo->prepare($get_status_list_sql);
    $req2->bindParam(':team', $experiment['team']);
    $req2->execute();
    while ($status = $req2->fetch()) {
        $status_arr[] = $status['id'];
    }
    // if we can't find the status id in the status list of the team
    // then we need to update the status to one owned by the team
    if (!in_array($experiment['status'], $status_arr)) {
        $update_sql = "UPDATE experiments SET status = :status WHERE id = :id";
        $req3 = $pdo->prepare($update_sql);
        // first one coming
        $req3->bindParam(':status', $status_arr[0]);
        $req3->bindParam(':id', $experiment['id']);
        $req3->execute();
    }
}

// 20150705 add ordering to experiments_templates, status and items types
if (add_field('experiments_templates', 'ordering', "INT(10) UNSIGNED NULL DEFAULT NULL")) {
    $msg_arr[] = '>>> Added ordering to experiments templates.';
}
if (add_field('status', 'ordering', "INT(10) UNSIGNED NULL DEFAULT NULL")) {
    $msg_arr[] = '>>> Added ordering to status.';
}

if (add_field('items_types', 'ordering', "INT(10) UNSIGNED NULL DEFAULT NULL")) {
    $msg_arr[] = '>>> Added ordering to items types.';
}


// 20150707
// set pki.dfn.de as TSA if we have not configured universign
if (get_config('stampprovider') == 'https://ws.universign.eu/tsa' && !get_config('stamplogin')) {
    $config_arr = array(
        'stampprovider' => 'http://zeitstempel.dfn.de/',
        'stampcert' => 'vendor/pki.dfn.pem');

    update_config($config_arr);
    $msg_arr[] = '>>> Timestamping is now done with pki.dfn.de, requires no further configuration and is free!';
}

// 20150708
// add chem_editor pref
if (add_field('users', 'chem_editor', "TINYINT(1) UNSIGNED NOT NULL DEFAULT '0' AFTER `close_warning`")) {
    $msg_arr[] = '>>> Added Chem editor pref to users.';
}

// 20150709 remove export folder
// first remove content
$dir = ELAB_ROOT . '/uploads/export';
if (is_dir($dir)) {
    $di = new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS);
    $ri = new RecursiveIteratorIterator($di, RecursiveIteratorIterator::CHILD_FIRST);
    foreach ($ri as $file) {
        $file->isDir() ? rmdir($file) : unlink($file);
    }
    // and remove folder itself
    rmdir($dir);
}

// 20150723 add db version
$sql = "SELECT COUNT(*) AS confcnt FROM config";
$req = $pdo->prepare($sql);
$req->execute();
$confcnt = $req->fetch(\PDO::FETCH_ASSOC);

if ($confcnt['confcnt'] < 21) {
    $sql = "INSERT INTO config (`conf_name`, `conf_value`) VALUES ('schema', '1')";
    $req = $pdo->prepare($sql);
    if ($req->execute()) {
        $msg_arr[] = '>>> Added schema config.';
    } else {
        die($die_msg);
    }
}

// //////////////////////////////////////////
// INSERT NEW CODE BLOCKS ABOVE THIS LINE //
// /////////////////////////////////////////

// cleanup files in tmp
$dir = ELAB_ROOT . '/uploads/tmp';
$di = new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS);
$ri = new RecursiveIteratorIterator($di, RecursiveIteratorIterator::CHILD_FIRST);
foreach ($ri as $file) {
    $file->isDir() ? rmdir($file) : unlink($file);
}

$msg_arr[] = "[SUCCESS] You are now running the latest version of eLabFTW. Have a great day! :)";
$_SESSION['infos'] = $msg_arr;
header('Location: sysconfig.php');
