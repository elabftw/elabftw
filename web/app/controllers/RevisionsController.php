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
require_once \dirname(__DIR__) . '/init.inc.php';

try {
    if ($Request->query->get('type') === 'experiments') {
        $Entity = new Experiments($App->Users);

    } elseif ($_GET['type'] === 'items') {

        $Entity = new Database($App->Users);

    } else {
        throw new Exception('Bad type!');
    }

    $Entity->setId($Request->query->get('item_id'));
    $Entity->canOrExplode('write');
    $Revisions = new Revisions($Entity);

    if ($Request->query->get('action') === 'restore') {
        $revId = Tools::checkId($Request->query->get('rev_id'));
        if ($revId === false) {
            throw new Exception(_('The id parameter is not valid!'));
        }

        if ($Revisions->restore($revId)) {
            $Session->getFlashBag()->add('ok', _('Revision restored successfully.'));
        }

        header("Location: ../../" . $Entity->page . ".php?mode=view&id=" . $Request->query->get('item_id'));
    }
} catch (Exception $e) {
    $App->Logs->create('Error', $Session->get('userid'), $e->getMessage());
}
