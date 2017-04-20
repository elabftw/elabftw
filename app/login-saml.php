<?php
namespace Elabftw\Elabftw;

use OneLogin_Saml2_Auth;

require_once 'init.inc.php';

$Saml = new Saml(new Idps());

$settings = $Saml->getSettings($_POST['idp_id']);

$auth = new OneLogin_Saml2_Auth($settings);
$returnUrl = "https://elab.local/index.php?acs&idp=" . $_POST['idp_id'];
$auth->login($returnUrl);
