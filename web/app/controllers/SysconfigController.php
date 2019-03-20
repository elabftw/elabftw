<?php
/**
 * @author Nicolas CARPi <nicolas.carpi@curie.fr>
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
use Exception;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Deal with requests sent from the sysconfig page
 */
require_once \dirname(__DIR__) . '/init.inc.php';

try {
    $tab = '1';
    if (!$App->Session->get('is_sysadmin')) {
        throw new IllegalActionException('Non sysadmin user tried to access sysadmin controller.');
    }

    // CLEAR SMTP PASS
    if ($Request->query->get('clearSmtppass')) {
        $tab = '6';
        $App->Config->update(array('smtp_password' => null));
    }

    // TAB 1 and 4 to 8
    if ($Request->request->has('updateConfig')) {
        if ($Request->request->has('lang')) {
            $tab = '1';
        }

        if ($Request->request->has('stampshare')) {
            $tab = '4';
        }

        if ($Request->request->has('admin_validate')) {
            $tab = '5';
        }

        if ($Request->request->has('mail_method')) {
            $tab = '6';
        }

        if ($Request->request->has('saml_debug')) {
            $tab = '7';
        }

        if ($Request->request->has('privacy_policy')) {
            $tab = '8';
        }

        $App->Config->update($Request->request->all());
    }

    // CLEAR STAMP PASS
    if ($Request->query->get('clearStamppass')) {
        $tab = '4';
        $App->Config->destroyStamppass();
    }

    $Session->getFlashBag()->add('ok', _('Saved'));

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
    $Response = new RedirectResponse("../../sysconfig.php?tab=" . $tab);
    $Response->send();
}
