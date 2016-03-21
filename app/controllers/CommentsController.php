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

/**
 * Controller for the experiments comments
 *
 */
require_once '../../inc/common.php';

try {
    $comments = new \Elabftw\Elabftw\Comments();

    // CREATE
    if (isset($_POST['commentsCreate'])) {
        if ($comments->create(
            $_POST['commentsCreateId'],
            $_POST['commentsCreateComment'],
            $_SESSION['userid']
        )) {
            echo '1';
        } else {
            echo '0';
        }
    }

} catch (Exception $e) {
    dblog('Error', $_SESSION['userid'], $e->getMessage());
}
