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
        echo $e->getMessage();
        die();
    }
}

function add_field($table, $field, $params, $added, $not_added) {
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
            return $added;
        } else {
             die($die_msg);
        }
    } else {
        return $not_added;
    }
}

// check if it's run from cli or web; do nothing if it's from web
if(php_sapi_name() != 'cli' || !empty($_SERVER['REMOTE_ADDR'])) {
    die("<p>Thank you for using eLabFTW. <br />To update your database, run this file only from the command line.</p>");
}


// Switching from ini_arr to config.php constants
if (!file_exists('admin/config.php')) {
    die("There is something seriously wrong with your install. I could not find the file admin/config.php !");
}

require_once 'inc/connect.php';

// add elabid field in experiments table
add_field('experiments', 'elabid', 'VARCHAR(255) NOT NULL', ">>> Experiments now have unique elabid number.\n", "Column 'elabid' already exists. Nothing to do.\n");

// ADD elabid for experiments without it
// get id of experiments with empty elabid
$sql = "SELECT id from experiments WHERE elabid LIKE ''";
$req = $pdo->prepare($sql);
$req->execute();
// array to store the id
$id_arr = array();
while ($get_id = $req->fetch()) {
    $id_arr[] = $get_id['id']." ";
}
foreach($id_arr as $id) {
    // get date
    $sql = "SELECT date from experiments WHERE id = :id";
    $req = $pdo->prepare($sql);
    $req->execute(array(
        'id' => $id
    ));
    $elabid_fill = $req->fetch();
    $date = $elabid_fill['date'];
    // Generate unique elabID
    $elabid = $date."-".sha1(uniqid($date, true));
    // add elabid
    $sql = "UPDATE experiments SET elabid=:elabid WHERE id=:current_id";
    $req = $pdo->prepare($sql);
    $result = $req->execute(array(
        'elabid' => $elabid,
        'current_id' => $id
    ));
    if ($result) {
        echo "Experiment id ".$id." updated.\n";
    } else {
        die($die_msg);
    }
}

// ADD locked field in experiments table
add_field('experiments', 'locked', "TINYINT(1) UNSIGNED NOT NULL DEFAULT '0'", ">>> Experiments can now be locked.\n", "Column 'locked' already exists. Nothing to do.\n");

// items_type :
$sql = "SHOW TABLES";
$req = $pdo->prepare($sql);
$req->execute();
$test = $req->fetch();
$test_arr = array();
while ($row = $req->fetch()) {
        $test_arr[] = $row[0];
}

