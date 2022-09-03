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
use Elabftw\Enums\Action;
use Elabftw\Exceptions\DatabaseErrorException;
use Elabftw\Exceptions\FilesystemErrorException;
use Elabftw\Exceptions\IllegalActionException;
use Elabftw\Exceptions\ImproperActionException;
use Exception;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Deal with requests sent from the sysconfig page
 */
require_once dirname(__DIR__) . '/init.inc.php';

$tab = '1';
$query = '';
try {
    if (!$App->Session->get('is_sysadmin')) {
        throw new IllegalActionException('Non sysadmin user tried to access sysadmin controller.');
    }

    // TAB 1, 4 to 7 and 9
    if ($Request->request->has('updateConfig')) {
        if ($Request->request->has('lang')) {
            $tab = '1';
        }

        if ($Request->request->has('admin_validate')) {
            $tab = '5';
        }

        if ($Request->request->has('mail_from')) {
            $tab = '6';
        }

        if ($Request->request->has('saml_debug')) {
            $tab = '7';
        }

        if ($Request->request->has('extauth_remote_user')) {
            $tab = '9';
        }

        if ($Request->request->has('ldap_host')) {
            $tab = '10';
        }

        $App->Config->patch(Action::Update, $Request->request->all());
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
} catch (Exception $e) {
    $App->Log->error('', array(array('userid' => $App->Session->get('userid')), array('Exception' => $e)));
    $App->Session->getFlashBag()->add('ko', Tools::error());
} finally {
    $Response = new RedirectResponse('../../sysconfig.php?tab=' . $tab . $query);
    $Response->send();
}
