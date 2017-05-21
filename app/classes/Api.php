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

/**
 * An API for elab
 *
 */
class Api
{
    /** http method GET POST PUT DELETE */
    public $method;

    /** the model (experiments/items) */
    private $endpoint;

    /** optional arguments, like the id */
    public $args = array();

    /** the id of the entity */
    public $id = null;

    /** our entity object */
    private $Entity;

    /**
     * Get data for user from the API key
     *
     * @param string $key API key
     * @param string $method GET/POST
     * @param string $request experiments/12
     */
    public function __construct($key, $method, $request)
    {
        $availMethods = array('GET', 'POST', 'PUT');
        if (!in_array($method, $availMethods)) {
            throw new Exception('Incorrect HTTP verb!');
        }
        $this->method = $method;

        // reply in JSON
        header("Access-Control-Allow-Origin: *");
        header("Access-Control-Allow-Methods: *");
        header("Content-Type: application/json");

        // parse args
        $this->args = explode('/', rtrim($request, '/'));

        // assign the id if there is one
        if (Tools::checkId(end($this->args))) {
            $this->id = end($this->args);
        }

        // assign the endpoint
        $this->endpoint = array_shift($this->args);

        // get info about user
        $Users = new Users();
        $Users->readFromApiKey($key);

        // load Entity
        if ($this->endpoint === 'experiments') {
            $this->Entity = new Experiments($Users, $this->id);
        } elseif ($this->endpoint === 'items') {
            $this->Entity = new Database($Users, $this->id);
        } else {
            throw new Exception('Bad endpoint.');
        }
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

        if (empty($_POST['title']) || empty($_POST['date']) || empty($_POST['body'])) {
            throw new Exception('Empty title, date or body sent.');
        }

        if ($this->Entity->update($_POST['title'], $_POST['date'], $_POST['body'])) {
            return array('Result', 'Success');
        }

        return array('Result', Tools::error());
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

        if ($Uploads->create($_FILES)) {
            return array('Result', 'Success');
        }

        return array('Result', Tools::error());
    }
}
