<?php
/**
 * metadata.php
 *
 * @author Nicolas CARPi <nicolas.carpi@curie.fr>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

/**
 *  This page displays an XML file with all the infos of the Service Provider
 */
namespace Elabftw\Elabftw;

use Exception;
use OneLogin_Saml2_Error;
use OneLogin_Saml2_Settings;

require_once 'app/init.inc.php';

try {

    $Saml = new Saml(new Config, new Idps);
    $settingsArr = $Saml->getSettings();
    if (empty($settingsArr['sp']['entityId'])) {
        throw new Exception('No Service Provider configured. Aborting.');
    }

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
