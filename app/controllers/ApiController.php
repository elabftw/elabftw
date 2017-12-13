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
require_once '../../config.php';
require_once ELAB_ROOT . 'vendor/autoload.php';

try {
    // create Request object
    $Request = Request::createFromGlobals();

    // do we have an API key?
    if (!$Request->server->has('HTTP_AUTHORIZATION')) {
        throw new Exception('No API key received.');
    }

    // verify the key and load user infos
    $Users = new Users();
    $Users->readFromApiKey($Request->server->get('HTTP_AUTHORIZATION'));

    $availMethods = array('GEiT', 'POSiT');
    if (!in_array($Request->server->get('REQUEST_METHOD'), $availMethods)) {
        throw new Exception('Incorrect HTTP verb! Available verbs are: ' . implode(', ', $availMethods));
    }

    // parse args
    $args = explode('/', rtrim($Request->query->get('req'), '/'));

    // assign the id if there is one
    $id = null;
    if (Tools::checkId(end($args))) {
        $id = end($args);
    }

    // assign the endpoint
    $endpoint = array_shift($args);

    // load Entity
    if ($endpoint === 'experiments') {
        $Entity = new Experiments($Users, $id);
    } elseif ($endpoint === 'items') {
        $Entity = new Database($Users, $id);
    } else {
        throw new Exception('Bad endpoint.');
    }

    $Api = new Api($Entity);

    // a simple GET
    if ($Request->server->get('REQUEST_METHOD') === 'GET') {
        $content = $Api->getEntity();

    // POST request
    } else {

        // FILE UPLOAD
        if ($Request->files->count() > 0) {
            $content = $Api->uploadFile($Request);

        // TITLE DATE BODY UPDATE
        } elseif ($Request->request->has('title')) {
            $content = $Api->updateEntity(
                $Request->request->get('title'),
                $Request->request->get('date'),
                $Request->request->get('body')
            );

        // ADD TAG
        } elseif ($Request->request->has('tag')) {
            $content = $Api->addTag($Request->request->get('tag'));

        // CREATE AN EXPERIMENT
        } else {
            if ($endpoint === 'experiments') {
                $content = $Api->createExperiment();
            } else {
                throw new Exception("Creating database items is not supported.");
            }
        }
    }
    // create response
    $Response = new JsonResponse($content);

} catch (Exception $e) {
    $Response = new JsonResponse(array('error', $e->getMessage()));

} finally {
    $Response->send();
}
