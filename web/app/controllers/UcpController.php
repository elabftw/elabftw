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
use Elabftw\Models\Templates;
use Exception;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Deal with requests sent from the user control panel
 */
require_once \dirname(__DIR__) . '/init.inc.php';
$tab = 1;
$Response = new RedirectResponse('../../ucp.php?tab=' . $tab);

try {
    // TAB 1 : PREFERENCES
    if ($Request->request->has('lang')) {
        $App->Users->updatePreferences($Request->request->all());
    }
    // END TAB 1

    // TAB 2 : ACCOUNT
    if ($Request->request->has('currpass')) {
        $tab = '2';
        $App->Users->updateAccount($Request->request->all());
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
        $tpl_body = Tools::checkBody($Request->request->get('new_tpl_body'));

        $Templates = new Templates($App->Users);
        $Templates->create($tpl_name, $tpl_body);
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

    $App->Session->getFlashBag()->add('ok', _('Saved'));
    $Response = new RedirectResponse('../../ucp.php?tab=' . $tab);

} catch (ImproperActionException $e) {
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
