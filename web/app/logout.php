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

use function dirname;
use Elabftw\Models\Config;
use Elabftw\Models\Idps;
use OneLogin\Saml2\Auth as SamlAuthLib;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session;

require_once dirname(__DIR__, 2) . '/vendor/autoload.php';
require_once dirname(__DIR__, 2) . '/config.php';

$Config = new Config();
$Session = new Session();
$Session->start();
$Request = Request::createFromGlobals();

$redirectUrl = '../login.php';

// now if we are logged in through external auth, hit the external auth url
if ($Session->get('is_auth_by') === 'external') {
    $redirectUrl = $Config->configArr['logout_url'];
    if (empty($redirectUrl)) {
        $redirectUrl = '../login.php';
    }
}

// kill session
$Session->invalidate();
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
// if we get redirected by init.inc.php we want to keep this cookie
// if the user requested logout, remove it
if (!$Request->query->get('keep_redirect')) {
    setcookie('redirect', '', $cookieOptions);
}
setcookie('pdf_sig', '', $cookieOptions);

// this will be present if we logged in through SAML
if ($Session->get('is_auth_by') === 'saml') {
    //if ($Session->get('is_auth_by_saml')) {
    // initiate SAML SLO
    $Saml = new Saml(new Config(), new Idps());
    $settings = $Saml->getSettings();
    $samlAuthLib = new SamlAuthLib($settings);
    $samlAuthLib->logout();
} else {
    // and redirect to login page or ext auth logout url
    $Response = new RedirectResponse($redirectUrl);
    $Response->send();
}
