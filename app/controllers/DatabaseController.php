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

use Exception;

/**
 * Database
 *
 */
try {
    require_once '../../app/init.inc.php';

    $Entity = new Database($Users);

    $mode = 'show';
    $id = '';
    $redirect = false;


    // CREATE
    if (isset($_GET['databaseCreateId'])) {
        $redirect = true;
        // can raise an exception
        $id = $Entity->create($_GET['databaseCreateId']);
        $mode = 'edit';
    }

    // UPDATE
    if (isset($_POST['update'])) {
        $Entity->setId($_POST['id']);
        $Entity->canOrExplode('write');

        if ($Entity->update(
            $_POST['title'],
            $_POST['date'],
            $_POST['body']
        )) {
            $id = $Entity->id;
            $mode = 'view';
            $redirect = true;
        } else {
            throw new Exception('Error');
        }
    }

    // DUPLICATE
    if (isset($_GET['duplicateId'])) {
        $Entity->setId($_GET['duplicateId']);
        $Entity->canOrExplode('read');

        $id = $Entity->duplicate();
        $mode = 'edit';
        $redirect = true;
    }

    // UPDATE RATING
    if (isset($_POST['rating'])) {
        $Entity->setId($_POST['id']);
        $Entity->canOrExplode('write');

        if ($Entity->updateRating($_POST['rating'])) {
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
        $Entity->setId($_POST['id']);
        if ($Entity->destroy()) {
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
