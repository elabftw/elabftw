<?php
/**
 * install/index.php
 *
 * @author Nicolas CARPi <nicolas.carpi@curie.fr>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */
namespace Elabftw\Elabftw;

use Exception;
use RuntimeException;
use Symfony\Component\HttpFoundation\Request;

/**
 * The default path in Docker is to automatically install the database schema
 * because the config file is already here. Otherwise, ask infos for creating it.
 *
 */
session_start();
require_once \dirname(__DIR__, 2) . '/vendor/autoload.php';
$configFilePath = \dirname(__DIR__, 2) . '/config.php';
$errflag = false;
// we disable errors to avoid having notice stopping the redirect
error_reporting(E_ERROR);


try {
    // create Request object
    $Request = Request::createFromGlobals();

    // Check if there is already a config file
    if (file_exists($configFilePath)) {
        // ok there is a config file, but maybe it's a fresh install, so redirect to the register page
        // check that the config file is here and readable
        if (!is_readable($configFilePath)) {
            $message = 'No readable config file found. Make sure the server has permissions to read it. Try :<br />
                chmod 644 config.php<br />';
            throw new Exception($message);
        }

        // check if there are users registered
        require_once $configFilePath;
        $Db = Db::getConnection();
        // ok so we are connected, now count the number of tables before trying to count the users
        // if we are in docker, the number of tables might be 0
        // so we will need to import the structure before going further
        $sql = 'SELECT COUNT(DISTINCT `table_name`) AS tablesCount
            FROM `information_schema`.`columns` WHERE `table_schema` = :db_name';
        $req = $Db->prepare($sql);
        $req->bindValue(':db_name', \DB_NAME);
        $req->execute();
        $res = $req->fetch();
        if ($res['tablesCount'] < 2) {
            // bootstrap MySQL database
            $sqlFile = \dirname(__DIR__, 2) . '/src/sql/structure.sql';
            // temporary variable, used to store current query
            $queryline = '';
            // read in entire file
            $lines = file($sqlFile);
            // loop through each line
            foreach ($lines as $line) {
                // Skip it if it's a comment
                if ($line === '' || strpos($line, '--') === 0) {
                    continue;
                }

                // Add this line to the current segment
                $queryline .= $line;
                // If it has a semicolon at the end, it's the end of the query
                if (trim($line)[\mb_strlen(trim($line)) - 1] === ';') {
                    // Perform the query
                    $Db->q($queryline);
                    // Reset temp variable to empty
                    $queryline = '';
                }
            }
            $Config = new Config();
            $Teams = new Teams(new Users());
            $Teams->create('Default team');
            header('Location: ../register.php');
            throw new Exception('Redirecting to register page');
        }

        $sql = 'SELECT * FROM users';
        $req = $Db->prepare($sql);
        $req->execute();
        // redirect to register page if no users are in the database
        if ($req->rowCount() === 0) {
            header('Location: ../register.php');
            throw new Exception('Redirecting to register page');
        }
        $message = 'It looks like eLabFTW is already installed. Delete the config.php file if you wish to reinstall it.';
        throw new Exception($message);
    }
    ?>
    <!DOCTYPE HTML>
    <html>
    <head>
    <meta http-equiv="Content-type" content="text/html;charset=UTF-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="author" content="Nicolas CARPi" />
    <meta name='referrer' content='origin'>
    <link rel="icon" type="image/ico" href="../app/img/favicon.ico" />
    <title>eLabFTW - INSTALL</title>
    <!-- CSS -->
    <link rel="stylesheet" media="all" href="../app/css/elabftw.min.css" />
    <!-- JAVASCRIPT -->
    <script src="../app/js/main.bundle.js?v=200"></script>
    <script src="../app/js/elabftw.min.js?v=200"></script>
    </head>

    <body>
    <section id="container" class='container'>
    <section id='real_container'>
    <center><img src='../app/img/logo.png' alt='elabftw' title='elabftw' /></center>
    <h2>Welcome to the install of eLabFTW</h2>

    <h3>Preliminary checks</h3>
    <?php
    // if we are not in https, die saying we work only in https
    if (!$Request->isSecure() && !$Request->server->has('HTTP_X_FORWARDED_PROTO')) {
        // get the url to display a link to click (without the port)
        $url = Tools::getUrlFromRequest($Request);
        // not pretty but gets the job done
        $url = str_replace('install/', '', $url);
        $url = str_replace(':80', ':443', $url);
        $message = "eLabFTW works only in HTTPS. Please enable HTTPS on your server. Or click this link : <a href='" .
            $url . "'>$url</a>";
        throw new Exception($message);
    }

    // Check for hash function
    if (!function_exists('hash')) {
        $message = "You don't have the hash function. On Freebsd it's in /usr/ports/security/php5-hash.";
        throw new Exception($message);
    }

    // same doc url for cache and uploads folder
    $docUrl = 'https://doc.elabftw.net/faq.html#failed-creating-uploads-directory';

    // CACHE FOLDER
    $cacheDir = dirname(__DIR__, 2) . '/cache';
    if (!is_dir($cacheDir) && !mkdir($cacheDir, 0700) && !is_dir($cacheDir)) {
        $message = sprintf(
            "Unable to create 'cache' folder! (%s) You need to do it manually. %sClick here to discover how%s.",
            $cacheDir,
            '<a href=' . $docUrl . '>',
            '</a>'
        );
        $errflag = true;
        throw new RuntimeException($message);
    } else {
        $message = "The 'cache' folder was created successfully.";
        echo Tools::displayMessage($message, 'ok', false);
    }

    // UPLOADS FOLDER
    $uploadsDir = dirname(__DIR__, 2) . '/uploads';

    if (!is_dir($uploadsDir) && !mkdir($uploadsDir, 0700) && !is_dir($uploadsDir)) {
        $message = sprintf(
            "Unable to create 'uploads' folder! (%s) You need to do it manually. %sClick here to discover how%s.",
            $uploadsDir,
            '<a href=' . $docUrl . '>',
            '</a>'
        );
        $errflag = true;
        throw new RuntimeException($message);
    } else {
        $message = "The 'uploads' folder was created successfully.";
        echo Tools::displayMessage($message, 'ok', false);
    }

    // Check for required php extensions
    $extensionArr = array('curl', 'gettext', 'gd', 'openssl', 'mbstring');
    foreach ($extensionArr as $ext) {
        if (!extension_loaded($ext)) {
            $message = "The <em>" . $ext . "</em> extension is <strong>NOT</strong> loaded.
                    <a href='https://doc.elabftw.net/faq.html#extension-is-not-loaded'>Click here to read how to fix this.</a>";
            $errflag = true;
        }
    }

    if ($errflag) {
        throw new Exception($message);
    }

    $message = 'Everything is good on your server. You can install eLabFTW :)';
    echo Tools::displayMessage($message, 'ok', false);
    ?>
    <h3>Configuration</h3>

    <!-- MYSQL -->
    <form action='install.php' id='install-form' method='post'>
    <fieldset>
    <legend><strong>MySQL</strong></legend>
    <p>MySQL is the database that will store everything. eLabFTW need to connect to it with a username/password. This is <strong>NOT</strong> your account with which you'll use eLabFTW. If you followed the installation instructions, you should have created a database <em>elabftw</em> with a user <em>elabftw</em> that have all the rights on it.</p>

    <p>
    <label for='db_host'>Host for mysql database:</label><br />
    <input id='db_host' name='db_host' type='text' value='localhost' />
    <p class='smallgray'>(you can safely leave 'localhost' here)</p>
    </p>

    <p>
    <label for='db_name'>Name of the database:</label><br />
    <input id='db_name' name='db_name' type='text' value='elabftw' />
    <p class='smallgray'>(should be 'elabftw' if you followed the instructions)</p>
    </p>

    <p>
    <label for='db_user'>Username to connect to the MySQL server:</label><br />
    <input id='db_user' name='db_user' type='text' value='<?php
    // we show root here if we're on windoze or Mac OS X
    if (PHP_OS == 'WINNT' || PHP_OS == 'WIN32' || PHP_OS == 'Windows' || PHP_OS == 'Darwin') {
        echo 'root';
    } else {
        echo 'elabftw';
    }
    ?>' />
    <p class='smallgray'>(should be 'elabftw' or 'root' if you're on Mac/Windows)</p>
    </p>

    <p>
    <label for='db_password'>Password:</label><br />
    <input id='db_password' name='db_password' type='password' />
    <p class='smallgray'>(should be a very complicated one that you won't have to remember)</p>
    </p>

    <div class='text-center mt-2'>
    <button type='button' id='test_sql_button' class='button'>Test MySQL connection to continue</button>
    </div>

    </fieldset>

    <br />

    <!-- FINAL SECTION -->
    <section id='final_section'>
    <p>When you click the button below, it will create the file <em>config.php</em>. If it cannot create it (because the server doesn't have write permission to this folder), your browser will download it and you will need to put it in the main elabftw folder.</p>
    <p>To put this file on the server, you can use scp (don't write the '$') :</p>
    <code>$ scp /path/to/downloaded/config.php your-user@12.34.56.78:<?= dirname(__DIR__, 2) ?></code>
    <p>If you want to modify some parameters afterwards, just edit this file directly.</p>

    <div class='text-center mt-2'>
        <button type="submit" name="Submit" class='button'>INSTALL eLabFTW</button>
    </div>
    </form>

    <p>If the config.php file is in place, <button class='button click2reload'>reload this page</button></p>
    <p>You will be redirected to the registration page, where you can get your admin account :)</p>

    </section>

    </section>

    </section>

    <script src='../app/js/install.min.js'></script>
    <?php
} catch (Exception $e) {
    echo Tools::displayMessage($e->getMessage(), 'ko', false);
    echo "</section></section>";
} finally {
    echo "</body></html>";
}
