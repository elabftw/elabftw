<?php
/**
 * register-exec.php
 *
 * @author Nicolas CARPi <nicolas.carpi@curie.fr>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */
namespace Elabftw\Elabftw;

use Exception;
use Symfony\Component\HttpFoundation\RedirectResponse;

try {
    require_once '../init.inc.php';

    $Users = new Users(null, $Auth, $Config);

    // default location to redirect to
    $location = '../../login.php';

    // check for disabled local register
    if ($Users->Config->configArr['local_register'] === '0') {
        throw new Exception('Registration is disabled.');
    }

    // Stop bot registration by checking if the (invisible to humans) bot input is filled
    if (!empty($Request->request->get('bot'))) {
        throw new Exception('Only humans can register an account!');
    }

    if ((Tools::checkId($Request->request->get('team')) === false) ||
        !$Request->request->get('firstname') ||
        !$Request->request->get('lastname') ||
        !$Request->request->get('email') ||
        !filter_var($Request->request->get('email'), FILTER_VALIDATE_EMAIL)) {

        throw new Exception(_('A mandatory field is missing!'));
    }

    // Check whether the query was successful or not
    if (!$Users->create(
        $Request->request->get('email'),
        $Request->request->get('team'),
        $Request->request->get('firstname'),
        $Request->request->get('lastname'),
        $Request->request->get('password')
    )) {
        throw new Exception('Failed inserting new account in SQL!');
    }

    if ($Users->needValidation) {
        $Session->getFlashBag()->add('ok', _('Registration successful :)<br>Your account must now be validated by an admin.<br>You will receive an email when it is done.'));
    } else {
        $Session->getFlashBag()->add('ok', _('Registration successful :)<br>Welcome to eLabFTW o/'));
    }
    // store the email here so we can put it in the login field
    $Session->set('email', $Request->request->get('email'));

} catch (Exception $e) {
    $Session->getFlashBag()->add('ko', $e->getMessage());
    $location = '../../register.php';

} finally {
    $Response = new RedirectResponse($location);
    $Response->send();
}
