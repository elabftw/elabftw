<?php
/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */
declare(strict_types=1);

namespace Elabftw\Elabftw;

use Elabftw\Exceptions\DatabaseErrorException;
use Elabftw\Exceptions\FilesystemErrorException;
use Elabftw\Exceptions\IllegalActionException;
use Elabftw\Exceptions\ImproperActionException;
use Elabftw\Exceptions\InvalidCsrfTokenException;
use Elabftw\Maps\Team;
use Elabftw\Models\Teams;
use Exception;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Deal with requests sent from the admin page
 *
 */
require_once \dirname(__DIR__) . '/init.inc.php';

$Response = new RedirectResponse('../../admin.php?tab=1');

try {
    if (!$App->Session->get('is_admin')) {
        throw new IllegalActionException('Non admin user tried to access admin controller.');
    }

    // CSRF
    $App->Csrf->validate();

    $Teams = new Teams($App->Users);

    // UPDATE TEAM SETTINGS (first tab of admin panel)
    if ($Request->request->has('teamsUpdateFull')) {
        $Team = new Team((int) $App->Users->userData['team']);
        $Team->hydrate($Request->request->all());
        $Team->save();
    }

    // CLEAR STAMP PASS
    if ($Request->query->get('clearStamppass')) {
        $Teams->destroyStamppass();
    }

    // DISPLAY RESULT
    $App->Session->getFlashBag()->add('ok', _('Saved'));
} catch (ImproperActionException | InvalidCsrfTokenException $e) {
    // show message to user
    $App->Session->getFlashBag()->add('ko', $e->getMessage());
} catch (IllegalActionException $e) {
    $App->Log->notice('', array(array('userid' => $App->Session->get('userid')), array('IllegalAction', $e)));
    $App->Session->getFlashBag()->add('ko', Tools::error(true));
} catch (DatabaseErrorException | FilesystemErrorException $e) {
    $App->Log->error('', array(array('userid' => $App->Session->get('userid')), array('Error', $e)));
    $App->Session->getFlashBag()->add('ko', $e->getMessage());
} catch (Exception $e) {
    $App->Log->error('', array(array('userid' => $App->Session->get('userid')), array('Exception' => $e)));
    $App->Session->getFlashBag()->add('ko', Tools::error());
} finally {
    $Response->send();
}
