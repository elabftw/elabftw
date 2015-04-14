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
require_once 'vendor/autoload.php';

// die if you are not sysadmin
if ($_SESSION['is_sysadmin'] != 1) {
    die(_('This section is out of your reach.'));
}

$die_msg = "There was a problem in the database update :/ Please report a bug : https://github.com/elabftw/elabftw/issues?state=open";

// UPDATE the config file path
// check for config file
if (!file_exists('config.php')) {
    if (file_exists('admin/config.php')) { // update
        // copy the file
        if (rename('admin/config.php', 'config.php')) {
            echo ">>> Config file is now in the root directory\n";
            echo "!!! You can now safely delete the admin directory if you wish: 'rm -rf admin'\n";
        } else {
            echo "!!! Please move 'admin/config.php' to the root directory : 'mv admin/config.php .'\n";
            exit;
        }
    } else {
        die("There is something seriously wrong with your install. I could not find the file config.php !");
    }
}

require_once 'inc/connect.php';

// START //

// BIG TEAM AND GROUPS UPDATE
// CREATE table teams
$sql = "SHOW TABLES";
$req = $pdo->prepare($sql);
$req->execute();
$table_is_here = false;
while ($show = $req->fetch()) {
    if (in_array('teams', $show)) {
        $table_is_here = true;
    }
}



