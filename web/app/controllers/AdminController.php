<?php
/**
 * app/controllers/AdminController.php
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
 * Deal with requests sent from the admin page
 *
 */
require_once \dirname(__DIR__) . '/init.inc.php';

$Response = new RedirectResponse("../../admin.php?tab=1");

try {

    if (!$App->Session->get('is_admin')) {
        throw new IllegalActionException('Non admin user tried to access admin controller.');
    }

    $Teams = new Teams($App->Users);

    // UPDATE TEAM SETTINGS
    if ($Request->request->has('teamsUpdateFull')) {
        $res = $Teams->update($Request->request->all());
    }

    // CLEAR STAMP PASS
    if ($Request->query->get('clearStamppass')) {
        $res = $Teams->destroyStamppass();
    }

    // DISPLAY RESULT
    if ($res) {
        $App->Session->getFlashBag()->add('ok', _('Configuration updated successfully.'));
    } else {
        $App->Session->getFlashBag()->add('ko', Tools::error());
    }

} catch (IllegalActionException $e) {
    $App->Log->notice('', array(array('userid' => $App->Session->get('userid')), array('IllegalAction', $e->__toString())));
    $App->Session->getFlashBag()->add('ko', Tools::error(true));

} catch (Exception $e) {
    $App->Log->error('', array(array('userid' => $App->Session->get('userid')), array('exception' => $e->__toString())));

} finally {
    $Response->send();
}