if(in_array('items_types',$test_arr)) {
      echo "Table 'items_types' already exists. Nothing to do.\n";
} else {


    $create_sql = "CREATE TABLE `items_types` (
        `id` INT UNSIGNED NOT NULL AUTO_INCREMENT ,
        `name` TEXT NOT NULL ,
        `bgcolor` VARCHAR( 6 ) DEFAULT '000000',
        `template` TEXT NULL,
        `tags` TEXT NULL,
        PRIMARY KEY ( `id` )
    ) ENGINE = InnoDB CHARACTER SET utf8 COLLATE utf8_general_ci;";
    $req = $pdo->prepare($create_sql);
    $result = $req->execute();
    if($result) {
        echo 'Table items_types successfully created.\n';
    } else {
        die($die_msg);
    }

    // Transform all ant => 1, pla => 2, pro => 3
    // get id of items type ant
    $sql = "SELECT id from items WHERE type LIKE 'ant'";
    $req = $pdo->prepare($sql);
    $req->execute();
    // array to store the id
    $id_arr = array();
    while ($get_id = $req->fetch()) {
        $id_arr[] = $get_id['id']." ";
    }
    foreach($id_arr as $id) {
        // change value
        $sql = "UPDATE items SET type=:type WHERE id=:current_id";
        $req = $pdo->prepare($sql);
        $result = $req->execute(array(
            'type' => '1',
            'current_id' => $id
        ));
        if ($result) {
            echo "Item id ".$id." updated.\n";
        } else {
        die($die_msg);
        }
    }
    // get id of items type pla
    $sql = "SELECT id from items WHERE type LIKE 'pla'";
    $req = $pdo->prepare($sql);
    $req->execute();
    // array to store the id
    $id_arr = array();
    while ($get_id = $req->fetch()) {
        $id_arr[] = $get_id['id']." ";
    }
    foreach($id_arr as $id) {
        // change value
        $sql = "UPDATE items SET type=:type WHERE id=:current_id";
        $req = $pdo->prepare($sql);
        $result = $req->execute(array(
            'type' => '2',
            'current_id' => $id
        ));
        if ($result) {
            echo "Item id ".$id." updated.\n";
        } else {
            die($die_msg);
        }
    }
    // get id of items type pro
    $sql = "SELECT id from items WHERE type LIKE 'pro'";
    $req = $pdo->prepare($sql);
    $req->execute();
    // array to store the id
    $id_arr = array();
    while ($get_id = $req->fetch()) {
        $id_arr[] = $get_id['id']." ";
    }
    foreach($id_arr as $id) {
        // change value
        $sql = "UPDATE items SET type=:type WHERE id=:current_id";
        $req = $pdo->prepare($sql);
        $result = $req->execute(array(
            'type' => '3',
            'current_id' => $id
        ));
        if ($result) {
            echo "Item id ".$id." updated.\n";
        } else {
            die($die_msg);
        }
    }
    $sql = "";

    // Change type of type (string => int) in items table and fill table items_types
    $sql = "ALTER TABLE `items` CHANGE `type` `type` INT UNSIGNED NOT NULL;INSERT INTO `items_types` (`id`, `name`, `bgcolor`, `template`, `tags`) VALUES (NULL, 'Antibody', '2cff00', NULL, NULL);INSERT INTO `items_types` (`id`, `name`, `bgcolor`, `template`, `tags`) VALUES (NULL, 'Plasmid', '004bff', NULL, NULL);INSERT INTO `items_types` (`id`, `name`, `bgcolor`, `template`, `tags`) VALUES (NULL, 'Protocol', 'ff0000', NULL, NULL);";
    $req = $pdo->prepare($sql);
    $result = $req->execute();
    if($result) {
        echo "Database successfully updated with default values.\n";
    } else {
        die($die_msg);
    }


}

// ADD visibility field in experiments table
add_field('experiments', 'visibility', "VARCHAR(255) NOT NULL", ">>> Experiments now have a visibility switch.\n", "Column 'visibility' already exists. Nothing to do.\n");
// put visibility = team everywhere
$sql = "UPDATE `experiments` SET `visibility` = 'team'";
$req = $pdo->prepare($sql);
$result = $req->execute();


// remove unused items_templates table
echo "Table items_templates...";
$sql = "DROP TABLE IF EXISTS `items_templates`";
$req = $pdo->prepare($sql);
$result = $req->execute();
if ($result) {
    echo "Nothing to do.\n";
} else {
    die($die_msg);
}
// remove unused users table
echo "Unused users columns...";
$sql = "SELECT * from users";
$req = $pdo->prepare($sql);
$req->execute();
$test = $req->fetch();
if(isset($test['is_jc_resp'])) {
    $sql = "ALTER TABLE `users` DROP `is_jc_resp`,DROP `is_pi`, DROP `journal`, DROP `last_jc`";
    $req = $pdo->prepare($sql);
    $result = $req->execute();
    if($result) {
        echo "Removed unused fields in users table.\n";
    } else {
        die($die_msg);
    }
} else {
    echo "Nothing to do.\n";
}
// TMP upload dir
echo "Create uploads/tmp directory...";
if (!is_dir("uploads/tmp")){
   if  (mkdir("uploads/tmp", 0777)){
    echo "Directory created";
    }else{
        // TODO link to the FAQ
        die("Failed creating uploads/tmp directory. Do it manually and chmod 777 it.");
    }
}else{
    echo "Nothing to do.\n";
}


// ADD locked field in items table
add_field('items', 'locked', "TINYINT(1) UNSIGNED NOT NULL DEFAULT '0'", ">>> Items can now be locked.\n", "Column 'locked' already exists. Nothing to do.\n");


