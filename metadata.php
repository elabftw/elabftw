<?php
/**
 *  SAML Metadata view
 */
namespace Elabftw\Elabftw;

use OneLogin_Saml2_Error;
use OneLogin_Saml2_Settings;

try {
    require_once 'app/init.inc.php';

    $Saml = new Saml(new Config, new Idps);
    $settingsArr = $Saml->getSettings();

    // Now we only validate SP settings
    $Settings = new OneLogin_Saml2_Settings($settingsArr, true);
    $metadata = $Settings->getSPMetadata();
    $errors = $Settings->validateMetadata($metadata);
    if (empty($errors)) {
        header('Content-Type: text/xml');
        echo $metadata;
    } else {
        throw new OneLogin_Saml2_Error(
            'Invalid SP metadata: '.implode(', ', $errors),
            OneLogin_Saml2_Error::METADATA_SP_INVALID
        );
    }
} catch (Exception $e) {
    echo $e->getMessage();
}
