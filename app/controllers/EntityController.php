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
require_once '../../inc/common.php';

try {
    // CREATE TAG
    if (isset($_POST['createTag'])) {
        if ($_POST['createTagType'] === 'experiments') {
            $Entity = new Experiments($_SESSION['userid'], $_POST['createTagId']);
        } else {
            $Entity = new Database($_SESSION['team_id']);
            $Entity->setId($_POST['createTagId']);
        }
        $Tags = new Tags($_POST['createTagType'], $Entity->id);
        $Tags->create($_POST['createTagTag']);
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
            echo '1';
        } else {
            echo '0';
        }
    }
} catch (Exception $e) {
    $Logs = new Logs();
    $Logs->create('Error', $_SESSION['userid'], $e->getMessage());
}