// TRANSFORM DATES IN NEW FORMAT
// first we check if we need to do it
$sql = "SELECT `date` FROM `experiments` WHERE CHAR_LENGTH(`date`) < 8";
$req = $pdo->prepare($sql);
$req->execute();
// if some dates are less than 8 char we make the update
if ($req->rowCount() > 0) {
    $sql = "UPDATE `experiments` SET date = date + 20000000 WHERE CHAR_LENGTH(`date`) = 6";
    $req = $pdo->prepare($sql);
    $req->execute();

    echo ">>> Dates are now YYYYMMDD in experiments.\n";
} else {
    echo "Dates are YYYYMMDD in experiments. Nothing to do.\n";
}


// same for items
$sql = "SELECT `date` FROM `items` WHERE CHAR_LENGTH(`date`) < 8";
$req = $pdo->prepare($sql);
$req->execute();
if ($req->rowCount() > 0) {
    $sql = "UPDATE `items` SET date = date + 20000000 WHERE CHAR_LENGTH(`date`) = 6";
    $req = $pdo->prepare($sql);
    $req->execute();
    echo ">>> Dates are now YYYYMMDD in the database.\n";

} else {
    echo "Dates are YYYYMMDD. Nothing to do.\n";
}

// ADD DELETABLE_XP CONFIG
// check if we need to add it

/*
if (defined('DELETABLE_XP'))  {
    echo "DELETABLE_XP already set. Nothing to do.\n";
} else {
    $deletable_xp_line = "\n\n// set to 0 if you don't want users to be able to delete experiments\ndefine('DELETABLE_XP', 1);\n";
    $file = 'admin/config.php';
    $result = file_put_contents($file, $deletable_xp_line, FILE_APPEND | LOCK_EX);
    if ($result) {
        echo ">>> Added the deletable experiments option in config file\n";
    } else {
        echo "Couldn't add the DELETEABLE_XP option in config file, add it manually (see the config.php-EXAMPLE file).\n";
    }
}
 */

// ADD experiments_comments table
$sql = "SHOW TABLES";
$req = $pdo->prepare($sql);
$req->execute();
$test = $req->fetch();
$test_arr = array();
while ($row = $req->fetch()) {
        $test_arr[] = $row[0];
}

if(in_array('experiments_comments',$test_arr)) {
      echo "Table 'experiments_comments' already exists. Nothing to do.\n";
} else {

    $create_sql = "
    CREATE TABLE IF NOT EXISTS `experiments_comments` (
      `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
      `datetime` datetime NOT NULL,
      `exp_id` int(11) NOT NULL,
      `comment` text NOT NULL,
      `userid` int(11) NOT NULL,
      PRIMARY KEY (`id`)
    ) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE utf8_general_ci;";
    $req = $pdo->prepare($create_sql);
    $result = $req->execute();
    if($result) {
        echo ">>> You can now leave a comment on an experiment !\n";
    } else {
        die($die_msg);
    }
}

// ADD lockedby field in experiments table
    // first test if it's here already
    $sql = "SHOW COLUMNS FROM experiments";
    $req = $pdo->prepare($sql);
    $req->execute();
    $field_is_here = false;
    while ($show = $req->fetch()) {
        if (in_array('lockedby', $show)) {
            $field_is_here = true;
        }
    }
    // add field if it's not here
    if (!$field_is_here) {
        $sql = "ALTER TABLE experiments ADD lockedby INT UNSIGNED NULL AFTER locked";
        $req = $pdo->prepare($sql);
        $result = $req->execute();
        // update the lockedby field and put userid of experiment
        // to avoid users with already locked experiments being locked out
        $sql = "UPDATE experiments SET lockedby = userid WHERE locked = 1";
        $req = $pdo->prepare($sql);
        $req->execute();

        if($result) {
            echo ">>> Now only the locker of an experiment can unlock it.\n";
        } else {
             die($die_msg);
        }
    } else {
        echo "Column 'lockedby' already exists. Nothing to do.\n";
    }



// ADD can_lock field in users table
add_field ('users', 'can_lock', "INT(1) NOT NULL DEFAULT '0' AFTER is_admin", ">>> A user needs to have locking rights to lock experiments of others.\n", "Column 'can_lock' already exists. Nothing to do.\n");

// remove unused tag column of items_types
// first test if it's here already
$sql = "SHOW COLUMNS FROM `items_types`";
$req = $pdo->prepare($sql);
$req->execute();
$column_is_here = false;
while ($show = $req->fetch()) {
    if (in_array('tags', $show)) {
        $column_is_here = true;
    }
}
if ($column_is_here) {
    $sql = "ALTER TABLE `items_types` DROP `tags`";
    $req = $pdo->prepare($sql);
    $result = $req->execute();

    if($result) {
        echo ">>> Dropped unused tags column in items_types table.";
    } else {
         die($die_msg);
    }
} else {
    echo "Tags column is not here. Nothing to do.\n";
}

// remove TODO file
if (file_exists('TODO')) {
    unlink('TODO');
}

// CREATE table banned_users
$sql = "SHOW TABLES";
$req = $pdo->prepare($sql);
$req->execute();
$table_is_here = false;
while ($show = $req->fetch()) {
    if (in_array('banned_users', $show)) {
        $table_is_here = true;
    }
}

if (!$table_is_here) {
    $create_sql = "CREATE TABLE IF NOT EXISTS `banned_users` (
      `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
      `user_infos` text NOT NULL,
      `time` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
      PRIMARY KEY (`id`)
    ) ENGINE=InnoDB  DEFAULT CHARSET=utf8;";
    $req = $pdo->prepare($create_sql);
    $result = $req->execute();
    if($result) {
        echo "Table 'banned_users' successfully created.\n";
    } else {
        die($die_msg);
    }
} else {
    echo "Table 'banned_users' already exists. Nothing to do.\n";
}

