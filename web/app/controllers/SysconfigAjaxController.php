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
use Elabftw\Exceptions\UnauthorizedException;
use Elabftw\Maps\Team;
use Elabftw\Models\Idps;
use Elabftw\Models\Teams;
use Elabftw\Services\Email;
use Exception;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * Deal with ajax requests sent from the sysconfig page or full form from sysconfig.php
 */
require_once \dirname(__DIR__) . '/init.inc.php';

$Response = new JsonResponse();
$Response->setData(array(
    'res' => true,
    'msg' => _('Saved'),
));

try {
    if (!$App->Session->get('is_sysadmin')) {
        throw new IllegalActionException('Non sysadmin user tried to access sysadmin controller.');
    }

    $Teams = new Teams($App->Users);

    // CREATE TEAM
    if ($Request->request->has('teamsCreate')) {
        $Teams->create($Request->request->get('teamsName'));
    }

    // UPDATE TEAM
    if ($Request->request->has('teamsUpdate')) {
        $Team = new Team((int) $Request->request->get('id'));
        $Team->hydrate($Request->request->all());
        $Team->save();
    }

    // DESTROY TEAM
    if ($Request->request->has('teamsDestroy')) {
        $Teams->destroy((int) $Request->request->get('teamsDestroyId'));
    }

    // SEND TEST EMAIL
    if ($Request->request->has('testemailSend')) {
        $Email = new Email($App->Config, $App->Users);
        $Email->testemailSend($Request->request->get('testemailEmail'));
    }

    // SEND MASS EMAIL
    if ($Request->request->has('massEmail')) {
        $Email = new Email($App->Config, $App->Users);
        $Email->massEmail($Request->request->get('subject'), $Request->request->get('body'));
    }

    // DESTROY IDP
    if ($Request->request->has('idpsDestroy')) {
        $Idps = new Idps();
        $Idps->destroy((int) $Request->request->get('id'));
    }
} catch (ImproperActionException | InvalidCsrfTokenException | UnauthorizedException $e) {
    $Response->setData(array(
        'res' => false,
        'msg' => $e->getMessage(),
    ));
} catch (IllegalActionException $e) {
    $App->Log->notice('', array(array('userid' => $App->Session->get('userid')), array('IllegalAction', $e)));
    $Response->setData(array(
        'res' => false,
        'msg' => Tools::error(true),
    ));
} catch (DatabaseErrorException | FilesystemErrorException $e) {
    $App->Log->error('', array(array('userid' => $App->Session->get('userid')), array('Error', $e)));
    $Response->setData(array(
        'res' => false,
        'msg' => $e->getMessage(),
    ));
} catch (Exception $e) {
    $App->Log->error('', array(array('userid' => $App->Session->get('userid')), array('exception' => $e)));
} finally {
    $Response->send();
}
