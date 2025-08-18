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

use Elabftw\Controllers\LoginController;
use Elabftw\Enums\Action;
use Elabftw\Exceptions\AppException;
use Elabftw\Exceptions\DemoModeException;
use Elabftw\Params\UserParams;
use Elabftw\Services\MfaHelper;
use Exception;
use Symfony\Component\HttpFoundation\RedirectResponse;

use function dirname;

/**
 * Deal with requests sent from the user control panel
 */
require_once dirname(__DIR__) . '/init.inc.php';
$Response = new RedirectResponse('/ucp.php?tab=2');

try {
    if ($App->demoMode) {
        throw new DemoModeException();
    }
    $okMsg = _('Saved');
    $postData = $App->Request->request->all();
    // TAB 2 : ACCOUNT
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
        $okMsg = _('Password successfully changed.');
    }

    // ENABLE MFA
    if ($App->Request->request->has('mfa_secret') && !$App->Users->userData['mfa_secret']) {
        $MfaHelper = new MfaHelper($App->Request->request->getString('mfa_secret'));
        if ($MfaHelper->verifyCode($App->Request->request->getString('mfa_code'))) {
            $App->Users->update(new UserParams('mfa_secret', $App->Request->request->getString('mfa_secret')));
            $okMsg = _('Two-factor authentication has been successfully enabled for your account.');
        }
    }
    // END TAB 2

    $App->Session->getFlashBag()->add('ok', $okMsg);
} catch (AppException $e) {
    $Response = $e->getResponseFromException($App);
} catch (Exception $e) {
    $Response = $App->getResponseFromException($e);
} finally {
    $Response->send();
}
