<?php
// elabftw update file. Run it after each git pull.
// php update.php on normal server
// /Applications/MAMP/bin/php/php5.3.6/bin/php update.php for MAMP install
//
$die_msg = "There was a problem in the database update :/ Please report a bug : https://github.com/NicolasCARPi/elabftw/issues?state=open";

require_once 'inc/functions.php';

// make a simple query
function q($sql) {
    global $pdo;
    try {
        $req = $pdo->prepare($sql);
        $req->execute();
    }
    catch (PDOException $e)
    {
        echo "\nThe update failed. Here is the error :\n\n".$e->getMessage();
        die();
    }
}

function add_field($table, $field, $params, $added) {
    global $pdo;
    // first test if it's here already
    $sql = "SHOW COLUMNS FROM $table";
    $req = $pdo->prepare($sql);
    $req->execute();
    $field_is_here = false;
    while ($show = $req->fetch()) {
        if (in_array($field, $show)) {
            $field_is_here = true;
        }
    }
    // add field if it's not here
    if (!$field_is_here) {
        $sql = "ALTER TABLE $table ADD $field $params";
        $req = $pdo->prepare($sql);
        $result = $req->execute();

        if($result) {
            echo $added;
        } else {
             die($die_msg);
        }
    }
}

function rm_field($table, $field, $added) {
    global $pdo;
    // first test if it's here already
    $sql = "SHOW COLUMNS FROM $table";
    $req = $pdo->prepare($sql);
    $req->execute();
    $field_is_here = false;
    while ($show = $req->fetch()) {
        if (in_array($field, $show)) {
            $field_is_here = true;
        }
    }
    // rm field if it's here
    if ($field_is_here) {
        $sql = "ALTER TABLE $table DROP $field";
        $req = $pdo->prepare($sql);
        $result = $req->execute();

        if($result) {
            echo $added;
        } else {
             die($die_msg);
        }
    }
}

// check if it's run from cli or web; do nothing if it's from web
if(php_sapi_name() != 'cli' || !empty($_SERVER['REMOTE_ADDR'])) {
    die("<p>Thank you for using eLabFTW. <br />To update your database, run this file only from the command line.</p>");
}


// check for config file
if (!file_exists('admin/config.php')) {
    die("There is something seriously wrong with your install. I could not find the file admin/config.php !");
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
     ('".get_config('lab_name')."', '".get_config('deletable_xp')."', '".get_config('link_name')."', '".get_config('link_href')."')");
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
    q("ALTER TABLE `experiments` ADD `timestamped` BOOLEAN NOT NULL DEFAULT FALSE AFTER `lockedwhen`, ADD `timestampedby` INT NULL DEFAULT NULL AFTER `timestamped`, ADD `timestampedwhen` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP AFTER `timestampedby`, ADD `timestamptoken` TEXT NULL AFTER `timestampedwhen`;");
    q("INSERT INTO `config` (`conf_name`, `conf_value`) VALUES ('stamplogin', NULL), ('stamppass', NULL);");
    echo ">>> You can now timestamp experiments. See the wiki for more infos.\n";
}

echo "\n\nEverything went well :). Thanks for using eLabFTW. Have a great day !\n";
