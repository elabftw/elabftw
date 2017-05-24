<?php
/**
 * install/install.php
 *
 * @author Nicolas CARPi <nicolas.carpi@curie.fr>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

/**
 * This file reads infos from POST and creates the config.php file (unless it exists)
 *
 */
namespace Elabftw\Elabftw;

use Exception;
use Defuse\Crypto\Key as Key;

try {
    session_start();
    require_once '../vendor/autoload.php';

    // we disable errors to avoid having notice and warning polluting our file
    error_reporting(E_ERROR);

    // Check if there is already a config file, redirect to index if yes.
    if (file_exists('../config.php')) {
        header('Location: ../install/index.php');
        throw new Exception('Redirecting to install page');
    }

    // POST data
    if (isset($_POST['db_host']) && !empty($_POST['db_host'])) {
        $db_host = $_POST['db_host'];
    } else {
        throw new Exception('Bad POST data');
    }

    if (isset($_POST['db_name']) && !empty($_POST['db_name'])) {
        $db_name = $_POST['db_name'];
    } else {
        throw new Exception('Bad POST data');
    }

    if (isset($_POST['db_user']) && !empty($_POST['db_user'])) {
        $db_user = $_POST['db_user'];
    } else {
        throw new Exception('Bad POST data');
    }

    // the db pass can be empty on mac and windows install
    if (isset($_POST['db_password']) && !empty($_POST['db_password'])) {
        $db_password = $_POST['db_password'];
    }

    // BUILD CONFIG FILE

    // the new file to write to
    $config_file = '../config.php';
    $elab_root = dirname(dirname(__FILE__)) . '/';
    // make a new secret key
    $new_key = Key::createNewRandomKey();

    // what we will write in the file
    $config = "<?php
    define('DB_HOST', '" . $db_host . "');
    define('DB_NAME', '" . $db_name . "');
    define('DB_USER', '" . $db_user . "');
    define('DB_PASSWORD', '" . $db_password . "');
    define('ELAB_ROOT', '" . $elab_root . "');
    define('SECRET_KEY', '" . $new_key->saveToAsciiSafeString() . "');";

    // we try to write content to file and propose the file for download if we can't write to it
    if (file_put_contents($config_file, $config)) {
        // it's cool, we managed to write the config file
        // let's put restricting permissions on it as discussed in #129
        if (is_writable($config_file)) {
            chmod($config_file, 0400);
        }
        $infos_arr = array();
        $infos_arr[] = 'Congratulations, you successfully installed eLabFTW, 
        now you need to <strong>register</strong> your account (you will have full admin rights).';
        $_SESSION['ok'] = $infos_arr;
        // redirect to install/index.php to import SQL structure
        header('Location: index.php');

    } else {
        header('Content-Type: text/x-delimtext; name="config.php"');
        header('Content-disposition: attachment; filename=config.php');
        echo $config;
    }
} catch (Exception $e) {
    echo Tools::displayMessage('Error: ' . $e->getMessage(), 'ko', false);
}
