<?php
/**
 * app/logout.php
 *
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */
declare(strict_types=1);

namespace Elabftw\Elabftw;

use Elabftw\Models\AuthenticatedUser;
use Elabftw\Models\Idps;
use OneLogin\Saml2\Auth as SamlAuthLib;
use Symfony\Component\HttpFoundation\RedirectResponse;

require_once 'init.inc.php';

$redirectUrl = '../login.php';

if ($App->Users instanceof AuthenticatedUser) {
    $App->Users->invalidateToken();
}

// now if we are logged in through external auth, hit the external auth url
if ($App->Session->get('is_auth_by') === 'external') {
    $redirectUrl = $App->Config->configArr['logout_url'];
    if (empty($redirectUrl)) {
        $redirectUrl = '../login.php';
    }
}

// kill session
$App->Session->invalidate();
// options to disable a cookie
$cookieOptions = array(
    'expires' => time() - 3600,
    'path' => '/',
    'domain' => '',
    'secure' => true,
    'httponly' => true,
    'samesite' => 'Strict',
);
setcookie('token', '', $cookieOptions);
setcookie('token_team', '', $cookieOptions);
setcookie('icanhazcookies', '', $cookieOptions);
// if we get redirected by init.inc.php we want to keep this cookie
// if the user requested logout, remove it
if (!$Request->query->get('keep_redirect')) {
    setcookie('redirect', '', $cookieOptions);
}
setcookie('pdf_sig', '', $cookieOptions);
setcookie('kickreason', '', $cookieOptions);

// this will be present if we logged in through SAML
if ($App->Session->get('is_auth_by') === 'saml') {
    // initiate SAML SLO
    $Saml = new Saml($App->Config, new Idps());
    $settings = $Saml->getSettings();
    $samlAuthLib = new SamlAuthLib($settings);
    $samlAuthLib->logout();
} else {
    // and redirect to login page or ext auth logout url
    $Response = new RedirectResponse($redirectUrl);
    $Response->send();
}
