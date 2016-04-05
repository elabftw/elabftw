<?php
/**
 * app/controllers/CommentsController.php
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
 * Controller for the experiments comments
 *
 */
require_once '../../inc/common.php';

try {

    // CREATE
    if (isset($_POST['commentsCreate'])) {
        $comments = new Comments(new Experiments($_SESSION['userid'], $_POST['itemId']));
        if ($comments->create($_POST['comment'])) {
            echo '1';
        } else {
            echo '0';
        }
    }

    // UPDATE
    if (isset($_POST['commentsUpdateComment'])) {
        $comments = new Comments(new Experiments($_SESSION['userid']), $_POST['id']);
        if ($comments->update($_POST['commentsUpdateComment'])) {
            echo '1';
        } else {
            echo '0';
        }
    }

    // DESTROY
    $comments = new Comments(new Experiments($_SESSION['userid']), $_POST['id']);
    if (isset($_POST['commentsDestroy'])) {
        if ($comments->destroy()) {
            echo '1';
        } else {
            echo '0';
        }
    }

} catch (Exception $e) {
    $Logs = new Logs();
    $Logs->create('Error', $_SESSION['userid'], $e->getMessage());
}