// BIG update coming up
if (!$table_is_here) {
    // create teams table
    q("CREATE TABLE IF NOT EXISTS `teams` (
    `team_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
      `team_name` text NOT NULL,
      `deletable_xp` tinyint(1) NOT NULL,
      `link_name` text NOT NULL,
      `link_href` text NOT NULL,
      `datetime` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY ( `team_id` )
    ) ENGINE=InnoDB  DEFAULT CHARSET=utf8");
    // populate table teams
    q("INSERT INTO teams (team_name, deletable_xp, link_name, link_href) VALUES
     ('".get_config('lab_name') . "', '" . get_config('deletable_xp') . "', '" . get_config('link_name') . "', '" . get_config('link_href') . "')");
    // add teams and group to other tables
    q("ALTER TABLE experiments ADD team int(10) unsigned not null after id;");
    q("ALTER TABLE items ADD team int(10) unsigned not null after id;");
    q("ALTER TABLE items_types ADD team int(10) unsigned not null after id;");
    q("ALTER TABLE status ADD team int(10) unsigned not null after id;");
    q("ALTER TABLE users ADD team int(10) unsigned not null after password;");
    q("ALTER TABLE users ADD usergroup int(10) unsigned not null after team;");
    // populate tables
    q("UPDATE experiments SET team = 1;");
    q("UPDATE items SET team = 1;");
    q("UPDATE items_types SET team = 1;");
    q("UPDATE status SET team = 1;");
    q("UPDATE users SET team = 1;");
    q("UPDATE users SET usergroup = 1 WHERE is_admin = 1;");
    q("UPDATE users SET usergroup = 4 WHERE is_admin = 0;");
    q("UPDATE users SET usergroup = 3 WHERE can_lock = 1;");
    // remove unused fields
    q("ALTER TABLE users DROP is_admin;");
    q("ALTER TABLE users DROP can_lock;");
    // add timestamp to locks
    q("ALTER TABLE `experiments` ADD `lockedwhen` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP AFTER `lockedby`;");
    // add team to experiments_templates
    q("ALTER TABLE `experiments_templates` ADD `team` INT(10) unsigned not null after id;");
    q("UPDATE experiments_templates SET team = 1;");
    // create table groups
    q("CREATE TABLE IF NOT EXISTS `groups` (
    `group_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
      `group_name` text NOT NULL,
      `is_sysadmin` tinyint(1) NOT NULL,
      `is_admin` text NOT NULL,
      `can_lock` text NOT NULL,
        PRIMARY KEY ( `group_id` )
    ) ENGINE=InnoDB  DEFAULT CHARSET=utf8");

    // Populate table
    q("INSERT INTO `groups` (`group_id`, `group_name`, `is_sysadmin`, `is_admin`, `can_lock`) VALUES
    (1, 'Sysadmins', 1, 1, 0),
    (2, 'Admins', 0, 1, 0),
    (3, 'Chiefs', 0, 1, 1),
    (4, 'Users', 0, 0, 0);");

    // Remove the configs from the config table because now they are in the teams table
    q("DELETE FROM `config` WHERE `config`.`conf_name` = 'deletable_xp';
    DELETE FROM `config` WHERE `config`.`conf_name` = 'link_name';
    DELETE FROM `config` WHERE `config`.`conf_name` = 'link_href';
    DELETE FROM `config` WHERE `config`.`conf_name` = 'lab_name'");

    echo ">>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>\n";
    echo ">>> BIG UPDATE !!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!\n";
    echo ">>> One eLabFTW install can now host several teams !\n";
    echo ">>> There is now groups with set of permissions.       \n";
    echo ">>> There is now a new sysadmin group for elabftw configuration\n";
    echo ">>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>\n";
}

// remove theme from users
rm_field('users', 'theme', ">>> Removed custom themes.\n");

// add logs
$sql = "SHOW TABLES";
$req = $pdo->prepare($sql);
$req->execute();
$table_is_here = false;
while ($show = $req->fetch()) {
    if (in_array('logs', $show)) {
        $table_is_here = true;
    }
}

if (!$table_is_here) {
    q("CREATE TABLE IF NOT EXISTS `logs` (
    `id` int(10) unsigned NOT NULL AUTO_INCREMENT PRIMARY KEY,
      `type` varchar(255) NOT NULL,
      `datetime` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
      `user` text,
      `body` text NOT NULL
    ) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;");
    echo ">>> Logs are now stored in the database.\n";
}

// TIMESTAMPS

// check if there is the timestamp columns
$sql = "SHOW COLUMNS FROM experiments";
$req = $pdo->prepare($sql);
$req->execute();
$field_is_here = false;
while ($show = $req->fetch()) {
    if (in_array('timestamped', $show)) {
        $field_is_here = true;
    }
}
// add field if it's not here
if (!$field_is_here) {
    q("ALTER TABLE `experiments` ADD `timestamped` BOOLEAN NOT NULL DEFAULT FALSE AFTER `lockedwhen`, ADD `timestampedby` INT NULL DEFAULT NULL AFTER `timestamped`, ADD `timestampedwhen` TIMESTAMP NULL AFTER `timestampedby`, ADD `timestamptoken` TEXT NULL AFTER `timestampedwhen`;");
    q("INSERT INTO `config` (`conf_name`, `conf_value`) VALUES ('stamplogin', NULL), ('stamppass', NULL);");
    echo ">>> You can now timestamp experiments. See the wiki for more infos.\n";
}

// add md5 field to uploads
add_field('uploads', 'md5', 'VARCHAR(32) NULL DEFAULT NULL', ">>> Uploaded files are now md5 summed upon upload.\n");

// change the unused date column in uploads to a datetime one with current timestamp on insert
rm_field('uploads', 'date', ">>> Removed unused field.\n");
add_field('uploads', 'datetime', "TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP AFTER `type`", ">>> Added timestamp to uploads\n");
// this is run each time (but doesn't hurt)
q("UPDATE uploads SET datetime = CURRENT_TIMESTAMP WHERE datetime = '0000-00-00 00:00:00'");

// add timestamp conf for teams
add_field('teams', 'stamplogin', "TEXT NULL DEFAULT NULL", ">>> Added timestamp team config (login)\n");
add_field('teams', 'stamppass', "TEXT NULL DEFAULT NULL", ">>> Added timestamp team config (pass)\n");

// add stampshare configuration
// check if we need to
$sql = "SELECT COUNT(*) AS confcnt FROM config";
$req = $pdo->prepare($sql);
$req->execute();
$confcnt = $req->fetch(PDO::FETCH_ASSOC);

if ($confcnt['confcnt'] < 14) {
    $sql = "INSERT INTO config (conf_name, conf_value) VALUES ('stampshare', null)";
    $req = $pdo->prepare($sql);
    $res = $req->execute();
    if ($res) {
        echo ">>> Added timestamp credentials sharing\n";
    } else {
        die($die_msg);
    }
}


// add lang to users
add_field('users', 'lang', "VARCHAR(5) NOT NULL DEFAULT 'en-GB'", ">>> You can now select a language!\n");

// add default lang to config
// remove the notice that will appear if there is no lang in config yet
error_reporting(E_ALL & ~E_NOTICE);
if (strlen(get_config('lang')) != 5) {
    q("INSERT INTO `config` (`conf_name`, `conf_value`) VALUES ('lang', 'en-GB');");
}

// update lang, put locale with _ instead of -
// put everyone in english, it's simpler
if (strpos(get_config('lang'), '-')) {
    q("UPDATE users SET `lang` = 'en_GB';");
}

// add elab_root in config.php
if (!defined('ELAB_ROOT')) {
    $path = substr(realpath(__FILE__), 0, -10);
    $text2add = "define('ELAB_ROOT', '" . $path . "');";
    if (file_put_contents('config.php', $text2add, FILE_APPEND)) {
        echo ">>> Added constant ELAB_ROOT in file config.php\n";
    } else {
        echo "!!! Error writing file config.php. Please fix permissions for server to write to it. Or edit it yourself (look at config.php-EXAMPLE)";
    }
}

// remove path in sql config table
if (strlen(get_config('path')) == 32) {
    q("DELETE FROM `config` WHERE `conf_name` = 'path';");
}

// add a team column to items_tags
add_field('items_tags', 'team_id', "INT(10) NOT NULL DEFAULT 0", ">>> Added team column in items_tags table.\n");
// now loop on each items_tags and assign the right team for each. We have the item_id, which is linked to the team.
// first, do we need to do that ?
$sql = "SELECT team_id FROM items_tags LIMIT 1";
$req = $pdo->prepare($sql);
$req->execute();
$team_id = $req->fetch();
if ($team_id[0] == 0) { // if we just added the column, it will be 0
    $sql = "SELECT items_tags.id, items_tags.item_id FROM items_tags";
    $req = $pdo->prepare($sql);
    $req->execute();
    while ($tag = $req->fetch()) {
        $sql = "SELECT items.team FROM items WHERE items.id = :item_id";
        $req2 = $pdo->prepare($sql);
        $req2->execute(array(
            'item_id' => $tag['item_id']
        ));
        while ($team = $req2->fetch()) {
            $sql = "UPDATE items_tags SET team_id = :team WHERE id = :id";
            $update = $pdo->prepare($sql);
            $update->execute(array(
                'team' => $team['team'],
                'id' => $tag['id']
            ));
        }
    }
}


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
    q("CREATE TABLE IF NOT EXISTS `items_revisions` (
      `id` int(10) unsigned NOT NULL AUTO_INCREMENT PRIMARY KEY,
      `item_id` int(10) unsigned NOT NULL,
      `body` text NOT NULL,
      `savedate` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
      `userid` int(11) NOT NULL
    ) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;");
}

// 20150324 : adding secret key used to encrypt the SMTP password
// first we check if we can write the config file
if (!is_writable('config.php')) {

    // check that there is no secret key already
    if (!defined('SECRET_KEY')) {

        $msg_arr[] = "[ERROR] Please allow webserver to write config file, or add SECRET_KEY yourself to config.php. <a href='https://github.com/elabftw/elabftw/wiki/Troubleshooting'>Link to documentation</a>";
        $_SESSION['errors'] = $msg_arr;
        header('Location: sysconfig.php');
        exit;
    }

} elseif (is_writable('config.php') && !defined('SECRET_KEY')) {

    $crypto = new \Elabftw\Elabftw\Crypto();
    // add generated strings to config file
    // the IV is stored in hex
    $data_to_add = "\ndefine('SECRET_KEY', '" . $crypto->getSecretKey() . "');\ndefine('IV', '" . bin2hex($crypto->getIv()) . "');\n";

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
add_field('teams', 'stampprovider', "TEXT NULL DEFAULT NULL", ">>> Added timestamp team config (provider)\n");
add_field('teams', 'stampcert', "TEXT NULL DEFAULT NULL", ">>> Added timestamp team config (cert)\n");
add_field('teams', 'stamphash', "VARCHAR(10) NULL DEFAULT 'sha256'", ">>> Added timestamp team config (hash)\n");

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
        echo ">>> Added Universign.eu as global RFC 3161 TSA\n";
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
        $sql = "UPDATE teams SET stampprovider = 'https://ws.universign.eu/tsaa', stampcert = :certfile,
                stamphash = 'sha256' WHERE team_id = :id";
        $req = $pdo->prepare($sql);
        $res = $req->execute(array(
            'certfile' => 'vendor/universign-tsa-root.pem',
            'id' => $team['team_id']
            ));
        if ($res) {
            echo ">>> Added Universign.eu as RFC 3161 TSA for team #" . $team['team_id'] . "\n";
        } else {
            die($die_msg);
        }
    }
}

