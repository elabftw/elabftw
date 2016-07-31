<?php
/**
 * app/controllers/EntityController.php
 *
 * @author Nicolas CARPi <nicolas.carpi@curie.fr>
 * @copyright 2012 Nicolas CARPi
 * @see http://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */
namespace Elabftw\Elabftw;

use Exception;

/**
 * Deal with things common to experiments and items like tags and uploads
 *
 */
require_once '../../app/init.inc.php';

try {

    // QUICKSAVE
    if (isset($_POST['quickSave'])) {
        $title = Tools::checkTitle($_POST['title']);

        $body = Tools::checkBody($_POST['body']);

        $date = Tools::kdate($_POST['date']);

        if ($_POST['type'] == 'experiments') {

            $Experiments = new Experiments($_SESSION['userid'], $_POST['id']);
            $result = $Experiments->update($title, $date, $body);

        } elseif ($_POST['type'] == 'items') {

            $Database = new Database($_SESSION['team_id'], $_POST['id']);
            $result = $Database->update($title, $date, $body, $_SESSION['userid']);
        }

        if ($result) {
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

    // CREATE TAG
    if (isset($_POST['createTag'])) {
        if ($_POST['createTagType'] === 'experiments') {
            $Entity = new Experiments($_SESSION['userid'], $_POST['createTagId']);
        } else {
            $Entity = new Database($_SESSION['team_id'], $_POST['createTagId']);
        }
        $Tags = new Tags($_POST['createTagType'], $Entity->id);
        $Tags->create($_POST['createTagTag']);
    }

    // DELETE TAG
    if (isset($_POST['destroyTag'])) {
        if ($_POST['type'] === 'experiments') {
            $Entity = new Experiments($_SESSION['userid'], $_POST['item']);
        } else {
            $Entity = new Database($_SESSION['team_id'], $_POST['item']);
        }
        $Tags = new Tags($_POST['type'], $Entity->id);
        $Tags->destroy($_SESSION['userid'], $_POST['id']);
    }


    // CREATE UPLOAD
    if (isset($_POST['upload'])) {
        $Upload = new Uploads($_POST['type'], $_POST['item_id']);
        $Upload->create($_FILES);
    }
    // DESTROY UPLOAD
    if (isset($_POST['uploadsDestroy'])) {
        $Uploads = new Uploads($_POST['type'], $_POST['item_id'], $_POST['id']);
        if ($Uploads->destroy()) {
            echo json_encode(array(
                'res' => true,
                'msg' => _('File deleted successfully')
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
