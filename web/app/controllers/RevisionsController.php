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

    $Entity->setId((int) $Request->query->get('item_id'));
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
    $App->Log->error('', array(array('userid' => $App->Session->get('userid')), array('exception' => $e)));
    $Session->getFlashBag()->add('ko', $e->getMessage());
    header("Location: ../../" . $Entity->page . ".php?mode=view&id=" . $Request->query->get('item_id'));
}
