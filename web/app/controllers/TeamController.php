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

use function dirname;
use Elabftw\Exceptions\DatabaseErrorException;
use Elabftw\Exceptions\FilesystemErrorException;
use Elabftw\Exceptions\IllegalActionException;
use Elabftw\Exceptions\ImproperActionException;
use Elabftw\Exceptions\InvalidCsrfTokenException;
use Elabftw\Services\Email;
use Exception;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Actions from team.php
 */
require_once dirname(__DIR__) . '/init.inc.php';

$Response = new RedirectResponse('../../team.php?tab=4');
try {
    // NOT FOR ANON
    if ($App->Session->get('is_anon')) {
        throw new IllegalActionException('Anonymous user tried to send email to team');
    }

    // CSRF
    $App->Csrf->validate();

    // EMAIL TEAM
    if ($Request->request->has('emailTeam')) {
        $Email = new Email($App->Config, $App->Users);
        $sent = $Email->massEmail($Request->request->get('subject'), $Request->request->get('body'), true);
        $App->Session->getFlashBag()->add('ok', sprintf(_('Email sent to %d users'), $sent));
    }
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