// if Universign was used either globally or on a per team level, correct the recorded dates for the timestamps in the database
if ($old_timestamping_global || $old_timestamping_teams) {
    // check if we have timestamped experiments
    $sql = "SELECT * FROM experiments";
    $req = $pdo->prepare($sql);
    $req->execute();
    while ($show = $req->fetch()) {
        if ($show['timestamped'] === '1') {
            $ts = new Elabftw\Elabftw\TrustedTimestamps(null, null, ELAB_ROOT . 'uploads/' . $show['timestamptoken']);
            $date = $ts->getResponseTime();
            if ($show['timestampedwhen'] !== $date) {
                $sql_update = "UPDATE experiments SET timestampedwhen = :date WHERE id = :id";
                $req_update = $pdo->prepare($sql_update);
                $res_update = $req_update->execute(array('date' => $date, 'id' => $show['id']));
                if ($res_update) {
                    echo ">>> Corrected timestamp data for experiment #" . $show['id'] . "\n";
                } else {
                    die($die_msg);
                }
            }
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
            echo ">>> Added global timestamping provider, certificate and hash algorithm\n";
        } else {
            die($die_msg);
        }
    }
}

// END
$msg_arr[] = "[SUCCESS] You are now running the latest version of eLabFTW. Have a great day! :)";
$_SESSION['infos'] = $msg_arr;
header('Location: sysconfig.php');
