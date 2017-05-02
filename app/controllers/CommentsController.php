<?php
/**
 * app/controllers/CommentsController.php
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
 * Controller for the experiments comments
 *
 */
try {
    require_once '../../app/init.inc.php';

    $Comments = new Comments(new Experiments($Users));

    // CREATE
    if (isset($_POST['commentsCreate'])) {
        $Comments->Entity->setId($_POST['id']);
        if ($Comments->create($_POST['comment'])) {
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

    // UPDATE
    if (isset($_POST['commentsUpdate'])) {
        if ($Comments->update($_POST['commentsUpdate'], $_POST['id'])) {
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
        if ($Comments->destroy($_POST['id'], $_SESSION['userid'])) {
            echo json_encode(array(
                'res' => true,
                'msg' => _('Comment successfully deleted')
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
