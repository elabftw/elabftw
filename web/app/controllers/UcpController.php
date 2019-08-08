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
use Elabftw\Exceptions\InvalidCsrfTokenException;
use Elabftw\Models\ApiKeys;
use Elabftw\Models\Templates;
use Elabftw\Services\Filter;
use Exception;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Deal with requests sent from the user control panel
 */
require_once \dirname(__DIR__) . '/init.inc.php';
$tab = 1;
$Response = new RedirectResponse('../../ucp.php?tab=' . $tab);

try {
    // CSRF
    $App->Csrf->validate();

    // TAB 1 : PREFERENCES
    if ($Request->request->has('lang')) {
        $App->Users->updatePreferences($Request->request->all());
    }
    // END TAB 1

    // TAB 2 : ACCOUNT
    if ($Request->request->has('currpass')) {
        $tab = '2';
        // check that we got the good password
        if (!$Auth->checkCredentials($App->Users->userData['email'], $Request->request->get('currpass'))) {
            throw new ImproperActionException(_('Please input your current password!'));
        }
        $App->Users->updateAccount($Request->request->all());
    }

    // TAB 2 : CHANGE PASSWORD
    if (!empty($Request->request->get('newpass'))) {
        // check the confirm password
        if ($Request->request->get('newpass') !== $Request->request->get('cnewpass')) {
            throw new ImproperActionException(_('The passwords do not match!'));
        }

        $App->Users->updatePassword($Request->request->get('newpass'));
    }

    // END TAB 2

    // TAB 3 : EXPERIMENTS TEMPLATES

    // ADD NEW TPL
    if ($Request->request->has('new_tpl_form')) {
        $tab = '3';

        // template name must be 3 chars at least
        if (\mb_strlen($Request->request->get('new_tpl_name')) < 3) {
            throw new ImproperActionException(_('The template name must be 3 characters long.'));
        }

        $tpl_name = $Request->request->filter('new_tpl_name', null, FILTER_SANITIZE_STRING);
        $tpl_body = Filter::body($Request->request->get('new_tpl_body'));

        $Templates = new Templates($App->Users);
        $Templates->createNew($tpl_name, $tpl_body);
    }

    // EDIT TEMPLATES
    if ($Request->request->has('tpl_form')) {
        $tab = '3';

        $Templates = new Templates($App->Users);
        $Templates->updateTpl(
            (int) $Request->request->get('tpl_id')[0],
            $Request->request->get('tpl_name')[0],
            $Request->request->get('tpl_body')[0]
        );
    }

    // TAB 4 : CREATE API KEY
    if ($Request->request->has('createApiKey')) {
        $tab = '4';
        $ApiKeys = new ApiKeys($App->Users);
        $key = $ApiKeys->create(
            $Request->request->get('name'),
            (int) $Request->request->get('canWrite')
        );
        $Session->getFlashBag()->add('warning', sprintf(_("This is the only time the key will be shown! Make sure to copy it somewhere safe as you won't be able to see it again: %s"), $key));
    }

    $App->Session->getFlashBag()->add('ok', _('Saved'));
    $Response = new RedirectResponse('../../ucp.php?tab=' . $tab);
} catch (ImproperActionException | InvalidCsrfTokenException $e) {
    // show message to user
    $App->Session->getFlashBag()->add('ko', $e->getMessage());
} catch (IllegalActionException $e) {
    $App->Log->notice('', array(array('userid' => $App->Session->get('userid')), array('IllegalAction', $e->getMessage())));
    $App->Session->getFlashBag()->add('ko', Tools::error(true));
} catch (DatabaseErrorException | FilesystemErrorException $e) {
    $App->Log->error('', array(array('userid' => $App->Session->get('userid')), array('Error', $e)));
} catch (Exception $e) {
    $App->Log->error('', array(array('userid' => $App->Session->get('userid')), array('Exception' => $e)));
    $App->Session->getFlashBag()->add('ko', Tools::error());
} finally {
    $Response->send();
}
