<?php
/**
 * install/test.php
 *
 * @author Nicolas CARPi <nicolas.carpi@curie.fr>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

/**
 * Check if we can connect to database
 */
namespace Elabftw\Elabftw;

use Exception;
use PDO;

try {
    // Check if there is already a config file
    if (file_exists('../config.php')) {
        throw new Exception('Remove config file.');
    }

    // MYSQL
    if (isset($_POST['mysql'])) {
        $pdo_options = array();
        $pdo_options[PDO::ATTR_ERRMODE] = PDO::ERRMODE_EXCEPTION;
        $pdo = new PDO(
            'mysql:host=' . $_POST['db_host'] . ';dbname=' . $_POST['db_name'],
            $_POST['db_user'],
            $_POST['db_password'],
            $pdo_options
        );
        echo 1;
    }

} catch (Exception $e) {
    echo $e->getMessage();
}
