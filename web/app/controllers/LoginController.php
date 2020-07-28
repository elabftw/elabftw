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

        $rememberme = 'off';
        if ($Request->request->has('rememberme')) {
            $rememberme = $Request->request->get('rememberme');
        }

        // the actual login
        $team = null;
        if ($Session->has('team_selection_required')) {
            $team = (int) $Request->request->get('team_selection');
            $userid = $Session->get('auth_userid');

            $Session->remove('team_selection_required');
            $Session->remove('auth_userid');

            $Auth->loginInTeam($userid, $team);
        } else {
            // If checkCredentials fails there will be an exception and the subsequent code will not be executed.
            $userid = $Auth->checkCredentials($Request->request->get('email'), $Request->request->get('password'));
            $MFASecret = $Auth->getMFASecret($userid);

            if ($MFASecret && !$Session->has('mfa_secret')) {
                $Session->set('auth_userid', $userid);
                $Session->set('mfa_secret', $MFASecret);
                $Session->set('rememberme', $rememberme);
                $location = '../../login.php';

            } elseif (
                !$MFASecret
                || ($MFASecret
                    && $Request->request->has('mfa_code')
                    && $Auth->verifyMFACode($App->Session->get('mfa_secret'), (int) $Request->request->get('mfa_code'))
                   )
            ) {
                $loginResult = $Auth->login($userid, $rememberme);

                if ($loginResult && $Session->has('mfa_secret')) {
                    $Session->remove('mfa_secret');
                    $Session->remove('rememberme');
                }

                if ($loginResult === true) {
                    if ($Request->cookies->has('redirect')) {
                        $location = $Request->cookies->get('redirect');
                    } else {
                        $location = '../../experiments.php';
                    }
                } elseif (is_array($loginResult)) {
                    $Session->set('team_selection_required', 1);
                    $Session->set('auth_userid', $loginResult[0]);
                    $Session->set('team_selection', $loginResult[1]);
                    $location = '../../login.php';
                }
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
