<?php
/**
 * app/controllers/ApiController.php
 *
 * @author Nicolas CARPi <nicolas.carpi@curie.fr>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */
namespace Elabftw\Elabftw;

use Exception;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * This file is called without any auth, so we don't load init.inc.php but only what we need
 */
try {
    require_once '../../config.php';
    require_once ELAB_ROOT . 'vendor/autoload.php';

    // create Request object
    $Request = Request::createFromGlobals();
    // send to API
    $Api = new Api($Request);
    // create response
    $Response = new JsonResponse($Api->getContent());

} catch (Exception $e) {
    $Response = new JsonResponse(array('error', $e->getMessage()));

} finally {
    $Response->send();
}
