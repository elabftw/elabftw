<?php
/**
 * \Elabftw\Elabftw\Api
 *
 * @author Nicolas CARPi <nicolas.carpi@curie.fr>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */
namespace Elabftw\Elabftw;

use Exception;
use Symfony\Component\HttpFoundation\Request;

/**
 * An API for elab
 *
 */
class Api
{
    /** the Request object */
    private $Request;

    /** the id of the entity */
    public $id = null;

    /** our entity object */
    private $Entity;

    /** the output */
    private $content;

    /**
     * Get data for user from the API key
     *
     * @param Request $request
     */
    public function __construct(Request $request)
    {
        $this->Request = $request;

        // do we have an API key?
        if (!$this->Request->server->has('HTTP_AUTHORIZATION')) {
            throw new Exception('No API key received.');
        }
        // verify the key and load user infos
        $Users = new Users();
        $Users->readFromApiKey($this->Request->server->get('HTTP_AUTHORIZATION'));


        $availMethods = array('GET', 'POST');
        if (!in_array($this->Request->server->get('REQUEST_METHOD'), $availMethods)) {
            throw new Exception('Incorrect HTTP verb! Available verbs are: ' . implode($availMethods, ', '));
        }

        // parse args
        $args = explode('/', rtrim($this->Request->query->get('req'), '/'));

        // assign the id if there is one
        if (Tools::checkId(end($args))) {
            $this->id = end($args);
        }

        // assign the endpoint
        $endpoint = array_shift($args);

        // load Entity
        if ($endpoint === 'experiments') {
            $this->Entity = new Experiments($Users, $this->id);
        } elseif ($endpoint === 'items') {
            $this->Entity = new Database($Users, $this->id);
        } else {
            throw new Exception('Bad endpoint.');
        }

        // a simple GET
        if ($this->Request->server->get('REQUEST_METHOD') === 'GET') {
            $this->content = $this->getEntity();

        // POST request
        } else {

            // file upload
            if ($this->Request->files->count() > 0) {
                $this->content = $this->uploadFile();
            // title date body update
            } elseif ($this->Request->request->has('title')) {
                $this->content = $this->updateEntity();
            } else {
                // create an experiment
                if ($endpoint === 'experiments') {
                    $this->content = $this->createExperiment();
                } else {
                    throw new Exception("Creating database items is not supported.");
                }
            }
        }
    }


    /**
     * Return the response
     *
     * @return array
     */
    public function getContent()
    {
        return $this->content;
    }
    /**
     * Create an experiment
     *
     * @return array
     */
    public function createExperiment()
    {
        $id = $this->Entity->create();

        return array('id' => $id);
    }

    /**
     * Read an entity in full
     *
     * @return array<string,array>
     */
    public function getEntity()
    {
        $Uploads = new Uploads($this->Entity);
        $uploadedFilesArr = $Uploads->readAll();
        $entityArr = $this->Entity->read();
        $entityArr['uploads'] = $uploadedFilesArr;
        return $entityArr;
    }

    /**
     * Update an entity
     *
     * @return string[]
     */
    public function updateEntity()
    {
        if (is_null($this->id)) {
            throw new Exception('You need an id to update something!');
        }

        $this->Entity->canOrExplode('write');

        if (!$this->Request->request->has('title') ||
            !$this->Request->request->has('date') ||
            !$this->Request->request->has('body')) {
            throw new Exception('Empty title, date or body sent.');
        }

        if ($this->Entity->update(
            $this->Request->request->get('title'),
            $this->Request->request->get('date'),
            $this->Request->request->get('body')
        )) {
            return array('Result', 'Success');
        }

        return array('error', Tools::error());
    }

    /**
     * Add a file to an entity
     *
     * @return string[]
     */
    public function uploadFile()
    {
        $this->Entity->canOrExplode('write');

        $Uploads = new Uploads($this->Entity);

        // TODO
        if ($Uploads->create($_FILES)) {
            return array('Result', 'Success');
        }

        return array('Result', Tools::error());
    }
}
