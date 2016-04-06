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
$id = '1';
$redirect = false;

try {

    $Database = new Database($_SESSION['team_id']);

    // CREATE
    if (isset($_GET['databaseCreateId'])) {
        $id = $Database->create($_GET['databaseCreateId']);
        $mode = 'edit';
        $redirect = true;
    }

    // UPDATE
    if (isset($_POST['update'])) {
        $Database->setId($_POST['id']);
        if ($Database->update(
            $_POST['title'],
            $_POST['date'],
            $_POST['body'],
            $_SESSION['userid']
        )) {
            $id = $Database->id;
            $mode = 'view';
            $redirect = true;
        } else {
            throw new Exception('Error');
        }
    }

    // DUPLICATE
    if (isset($_GET['databaseDuplicateId'])) {
        $Database->setId($_GET['databaseDuplicateId']);
        $id = $Database->duplicate();
        $mode = 'edit';
        $redirect = true;
    }

    // UPDATE RATING
    if (isset($_POST['rating'])) {
        $Database->setId($_POST['id']);
        if ($Database->updateRating($_POST['rating'])) {
            echo '1';
        } else {
            echo '0';
        }
    }

    // DESTROY
    if (isset($_POST['destroy'])) {
        $Database->setId($_POST['id']);
        if ($Database->destroy()) {
            echo '1';
        } else {
            echo '0';
        }
    }

} catch (Exception $e) {
    $Logs = new Logs();
    $Logs->create('Error', $_SESSION['userid'], $e->getMessage());
    $_SESSION['ko'][] = Tools::error();

} finally {
    if ($redirect) {
        header("location: ../../database.php?mode=" . $mode . "&id=" . $id);
    }
}
