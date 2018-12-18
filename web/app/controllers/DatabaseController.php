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
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Database
 *
 */
require_once \dirname(__DIR__) . '/init.inc.php';

try {
    if ($App->Session->has('anon')) {
        throw new IllegalActionException('Anonymous user tried to access database controller.');
    }

    $Entity = new Database($App->Users);
    if ($Request->request->has('id')) {
        $Entity->setId((int) $Request->request->get('id'));
    }

    // CREATE
    if ($Request->query->has('create')) {
        $id = $Entity->create($Request->query->get('create'));
        $Response = new RedirectResponse("../../database.php?mode=edit&id=" . $id);
    }

} catch (IllegalActionException $e) {
    $App->Log->notice('', array(array('userid' => $App->Session->get('userid')), array('IllegalAction', $e->__toString())));
    $App->Session->getFlashBag()->add('ko', Tools::error(true));

} catch (Exception $e) {
    $App->Log->error('', array(array('userid' => $App->Session->get('userid')), array('exception' => $e->__toString())));
    $Session->getFlashBag()->add('ko', Tools::error());
    $Response = new RedirectResponse("../../database.php");
} finally {
    $Response->send();
}
