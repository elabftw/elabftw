<?php
/**
 * app/controllers/ExperimentsController.php
 *
 * @author Nicolas CARPi <nicolas.carpi@curie.fr>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */
declare(strict_types=1);

namespace Elabftw\Elabftw;

use Elabftw\Exceptions\IllegalActionException;
use Exception;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Experiments
 *
 */
require_once \dirname(__DIR__) . '/init.inc.php';

$Response = new RedirectResponse('../../experiments.php');

try {

    if ($App->Session->has('anon')) {
        throw new IllegalActionException('Anonymous user tried to access experiments controller.');
    }

    $Entity = new Experiments($App->Users);
    if ($Request->request->has('id')) {
        $Entity->setId((int) $Request->request->get('id'));
    }

    // CREATE EXPERIMENT
    if ($Request->query->has('create')) {
        $id = $Entity->create((int) $Request->query->get('tpl'));
        $Response = new RedirectResponse('../../experiments.php?mode=edit&id=' . $id);
    }

} catch (IllegalActionException $e) {
    $App->Log->notice('', array(array('userid' => $App->Session->get('userid')), array('IllegalAction', $e->__toString())));
    $App->Session->getFlashBag()->add('ko', Tools::error(true));

} catch (Exception $e) {
    $App->Log->error('', array(array('userid' => $App->Session->get('userid')), array('exception' => $e->__toString())));
    $Session->getFlashBag()->add('ko', Tools::error());
    $Response = new RedirectResponse("../../experiments.php");
} finally {
    $Response->send();
}
