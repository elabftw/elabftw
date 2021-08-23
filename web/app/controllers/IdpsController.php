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
use Elabftw\Models\Idps;
use Exception;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Controller for IDPs
 */
require_once dirname(__DIR__) . '/init.inc.php';

$Response = new RedirectResponse('../../sysconfig.php?tab=7');

try {
    if (!$App->Session->get('is_sysadmin')) {
        throw new IllegalActionException('Non sysadmin user tried to access idps controller.');
    }

    $Idps = new Idps();

    // CREATE IDP
    if ($Request->request->has('idpsCreate')) {
        $Idps->create(
            $Request->request->get('name'),
            $Request->request->get('entityid'),
            $Request->request->get('ssoUrl'),
            $Request->request->get('ssoBinding'),
            $Request->request->get('sloUrl'),
            $Request->request->get('sloBinding'),
            $Request->request->get('x509'),
            $Request->request->get('active'),
            $Request->request->get('email_attr'),
            $Request->request->get('team_attr'),
            $Request->request->get('fname_attr'),
            $Request->request->get('lname_attr')
        );
    }

    // UPDATE IDP
    if ($Request->request->has('idpsUpdate')) {
        $Idps->update(
            (int) $Request->request->get('id'),
            $Request->request->get('name'),
            $Request->request->get('entityid'),
            $Request->request->get('ssoUrl'),
            $Request->request->get('ssoBinding'),
            $Request->request->get('sloUrl'),
            $Request->request->get('sloBinding'),
            $Request->request->get('x509'),
            $Request->request->get('active'),
            $Request->request->get('email_attr'),
            $Request->request->get('team_attr'),
            $Request->request->get('fname_attr'),
            $Request->request->get('lname_attr')
        );
    }

    $App->Session->getFlashBag()->add('ok', _('Saved'));
} catch (ImproperActionException $e) {
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
