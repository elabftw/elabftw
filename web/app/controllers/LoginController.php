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
use Elabftw\Exceptions\InvalidCredentialsException;
use Elabftw\Exceptions\InvalidCsrfTokenException;
use Elabftw\Models\Idps;
use Elabftw\Models\Teams;
use Exception;
use OneLogin\Saml2\Auth as SamlAuth;
use Symfony\Component\HttpFoundation\RedirectResponse;

require_once \dirname(__DIR__) . '/init.inc.php';

// default location for redirect
$location = '../../login.php';
$Response = new RedirectResponse($location);

try {
    $Saml = new Saml($App->Config, new Idps);
    $Teams = new Teams($App->Users);

    // LOGIN WITH SAML
    if ($Request->request->has('saml_login')) {
        $settings = $Saml->getSettings();
        $SamlAuth = new SamlAuth($settings);
        $returnUrl = $settings['baseurl'] . '/index.php?acs';
        $SamlAuth->login($returnUrl);

    // login as anonymous
    } elseif ($Request->request->has('team_id') && $App->Config->configArr['anon_users']) {
        if ($Teams->isExisting((int) $Request->request->get('team_id'))) {
            $Auth->loginAsAnon((int) $Request->request->get('team_id'));
            if ($Request->cookies->has('redirect')) {
                $location = $Request->cookies->get('redirect');
            } else {
                $location = '../../experiments.php';
            }
        }
    } else {
        // CSRF
        $App->Csrf->validate();

        // EMAIL
        if (!$Request->request->has('email') || !$Request->request->has('password')) {
            //throw new ImproperActionException(_('A mandatory field is missing!'));
        }

        $App->Session->set('rememberme', 'off');
        if ($Request->request->has('rememberme')) {
            $App->Session->set('rememberme', $Request->request->get('rememberme'));
        }

        if (!$App->Session->has('auth_userid')) {
            // If checkCredentials fails there will be an exception and the subsequent code will not be executed.
            $userid = $Auth->checkCredentials($Request->request->get('email'), $Request->request->get('password'));
            $App->Session->set('auth_userid', $userid);
        }

        $Mfa = new Mfa($App->Request, $App->Session);
        // redirect to MFA code verification if necesssary
        $Mfa->needVerification($App->Session->get('auth_userid'), '../../loginController.php');

        // the actual login
        $team = null;
        if ($App->Session->has('team_selection_required')) {
            $Auth->loginInTeam(
                $App->Session->get('auth_userid'),
                (int) $Request->request->get('team_selection'),
                $App->Session->get('rememberme')
            );

            $App->Session->remove('rememberme');
            $App->Session->remove('team_selection_required');
            $App->Session->remove('auth_userid');
        } else {
            $loginResult = $Auth->login($App->Session->get('auth_userid'), $App->Session->get('rememberme'));
 
            if ($loginResult === true) {
                $App->Session->remove('rememberme');
                $App->Session->remove('auth_userid');
 
                if ($Request->cookies->has('redirect')) {
                    $location = $Request->cookies->get('redirect');
                } else {
                    $location = '../../experiments.php';
                }
            } elseif (is_array($loginResult)) {
                $App->Session->set('team_selection_required', 1);
                $App->Session->set('team_selection', $loginResult);
                $location = '../../login.php';
            }
        }
    }
    $Response = new RedirectResponse($location);
} catch (ImproperActionException | InvalidCsrfTokenException | InvalidCredentialsException $e) {
    // show message to user
    $App->Session->getFlashBag()->add('ko', $e->getMessage());
} catch (IllegalActionException $e) {
    $App->Log->notice('', array(array('ip' => $_SERVER['REMOTE_ADDR']), array('IllegalAction' => $e)));
    $App->Session->getFlashBag()->add('ko', Tools::error(true));
} catch (DatabaseErrorException | FilesystemErrorException $e) {
    $App->Log->error('', array(array('ip' => $_SERVER['REMOTE_ADDR']), array('Error' => $e)));
    $App->Session->getFlashBag()->add('ko', $e->getMessage());
} catch (Exception $e) {
    $App->Log->error('', array(array('ip' => $_SERVER['REMOTE_ADDR']), array('Exception' => $e)));
    $App->Session->getFlashBag()->add('ko', Tools::error());
} finally {
    $Response->send();
}
