<?php
/**
 * app/controllers/TodolistController.php
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
 *
 */
try {
    require_once '../../app/init.inc.php';

    $Todolist = new Todolist($_SESSION['userid']);

    if (isset($_POST['create'])) {
        $id = $Todolist->create($_POST['body']);
        if ($id) {
            echo json_encode(array(
                'res' => true,
                'id' => $id,
            ));
        } else {
            echo json_encode(array(
                'res' => false,
                'msg' => Tools::error()
            ));
        }
    }

    if (isset($_POST['read'])) {
        $todoItems = $Todolist->readAll();
        if (is_array($todoItems)) {
            echo json_encode(array(
                'res' => true,
                'todoItems' => $todoItems
            ));
        } else {
            echo json_encode(array(
                'res' => false,
                'msg' => Tools::error()
            ));
        }
    }

    if (isset($_POST['updateOrdering'])) {
        if ($Todolist->updateOrdering($_POST)) {
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

    if (isset($_POST['destroy'])) {
        if ($Todolist->destroy($_POST['id'])) {
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

    if (isset($_POST['destroyAll'])) {
        if ($Todolist->destroyAll()) {
            echo json_encode(array(
                'res' => true
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
}
