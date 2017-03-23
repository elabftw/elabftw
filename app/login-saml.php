<?php
namespace Elabftw\Elabftw;

use OneLogin_Saml2_Auth;

require_once 'init.inc.php';

$Saml = new Saml();

$settings = $Saml->getSettings();

//require_once('../vendor/onelogin/php-saml/_toolkit_loader.php');
$auth = new OneLogin_Saml2_Auth($settings);
$auth->login('https://elab.local/index.php?acs');
