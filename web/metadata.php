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
use OneLogin\Saml2\Error;
use OneLogin\Saml2\Settings;
use Symfony\Component\HttpFoundation\Response;

require_once 'app/init.inc.php';

try {

    $Saml = new Saml(new Config, new Idps);
    // TODO this is the id of the idp to use to get the settings
    $settingsArr = $Saml->getSettings(1);
    if (empty($settingsArr['sp']['entityId'])) {
        throw new Exception('No Service Provider configured. Aborting.');
    }

    // Now we only validate SP settings
    $Settings = new Settings($settingsArr, true);
    $metadata = $Settings->getSPMetadata();
    $errors = $Settings->validateMetadata($metadata);
    if (empty($errors)) {
        $Response = new Response();
        $Response->prepare($Request);
        $Response->setContent($metadata);
        $Response->headers->set('Content-Type', 'text/xml');
        $Response->send();
    } else {
        throw new Error(
            'Invalid SP metadata: ' . implode(', ', $errors),
            Error::METADATA_SP_INVALID
        );
    }

} catch (Exception $e) {
    $Response = new Response();
    $Response->prepare($Request);
    $Response->setContent($e->getMessage());
    $Response->send();
}
