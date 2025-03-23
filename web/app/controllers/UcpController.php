<?php

declare(strict_types=1);
/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2012, 2022 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

namespace Elabftw\Elabftw;

use Elabftw\Auth\Local;
use Elabftw\Controllers\LoginController;
use Elabftw\Enums\Action;
use Elabftw\Exceptions\DatabaseErrorException;
use Elabftw\Exceptions\IllegalActionException;
use Elabftw\Exceptions\ImproperActionException;
use Elabftw\Services\Filter;
use Elabftw\Services\MfaHelper;
use Exception;
use Symfony\Component\HttpFoundation\RedirectResponse;

use function dirname;

/**
 * Deal with requests sent from the user control panel
 */
require_once dirname(__DIR__) . '/init.inc.php';
$tab = 1;
$Response = new RedirectResponse(sprintf('/ucp.php?tab=%d', $tab));

$postData = $App->Request->request->all();
try {
    // TAB 2 : ACCOUNT
    if ($App->Request->request->getString('origin') === 'ucp_tab_2') {
        $tab = 2;
        // if user is authenticated through external service we skip the password verification
        if ($App->Users->userData['auth_service'] === LoginController::AUTH_LOCAL) {
            $App->Users->checkCurrentPasswordOrExplode($App->Request->request->getString('current_password'));
            // update the email if necessary
            if (isset($postData['email']) && ($postData['email'] !== $App->Users->userData['email'])) {
                $App->Users->patch(Action::Update, array('email' => $postData['email']));
            }
        }

        // CHANGE PASSWORD (only for local accounts)
        if (!empty($App->Request->request->getString('password'))
            && $App->Users->userData['auth_service'] === LoginController::AUTH_LOCAL
        ) {
            $App->Users->patch(Action::UpdatePassword, $postData);
        }

        // TWO FACTOR AUTHENTICATION
        $useMFA = Filter::onToBinary($postData['use_mfa'] ?? '');
        $MfaHelper = new MfaHelper($App->Users->userData['userid']);

        if ($useMFA && !$App->Users->userData['mfa_secret']) {
            // Need to request verification code to confirm user got secret and can authenticate in the future by MFA
            // so we will require mfa, redirect the user to login
            // which will pickup that enable_mfa is there so it will display the qr code to initialize the process
            // and after that we redirect on ucp back
            // the mfa_secret is not yet saved to the DB
            $App->Session->set('mfa_auth_required', true);
            $App->Session->set('mfa_secret', $MfaHelper->generateSecret());
            $App->Session->set('enable_mfa', true);
            $App->Session->set('mfa_redirect_origin', '/ucp.php?tab=2');

            // This will redirect user right away to verify mfa code
            $Response = new RedirectResponse('/login.php');
            $Response->send();
            exit;

            // Disable MFA if not enforced
        } elseif (!$useMFA
            && $App->Users->userData['mfa_secret']
            && !Local::isMfaEnforced(
                $App->Users->userData['userid'],
                (int) $App->Config->configArr['enforce_mfa'],
            )
        ) {
            $MfaHelper->removeSecret();
        }
    }
    // END TAB 2

    $App->Session->getFlashBag()->add('ok', _('Saved'));
    $Response = new RedirectResponse(sprintf('/ucp.php?tab=%d', $tab));
} catch (IllegalActionException $e) {
    $App->Log->notice('', array(array('userid' => $App->Session->get('userid')), array('IllegalAction', $e->getMessage())));
    $App->Session->getFlashBag()->add('ko', Tools::error(true));
} catch (ImproperActionException $e) {
    // show message to user
    $App->Session->getFlashBag()->add('ko', $e->getMessage());
} catch (DatabaseErrorException $e) {
    $App->Log->error('', array(array('userid' => $App->Session->get('userid')), array('Error', $e)));
} catch (Exception $e) {
    $App->Log->error('', array(array('userid' => $App->Session->get('userid')), array('Exception' => $e)));
    $App->Session->getFlashBag()->add('ko', Tools::error());
} finally {
    $Response->send();
}
