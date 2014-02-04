<?php
// elabftw update file. Run it after each git pull.
// php update.php on normal server
// /Applications/MAMP/bin/php/php5.3.6/bin/php update.php for MAMP install
//

function add_field($table, $field, $params, $added, $not_added) {
    global $bdd;
    // first test if it's here already
    $sql = "SHOW COLUMNS FROM $table";
    $req = $bdd->prepare($sql);
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
        $req = $bdd->prepare($sql);
        $result = $req->execute();

        if($result) {
            $added;
        } else {
             die($die_msg);
        }
    } else {
        $not_added;
    }
}

$die_msg = "There was a problem in the database update :/ Please report a bug : https://github.com/NicolasCARPi/elabftw/issues?state=open";
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
$req = $bdd->prepare($sql);
$req->execute();
// array to store the id
$id_arr = array();
while ($get_id = $req->fetch()) {
    $id_arr[] = $get_id['id']." ";
}
foreach($id_arr as $id) {
    // get date
    $sql = "SELECT date from experiments WHERE id = :id";
    $req = $bdd->prepare($sql);
    $req->execute(array(
        'id' => $id
    ));
    $elabid_fill = $req->fetch();
    $date = $elabid_fill['date'];
    // Generate unique elabID
    $elabid = $date."-".sha1(uniqid($date, true));
    // add elabid
    $sql = "UPDATE experiments SET elabid=:elabid WHERE id=:current_id";
    $req = $bdd->prepare($sql);
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
$req = $bdd->prepare($sql);
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
    $req = $bdd->prepare($create_sql);
    $result = $req->execute();
    if($result) {
        echo 'Table items_types successfully created.\n';
    } else {
        die($die_msg);
    }

    // Transform all ant => 1, pla => 2, pro => 3
    // get id of items type ant
    $sql = "SELECT id from items WHERE type LIKE 'ant'";
    $req = $bdd->prepare($sql);
    $req->execute();
    // array to store the id
    $id_arr = array();
    while ($get_id = $req->fetch()) {
        $id_arr[] = $get_id['id']." ";
    }
    foreach($id_arr as $id) {
        // change value
        $sql = "UPDATE items SET type=:type WHERE id=:current_id";
        $req = $bdd->prepare($sql);
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
    $req = $bdd->prepare($sql);
    $req->execute();
    // array to store the id
    $id_arr = array();
    while ($get_id = $req->fetch()) {
        $id_arr[] = $get_id['id']." ";
    }
    foreach($id_arr as $id) {
        // change value
        $sql = "UPDATE items SET type=:type WHERE id=:current_id";
        $req = $bdd->prepare($sql);
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
    $req = $bdd->prepare($sql);
    $req->execute();
    // array to store the id
    $id_arr = array();
    while ($get_id = $req->fetch()) {
        $id_arr[] = $get_id['id']." ";
    }
    foreach($id_arr as $id) {
        // change value
        $sql = "UPDATE items SET type=:type WHERE id=:current_id";
        $req = $bdd->prepare($sql);
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
    $req = $bdd->prepare($sql);
    $result = $req->execute();
    if($result) {
        echo "Database successfully updated with default values.\n";
    } else {
        die($die_msg);
    }


}

// change outcome in status
// check if it exists first
$sql = "SELECT * from experiments";
$req = $bdd->prepare($sql);
$req->execute();
$test = $req->fetch();
if(isset($test['status'])) {
    echo "Column 'status' already exists. Nothing to do.\n";
} else {
    $sql = "ALTER TABLE `experiments` CHANGE `outcome` `status` VARCHAR( 255 ) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL";
    $req = $bdd->prepare($sql);
    $result = $req->execute();
    if($result) {
        echo "Outcome is now status.\n";
    } else {
        die($die_msg);
    }
}


// ADD visibility field in experiments table
add_field('experiments', 'visibility', "VARCHAR(255) NOT NULL", ">>> Experiments now have a visibility switch.\n", "Column 'visibility' already exists. Nothing to do.\n");
// put visibility = team everywhere
$sql = "UPDATE `experiments` SET `visibility` = 'team'";
$req = $bdd->prepare($sql);
$result = $req->execute();


// remove unused items_templates table
echo "Table items_templates...";
$sql = "DROP TABLE IF EXISTS `items_templates`";
$req = $bdd->prepare($sql);
$result = $req->execute();
if ($result) {
    echo "Nothing to do.\n";
} else {
    die($die_msg);
}
// remove unused users table
echo "Unused users columns...";
$sql = "SELECT * from users";
$req = $bdd->prepare($sql);
$req->execute();
$test = $req->fetch();
if(isset($test['is_jc_resp'])) {
    $sql = "ALTER TABLE `users` DROP `is_jc_resp`,DROP `is_pi`, DROP `journal`, DROP `last_jc`";
    $req = $bdd->prepare($sql);
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
$req = $bdd->prepare($sql);
$req->execute();
// if some dates are less than 8 char we make the update
if ($req->rowCount() > 0) {
    $sql = "UPDATE `experiments` SET date = date + 20000000 WHERE CHAR_LENGTH(`date`) = 6";
    $req = $bdd->prepare($sql);
    $req->execute();

    echo ">>> Dates are now YYYYMMDD in experiments.\n";
} else {
    echo "Dates are YYYYMMDD in experiments. Nothing to do.\n";
}


// same for items
$sql = "SELECT `date` FROM `items` WHERE CHAR_LENGTH(`date`) < 8";
$req = $bdd->prepare($sql);
$req->execute();
if ($req->rowCount() > 0) {
    $sql = "UPDATE `items` SET date = date + 20000000 WHERE CHAR_LENGTH(`date`) = 6";
    $req = $bdd->prepare($sql);
    $req->execute();
    echo ">>> Dates are now YYYYMMDD in the database.\n";

} else {
    echo "Dates are YYYYMMDD. Nothing to do.\n";
}

// ADD DELETABLE_XP CONFIG
// check if we need to add it

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

// ADD experiments_comments table
$sql = "SHOW TABLES";
$req = $bdd->prepare($sql);
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
    $req = $bdd->prepare($create_sql);
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
    $req = $bdd->prepare($sql);
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
        $req = $bdd->prepare($sql);
        $result = $req->execute();
        // update the lockedby field and put userid of experiment
        // to avoid users with already locked experiments being locked out
        $sql = "UPDATE experiments SET lockedby = userid WHERE locked = 1";
        $req = $bdd->prepare($sql);
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
$req = $bdd->prepare($sql);
$req->execute();
$column_is_here = false;
while ($show = $req->fetch()) {
    if (in_array('tags', $show)) {
        $column_is_here = true;
    }
}
if ($column_is_here) {
    $sql = "ALTER TABLE `items_types` DROP `tags`";
    $req = $bdd->prepare($sql);
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
$req = $bdd->prepare($sql);
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
    $req = $bdd->prepare($create_sql);
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
$req = $bdd->prepare($sql);
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
    $req = $bdd->prepare($create_sql);
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
    $req = $bdd->prepare($sql);
    $result2 = $req->execute();

    if($result && $result2) {
        echo "Table 'config' successfully created and populated.\n";
    } else {
        die($die_msg);
    }


} else {
    echo "Table 'config' already exists. Nothing to do.\n";
}


