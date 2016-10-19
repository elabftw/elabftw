<?php
/**
 * app/controllers/DatabaseController.php
 *
 * @author Nicolas CARPi <nicolas.carpi@curie.fr>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

namespace Elabftw\Elabftw;

use \Exception;

/**
 * Database
 *
 */
require_once '../../app/init.inc.php';

$mode = 'show';
$id = '';
$redirect = false;

try {

    $Database = new Database($_SESSION['team_id']);

    // CREATE
    if (isset($_GET['databaseCreateId'])) {
        $redirect = true;
        // can raise an exception
        $id = $Database->create($_GET['databaseCreateId'], $_SESSION['userid']);
        $mode = 'edit';
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
        $id = $Database->duplicate($_SESSION['userid']);
        $mode = 'edit';
        $redirect = true;
    }

    // UPDATE RATING
    if (isset($_POST['rating'])) {
        $Database->setId($_POST['id']);
        if ($Database->updateRating($_POST['rating'])) {
            echo json_encode(array(
                'res' => true,
                'msg' => _('Saved')
            ));
        } else {
            echo json_encode(array(
                'res' => false,
                'msg' => Tools::error()
            ));
        }
    }

    // DESTROY
    if (isset($_POST['destroy'])) {
        $Database->setId($_POST['id']);
        if ($Database->destroy()) {
            echo json_encode(array(
                'res' => true,
                'msg' => _('Item deleted successfully')
            ));
        } else {
            echo json_encode(array(
                'res' => false,
                'msg' => Tools::error()
            ));
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
