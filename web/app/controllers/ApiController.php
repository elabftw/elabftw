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

use Elabftw\Exceptions\DatabaseErrorException;
use Elabftw\Exceptions\FilesystemErrorException;
use Elabftw\Exceptions\IllegalActionException;
use Elabftw\Exceptions\ImproperActionException;
use Exception;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * This file is called without any auth, so we don't load init.inc.php but only what we need
 */
require_once \dirname(__DIR__, 3) . '/config.php';
require_once \dirname(__DIR__, 3) . '/vendor/autoload.php';

$Response = new JsonResponse(array('error' => Tools::error()));

try {
    // create Request object
    $Request = Request::createFromGlobals();

    // do we have an API key?
    if (!$Request->server->has('HTTP_AUTHORIZATION')) {
        throw new ImproperActionException('No API key received.');
    }

    // verify the key and load user infos
    $Users = new Users();
    $Users->readFromApiKey($Request->server->get('HTTP_AUTHORIZATION'));

    $availMethods = array('GET', 'POST');
    if (!\in_array($Request->server->get('REQUEST_METHOD'), $availMethods, true)) {
        throw new ImproperActionException('Incorrect HTTP verb! Available verbs are: ' . implode(', ', $availMethods));
    }

    // parse args
    $args = explode('/', rtrim($Request->query->get('req'), '/'));

    // assign the id if there is one
    $id = null;
    if (Tools::checkId((int) end($args)) !== false) {
        $id = (int) end($args);
    }

    // assign the endpoint
    $endpoint = array_shift($args);

    // load Entity
    if ($endpoint === 'uploads') {
        if ($id === null) {
            throw new ImproperActionException('You need to specify an ID');
        }
        $Entity = new Uploads();
        $uploadData = $Entity->readFromId($id);
        // check user owns the file
        // we could also check if user has read access to the item
        // but for now let's just restrict downloading file via API to owned files
        if ($uploadData['userid'] != $Users->userid) {
            throw new IllegalActionException('User tried to download file without permission.');
        }
        $filePath = \dirname(__DIR__, 3) . '/uploads/' . $uploadData['long_name'];
        $Response = new Response(\file_get_contents($filePath));

    } elseif ($endpoint === 'experiments') {
        $Entity = new Experiments($Users, $id);
    } elseif ($endpoint === 'items') {
        $Entity = new Database($Users, $id);
    } else {
        throw new IllegalActionException('Bad endpoint.');
    }

    if ($Entity instanceof Experiments || $Entity instanceof Database) {
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

            // ADD LINK
            } elseif ($Request->request->has('link')) {
                $content = $Api->addLink((int) $Request->request->get('link'));


            // CREATE AN EXPERIMENT
            } else {
                if ($endpoint === 'experiments') {
                    $content = $Api->createExperiment();
                } else {
                    throw new ImproperActionException('Creating database items is not supported.');
                }
            }
        }
    }

} catch (ImproperActionException $e) {
    $Response->setData(array(
        'error' => $e->getMessage()
    ));

} catch (IllegalActionException $e) {
    $App->Log->notice('', array(array('userid' => $App->Session->get('userid')), array('IllegalAction', $e)));
    $Response->setData(array(
        'error' => Tools::error(true)
    ));

} catch (DatabaseErrorException | FilesystemErrorException $e) {
    $App->Log->error('', array(array('userid' => $App->Session->get('userid')), array('Error', $e)));
    $Response->setData(array(
        'error' => $e->getMessage()
    ));

} catch (Exception $e) {
    $App->Log->error('', array(array('userid' => $App->Session->get('userid')), array('exception' => $e)));

} finally {
    $Response->send();
}
