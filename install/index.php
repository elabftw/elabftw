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

/**
 * The default path in Docker is to automatically install the database schema
 * because the config file is already here. Otherwise, ask infos for creating it.
 *
 */
try {
    session_start();
    require_once '../vendor/autoload.php';
    $errflag = false;

    // Check if there is already a config file
    if (file_exists('../config.php')) {
        // ok there is a config file, but maybe it's a fresh install, so redirect to the register page
        // check that the config file is here and readable
        if (!is_readable('../config.php')) {
            $message = "No readable config file found. Make sure the server has permissions to read it. Try :<br />
                chmod 644 config.php<br />";
            throw new Exception($message);
        }

        // check if there are users registered
        require_once '../config.php';
        $pdo = Db::getConnection();
        // ok so we are connected, now count the number of tables before trying to count the users
        // if we are in docker, the number of tables might be 0
        // so we will need to import the structure before going further
        $sql = "SELECT COUNT(DISTINCT `table_name`) AS tablesCount
            FROM `information_schema`.`columns` WHERE `table_schema` = :db_name";
        $req = $pdo->prepare($sql);
        $req->bindValue(':db_name', DB_NAME);
        $req->execute();
        $res = $req->fetch();
        if ($res['tablesCount'] < 2) {
            // bootstrap MySQL database
            $sqlFile = __DIR__ . '/elabftw.sql';
            // temporary variable, used to store current query
            $queryline = '';
            // read in entire file
            $lines = file($sqlFile);
            // loop through each line
            foreach ($lines as $line) {
                // Skip it if it's a comment
                if (substr($line, 0, 2) == '--' || $line == '') {
                        continue;
                }

                // Add this line to the current segment
                $queryline .= $line;
                // If it has a semicolon at the end, it's the end of the query
                if (substr(trim($line), -1, 1) == ';') {
                    // Perform the query
                    $pdo->q($queryline);
                    // Reset temp variable to empty
                    $queryline = '';
                }
            }
            header('Location: ../register.php');
            throw new Exception('Redirecting to register page');
        }

        $sql = "SELECT * FROM users";
        $req = $pdo->prepare($sql);
        $req->execute();
        // redirect to register page if no users are in the database
        if ($req->rowCount() === 0) {
            header('Location: ../register.php');
            throw new Exception('Redirecting to register page');
        } else {
            $message = 'It looks like eLabFTW is already installed. Delete the config file if you wish to reinstall it.';
            throw new Exception($message);
        }
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
    <script src="../app/js/elabftw.min.js"></script>
    </head>

    <body>
    <section id="container" class='container'>
    <section id='real_container'>
    <center><img src='../app/img/logo.png' alt='elabftw' title='elabftw' /></center>
    <h2>Welcome to the install of eLabFTW</h2>

    <h3>Preliminary checks</h3>
    <?php
    // CHECK WE ARE WITH HTTPS
    if (!Tools::usingSsl()) {
        // get the url to display a link to click (without the port)
        $url = 'https://' . $_SERVER['SERVER_NAME'] . $_SERVER['PHP_SELF'];
        $message = "eLabFTW works only in HTTPS. Please enable HTTPS on your server. Or click this link : <a href='$url'>$url</a>";
        throw new Exception($message);
    }

    // CHECK PHP version
    if (!function_exists('version_compare') || version_compare(PHP_VERSION, '5.6', '<')) {
        $message = "Your version of PHP isn't recent enough. Please update your php version to at least 5.6";
        echo Tools::displayMessage($message, 'ko', false);
        $errflag = true;
    }

    // Check for hash function
    if (!function_exists('hash')) {
        $message = "You don't have the hash function. On Freebsd it's in /usr/ports/security/php5-hash.";
        throw new Exception($message);
    }

    // UPLOADS DIR
    if (!is_writable('../uploads') || !is_writable('../uploads/tmp')) {
        // create the folders
        mkdir('../uploads');
        mkdir('../uploads/tmp');
        // check the folders
        if (is_writable('../uploads') && is_writable('../uploads/tmp')) {
            $message = "The <em>uploads/</em> folder and its subdirectory were created successfully.";
            echo Tools::displayMessage($message, 'ok', false);
        } else { // failed at creating the folder
            $message = "Failed creating <em>uploads/</em> directory. You need to do it manually. 
                <a href='https://elabftw.readthedocs.io/en/stable/faq.html#failed-creating-uploads-directory'>Click here to discover how.</a>";
            $errflag = true;
        }
    }

    // Check for required php extensions
    $extensionArr = array('curl', 'gettext', 'gd', 'openssl', 'mbstring');
    foreach ($extensionArr as $ext) {
        if (!extension_loaded($ext)) {
            $message = "The <em>" . $ext . "</em> extension is <strong>NOT</strong> loaded.
                    <a href='https://elabftw.readthedocs.io/en/stable/faq.html#extension-is-not-loaded'>Click here to read how to fix this.</a>";
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
    <form action='install.php' method='post'>
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

    <div class='center' style='margin-top:8px'>
    <button type='button' id='test_sql_button' class='button'>Test MySQL connection to continue</button>
    </div>

    </fieldset>

    <br />

    <!-- FINAL SECTION -->
    <section id='final_section'>
    <p>When you click the button below, it will create the file <em>config.php</em>. If it cannot create it (because the server doesn't have write permission to this folder), your browser will download it and you will need to put it in the main elabftw folder.</p>
    <p>To put this file on the server, you can use scp (don't write the '$') :</p>
    <code>$ scp /path/to/config.php pi@12.34.56.78:/var/www/elabftw/</code>
    <p>If you want to modify some parameters afterwards, just edit this file directly.</p>

    <div class='center' style='margin-top:8px'>
        <button type="submit" name="Submit" class='button'>INSTALL eLabFTW</button>
    </div>

    <p>If the config.php file is in place, <button onclick='window.location.reload()'>reload this page</button></p>
    <p>You will be redirected to the registration page, where you can get your admin account :)</p>
    </section>

    </form>

    </section>

    </section>

    <script>
    $(document).ready(function() {
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
                    $('#test_sql_button').hide();
                    $('#final_section').show();
                } else {
                    alert('The connection failed with this error : ' + test_result);
                }
            });
        });
    });
    </script>
    <?php
} catch (Exception $e) {
    echo Tools::displayMessage($e->getMessage(), 'ko');
    echo "</section></section>";
} finally {
    echo "</body></html>";
}

