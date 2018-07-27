<?php
/**
 * LoginController.php
 *
 * @author Nicolas CARPi <nicolas.carpi@curie.fr>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */
namespace Elabftw\Elabftw;

use Exception;
use OneLogin_Saml2_Auth;
use Symfony\Component\HttpFoundation\RedirectResponse;

require_once \dirname(__DIR__) . '/init.inc.php';

// default location for redirect
$location = '../../login.php';

try {
    $FormKey = new FormKey($Session);
    $Saml = new Saml($App->Config, new Idps);
    $Teams = new Teams($App->Users);

    if ($Request->request->has('idp_id')) { // login with SAML
        $idpId = (int) $Request->request->get('idp_id');
        $settings = $Saml->getSettings($idpId);
        $SamlAuth = new OneLogin_Saml2_Auth($settings);
        $returnUrl = $settings['baseurl'] . "/index.php?acs&idp=" . $idpId;
        $SamlAuth->login($returnUrl);

    } elseif ($Request->request->has('team_id') && $App->Config->configArr['anon_users']) { // login as anonymous
        if ($Teams->isExisting((int) $Request->request->get('team_id'))) {
            $App->Users->Auth->loginAsAnon((int) $Request->request->get('team_id'));
            if ($Request->cookies->has('redirect')) {
                $location = $Request->cookies->get('redirect');
            } else {
                $location = '../../experiments.php';
            }
        }

    } else {

        // FORMKEY
        if (!$Request->request->has('formkey') || !$FormKey->validate($Request->request->get('formkey'))) {
            throw new Exception(_("Your session expired. Please retry."));
        }

        // EMAIL
        if (!$Request->request->has('email') || !$Request->request->has('password')) {
            throw new Exception(_('A mandatory field is missing!'));
        }

        if ($Request->request->has('rememberme')) {
            $rememberme = $Request->request->get('rememberme');
        } else {
            $rememberme = 'off';
        }

        // increase failed attempts counter
        if (!$Session->has('failed_attempt')) {
            $Session->set('failed_attempt', 1);
        } else {
            $n = $Session->get('failed_attempt');
            $n++;
            $Session->set('failed_attempt', $n);
        }
        // the actual login
        if ($App->Users->Auth->login($Request->request->get('email'), $Request->request->get('password'), $rememberme)) {
            if ($Request->cookies->has('redirect')) {
                $location = $Request->cookies->get('redirect');
            } else {
                $location = '../../experiments.php';
            }
        } else {
            // log the attempt if the login failed
            $App->Log->warning('Failed login attempt', array('ip' => $_SERVER['REMOTE_ADDR']));
            // inform the user
            $Session->getFlashBag()->add(
                'ko',
                _("Login failed. Either you mistyped your password or your account isn't activated yet.")
            );
        }
    }

} catch (Exception $e) {
    $Session->getFlashBag()->add('ko', $e->getMessage());
}

$Response = new RedirectResponse($location);
$Response->send();
