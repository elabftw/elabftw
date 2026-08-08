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

use Elabftw\Auth\AnonymousLoginValidator;
use Elabftw\Auth\LoginFlow;
use Elabftw\Auth\MfaPolicy;
use Elabftw\Auth\MfaVerifier;
use Elabftw\Auth\PasswordRenewalPolicy;
use Elabftw\Auth\RememberMe;
use Elabftw\Auth\SamlRequestState;
use Elabftw\Auth\SelectableTeamsProvider;
use Elabftw\Auth\UserLoginValidator;
use Elabftw\Controllers\LoginController;
use Elabftw\Enums\EnforceMfa;
use Elabftw\Enums\Entrypoint;
use Elabftw\Exceptions\AppException;
use Exception;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;

require_once 'app/init.inc.php';

$Response = new Response();

try {
    if (
        $App->Request->query->has('acs')
        && $App->Request->request->has('SAMLResponse')
    ) {
        $enforceMfa = EnforceMfa::from((int) $App->Config->configArr['enforce_mfa']);
        $loginFlow = new LoginFlow(
            new MfaPolicy($enforceMfa),
            new PasswordRenewalPolicy((int) $App->Config->configArr['max_password_age_days']),
            new SelectableTeamsProvider(),
            new UserLoginValidator(),
        );
        $Response = new LoginController(
            $App->Config->configArr,
            $App->Request,
            $App->Session,
            $loginFlow,
            new MfaVerifier($App->Session),
            new AnonymousLoginValidator((bool) $App->Config->configArr['anon_users']),
            new RememberMe(
                $App->Request,
                $App->Config->configArr['remember_me_allowed'] === '1',
            ),
            new SamlRequestState($App->Request),
            $App->demoMode,
        )->getResponse();
    } else {
        $location = '/' . (Entrypoint::tryFrom($App->Users->userData['entrypoint'] ?? 0) ?? Entrypoint::Dashboard)->toPage();
        $Response = new RedirectResponse($location);
    }
} catch (AppException $e) {
    $Response = $e->getResponseFromException($App);
} catch (Exception $e) {
    $Response = $App->getResponseFromException($e);
} finally {
    $Response->send();
}
