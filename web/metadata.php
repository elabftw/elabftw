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

use Elabftw\Exceptions\AppException;
use Elabftw\Exceptions\ImproperActionException;
use Elabftw\Exceptions\ResourceNotFoundException;
use Elabftw\Models\Config;
use Elabftw\Models\Idps;
use Exception;
use OneLogin\Saml2\Error;
use OneLogin\Saml2\Settings;
use Symfony\Component\HttpFoundation\Response;

/**
 *  This page displays an XML file with all the settings of the Service Provider
 */
require_once 'app/init.inc.php';

$Response = new Response();

try {
    $Response->prepare($App->Request);
    $IdpsHelper = new IdpsHelper(Config::getConfig(), new Idps($App->Users));
    try {
        $settingsArr = $IdpsHelper->getSettings();
    } catch (ResourceNotFoundException) {
        throw new ImproperActionException('No Service Provider configured. Aborting.');
    }

    // Now we only validate SP settings
    $Settings = new Settings($settingsArr, true);
    $metadata = $Settings->getSPMetadata();
    $errors = $Settings->validateMetadata($metadata);
    if (empty($errors)) {
        $Response->setContent($metadata);
        $Response->headers->set('Content-Type', 'text/xml');
    } else {
        throw new Error(
            'Invalid SP metadata: ' . implode(', ', $errors),
            Error::METADATA_SP_INVALID
        );
    }
} catch (AppException $e) {
    $Response = $e->getResponseFromException($App);
} catch (Exception $e) {
    $Response = $App->getResponseFromException($e);
} finally {
    $Response->send();
}
