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
    $config_msg = "
    %%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%
    The config file is now admin/config.php
    I will now write the new file for you, and delete the old file.
    If you want to do it manually, exit now (Ctrl-c).
    %%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%
    ";
    echo $config_msg;
    sleep(10);
    echo "Writing config file...\n";
    // get old config
    $ini_arr = parse_ini_file('admin/config.ini');
    // the new file to write to
    $config_file = 'admin/config.php';
    // what we will write
    $config = "<?php
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
// admin/config.php -- main configuration file for eLabFTW

/*
 * General settings
 */

// The name of the lab (shown in the footer)
define('LAB_NAME', '".$ini_arr['lab_name']."');

// if set to 1, user account will need admin validation before being able to login
define('ADMIN_VALIDATE', ".$ini_arr['admin_validate'].");

// the name of the custom link in menu
define('LINK_NAME', '".$ini_arr['link_name']."');

// the URL of the custom link
define('LINK_HREF', '".$ini_arr['link_href']."');

// the path of the install (absolute path) WITHOUT TRAILING SLASH
// on Windows it should be : 'C:<antislash>xampp<antislash>htdocs<antislash>elabftw'
// on GNU/Linux it might be : '/var/www/elabftw'
// on Mac OS X it might be : '/Applications/MAMP/htdocs'
define('PATH', '".$ini_arr['path']."');

// change to true to activate debug mode
define('DEBUG', false);

// proxy setting (to get updates)
define('PROXY', '".$ini_arr['proxy']."');


/*
 * Database settings
 */

// Host (generally localhost)
define('DB_HOST', '".$ini_arr['db_host']."');

// Name of the database
define('DB_NAME', '".$ini_arr['db_name']."');

// SQL username
define('DB_USER', '".$ini_arr['db_user']."');

// SQL Password (the one you chose in phpmyadmin)
define('DB_PASSWORD', '".$ini_arr['db_password']."');


/*
 * Email settings
 * You can leave these settings for later, because for the moment, 
 * they are only use when someone requests a new password.
 * You can use a free gmail account for this, but you can also use your company's SMTP server.
 */

// SMTP server address
define('SMTP_ADDRESS', '".$ini_arr['smtp_address']."');

// Port
define('SMTP_PORT', '".$ini_arr['smtp_port']."');

// Can be 'tls' or 'ssl'
define('SMTP_ENCRYPTION', '".$ini_arr['smtp_encryption']."');

// Username
define('SMTP_USERNAME', '".$ini_arr['smtp_username']."');

// Password
define('SMTP_PASSWORD', '".$ini_arr['smtp_password']."');

";

    // write content to file
    $result = file_put_contents($config_file, $config);
    if ($result) {
        echo "File written successfully. I will now delete the file admin/config.ini.\n";
        // remove old config file
        $unlink_result = unlink('admin/config.ini');
        if ($unlink_result) {
            echo "File admin/config.ini deleted.\n";
        } else {
            echo "There was a problem deleting the file admin/config.ini, please do it manually.\n";
        }
    } else {
        echo "There was a problem writing the new file admin/config.php. Please do it manually.\n";
        echo "Copy admin/config.php-EXAMPLE to admin/config.php and replace the values.\n";
    }
}


require_once('inc/connect.php');

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
add_field('experiments', 'lockedby', "INT UNSIGNED NULL AFTER locked", ">>> Now only the locker of an experiment can unlock it.\n", "Column 'lockedby' already exists. Nothing to do.\n");



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
    $path = substr(realpath(__FILE__), 0, -20);
    $create_sql = "CREATE TABLE IF NOT EXISTS `config` (
      `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
      `lab_name` VARCHAR(255) NOT NULL DEFAULT 'labname',
      `path` VARCHAR(255) NOT NULL DEFAULT $path,
      `admin_validate` tinyint(1) NOT NULL DEFAULT '0',
      `link_name` VARCHAR(255) NOT NULL DEFAULT 'Wiki',
      `link_href` VARCHAR(255) NOT NULL DEFAULT 'https://github.com/NicolasCARPi/elabftw/wiki',
      `smtp_address` VARCHAR(255) NULL,
      `smtp_port` VARCHAR(255) NULL,
      `smtp_encryption` VARCHAR(255) NULL,
      `smtp_username` VARCHAR(255) NULL,
      `smtp_password` VARCHAR(255) NULL,
      `proxy` VARCHAR(255) NULL,
      `debug` tinyint(1) NOT NULL DEFAULT '0',
      `deletable_xp` int(1) NOT NULL DEFAULT '0',
      PRIMARY KEY (`id`)
    ) ENGINE=InnoDB  DEFAULT CHARSET=utf8;";
    $req = $bdd->prepare($create_sql);
    $result = $req->execute();
    if($result) {
        echo "Table 'config' successfully created.\n";
    } else {
        die($die_msg);
    }
} else {
    echo "Table 'config' already exists. Nothing to do.\n";
}