// CREATE table config
$sql = "SHOW TABLES";
$req = $pdo->prepare($sql);
$req->execute();
$table_is_here = false;
while ($show = $req->fetch()) {
    if (in_array('config', $show)) {
        $table_is_here = true;
    }
}

if (!$table_is_here) {
    $create_sql = "CREATE TABLE IF NOT EXISTS `config` (
        `conf_name` VARCHAR(255) NOT NULL,
        `conf_value` TEXT NULL,
      PRIMARY KEY (`conf_name`)
    ) ENGINE=InnoDB  DEFAULT CHARSET=utf8;";
    $req = $pdo->prepare($create_sql);
    $result1 = $req->execute();

    // Populate config table
    $sql = "INSERT INTO config (conf_name, conf_value) VALUES
        ('lab_name', '".LAB_NAME."'),
        ('path', '".PATH."'),
        ('admin_validate', '".ADMIN_VALIDATE."'),
        ('link_name', '".LINK_NAME."'),
        ('link_href', '".LINK_HREF."'),
        ('smtp_address', '".SMTP_ADDRESS."'),
        ('smtp_port', '".SMTP_PORT."'),
        ('smtp_encryption', '".SMTP_ENCRYPTION."'),
        ('smtp_username', '".SMTP_USERNAME."'),
        ('smtp_password', '".SMTP_PASSWORD."'),
        ('proxy', '".PROXY."'),
        ('debug', '0'),
        ('deletable_xp', '".DELETABLE_XP."'),
        ('login_tries', '5'),
        ('ban_time', '60');";
    $req = $pdo->prepare($sql);
    $result2 = $req->execute();

    if($result && $result2) {
        echo "Table 'config' successfully created and populated.\n";
    } else {
        die($die_msg);
    }


} else {
    echo "Table 'config' already exists. Nothing to do.\n";
}


// STATUS UPDATE
// Convert all experiments status in numbers
$sql = "UPDATE experiments SET status = 1 WHERE status = 'running'";
$req = $pdo->prepare($sql);
$req->execute();
$sql = "UPDATE experiments SET status = 2 WHERE status = 'success'";
$req = $pdo->prepare($sql);
$req->execute();
$sql = "UPDATE experiments SET status = 3 WHERE status = 'redo'";
$req = $pdo->prepare($sql);
$req->execute();
$sql = "UPDATE experiments SET status = 4 WHERE status = 'fail'";
$req = $pdo->prepare($sql);
$req->execute();

// CREATE table config
$sql = "SHOW TABLES";
$req = $pdo->prepare($sql);
$req->execute();
$table_is_here = false;
while ($show = $req->fetch()) {
    if (in_array('status', $show)) {
        $table_is_here = true;
    }
}

