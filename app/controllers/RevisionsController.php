<?php
/**
 * app/controllers/RevisionsController.php
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
 * Revisions controller
 */
try {
    require_once '../../app/init.inc.php';

    $Users = new Users($_SESSION['userid']);
    if ($_GET['type'] === 'experiments') {
        $Entity = new Experiments($Users, $_GET['item_id']);
        $location = '../../experiments';

    } elseif ($_GET['type'] === 'items') {

        $Entity = new Database($Users, $_GET['item_id']);
        $location = '../../database';

    } else {
        throw new Exception('Bad type!');
    }

    $Entity->canOrExplode('write');
    $Revisions = new Revisions($Entity);

    if (isset($_GET['action']) && $_GET['action'] === 'restore') {
        $revId = Tools::checkId($_GET['rev_id']);
        if ($revId === false) {
            throw new Exception(_('The id parameter is not valid!'));
        }

        $Revisions->restore($revId);

        header("Location: " . $location . ".php?mode=view&id=" . $_GET['item_id']);
    }
} catch (Exception $e) {
    echo json_encode(array('error', $e->getMessage()));
}
