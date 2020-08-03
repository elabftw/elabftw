<?php declare(strict_types=1);
/**
 * app/logout.php
 *
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

namespace Elabftw\Elabftw;

use Elabftw\Models\Config;
use Elabftw\Models\Idps;
use OneLogin\Saml2\Auth as SamlAuth;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Session\Session;

require_once \dirname(__DIR__, 2) . '/vendor/autoload.php';
require_once \dirname(__DIR__, 2) . '/config.php';

$Config = new Config();
$Session = new Session();
$Session->start();

$redirectUrl = '../login.php';
$doSLO = false;

// now if we are logged in through external auth, hit the external auth url
if ($Session->get('is_ext_auth')) {
    $redirectUrl = $Config->configArr['logout_url'];
}
if ($Session->has('samlUserdata')) {
    $doSLO = true;
}

// kill session
$Session->invalidate();
// disable token cookie
setcookie('token', '', time() - 3600, '/', '', true, true);

// this will be present if we logged in through SAML
if ($doSLO) {
    // initiate SAML SLO
    $Saml = new Saml(new Config, new Idps);
    $SamlAuth = new SamlAuth($Saml->getSettings());
    $SamlAuth->logout();
} else {
    // and redirect to login page or ext auth logout url
    $Response = new RedirectResponse($redirectUrl);
    $Response->send();
}
