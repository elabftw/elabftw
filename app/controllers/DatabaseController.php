<?php
/**
 * app/controllers/DatabaseController.php
 *
 * @author Nicolas CARPi <nicolas.carpi@curie.fr>
 * @copyright 2012 Nicolas CARPi
 * @see http://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

namespace Elabftw\Elabftw;

use \Exception;

/**
 * Database
 *
 */
require_once '../../inc/common.php';

$mode = 'show';

try {

    // CREATE
    if (isset($_GET['databaseCreateId'])) {
        $database = new Database($_SESSION['team_id']);
        $id = $database->create($_GET['databaseCreateId']);
        $mode = 'edit';
    }

    // UPDATE
    if (isset($_POST['databaseUpdate'])) {
        $database = new Database($_SESSION['team_id']);
        $database->setId($_POST['databaseId']);
        if ($database->update(
            $_POST['databaseUpdateTitle'],
            $_POST['databaseUpdateDate'],
            $_POST['databaseUpdateBody'],
            $_SESSION['userid']
        )) {
            $id = $database->id;
            $mode = 'view';
        } else {
            throw new Exception('Error');
        }
    }

    // DUPLICATE
    if (isset($_GET['databaseDuplicateId'])) {
        $database = new Database($_SESSION['team_id'], $_GET['databaseDuplicateId']);
        $id = $database->duplicate();
        $mode = 'edit';
    }

} catch (Exception $e) {
    dblog('Error', $_SESSION['userid'], $e->getMessage());
    $_SESSION['ko'][] = $e->getMessage();
} finally {
    header("location: ../../database.php?mode=" . $mode . "&id=" . $id);
}