if (!$table_is_here) {
    $create_sql = "CREATE TABLE IF NOT EXISTS `status` (
          `id` int unsigned NOT NULL AUTO_INCREMENT,
          `name` text NOT NULL,
          `color` varchar(6) NOT NULL,
          `is_default` BOOLEAN NULL DEFAULT NULL,
          PRIMARY KEY (`id`)
    ) ENGINE=InnoDB  DEFAULT CHARSET=utf8;";
    $req = $pdo->prepare($create_sql);
    $result1 = $req->execute();

    // Populate status table
    $sql = "INSERT INTO status (name, color, is_default) VALUES
        ('Running', '0000FF', true),
        ('Success', '00ac00', false),
        ('Need to be redone', 'c0c0c0', false),
        ('Fail', 'ff0000', false);";
    $req = $pdo->prepare($sql);
    $result2 = $req->execute();

    if($result && $result2) {
        echo "Table 'status' successfully created and populated.\n";
    } else {
        die($die_msg);
    }


} else {
    echo "Table 'status' already exists. Nothing to do.\n";
}

// change path to md5(path) in config
// first check if we have something else than a md5 in the config
if (strlen(get_config('path')) != 36 || strpos(get_config('path'), '/'))  {
    $newpath = md5(dirname(__FILE__));
    $sql = "UPDATE config SET conf_value = :newpath WHERE conf_name = 'path'";
    $req = $pdo->prepare($sql);
    $req->bindParam(':newpath', $newpath);
    $req->execute();
}

// in uploads there is no more database type so change database to 'items'
// it will be executed everytime but we don't care.
$sql = "UPDATE uploads SET type = 'items' WHERE type = 'database'";
$req = $pdo->prepare($sql);
$req->execute();

// add experiment template
// check if there is one
$sql = "SELECT COUNT(id) FROM experiments_templates WHERE userid = 0";
$req = $pdo->prepare($sql);
$req->execute();
$count = $req->fetch();
if ($count[0] > "0") {
    echo "Default experiment template already set. Nothing to do.\n";
} else {
    // we need to add it
    $sql = "INSERT INTO `experiments_templates` (`body`, `name`, `userid`) VALUES
        ('<p><span style=\"font-size: 14pt;\"><strong>Goal :</strong></span></p>
        <p>&nbsp;</p>
        <p><span style=\"font-size: 14pt;\"><strong>Procedure :</strong></span></p>
        <p>&nbsp;</p>
        <p><span style=\"font-size: 14pt;\"><strong>Results :</strong></span></p><p>&nbsp;</p>', 'default', 0)";
    $req = $pdo->prepare($sql);
    $req->execute();
    echo ">>> There is now a default experiment template editable by admin.\n";
}

// CREATE table experiments_revisions
$sql = "SHOW TABLES";
$req = $pdo->prepare($sql);
$req->execute();
$table_is_here = false;
while ($show = $req->fetch()) {
    if (in_array('experiments_revisions', $show)) {
        $table_is_here = true;
    }
}

if (!$table_is_here) {
    $create_sql = "CREATE TABLE IF NOT EXISTS `experiments_revisions` (
      `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
      `exp_id` int(10) unsigned NOT NULL,
      `body` text NOT NULL,
      `savedate` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
      `userid` int(11) NOT NULL,
      PRIMARY KEY (`id`)
    ) ENGINE=InnoDB  DEFAULT CHARSET=utf8";
    $req = $pdo->prepare($create_sql);
    $result = $req->execute();


    if($result) {
        echo ">>> There is now a revision system for experiments.\n";
    } else {
        die($die_msg);
    }

} else {
    echo "Table 'experiments_revisions' already exists. Nothing to do.\n";
}


// ADD close_warning column to users table
echo add_field ('users', 'close_warning', "TINYINT(1) UNSIGNED NOT NULL DEFAULT '0' AFTER sc_todo", ">>> New preference to ask confirmation before closing an edition window (go in UCP to check it).\n", "Column 'close_warning' already exists. Nothing to do.\n");


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

} else {
    echo "Table 'teams' already exists. Nothing to do.\n";
}


