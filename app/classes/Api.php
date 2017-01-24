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
 * Get your api key from your profile page.
 * Send it in an Authorization header like so:
 * curl -kL -X GET -H "Authorization: $API_KEY" "https://elabftw.example.org/api/v1/items/7"
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
     * @param string $method
     * @param string $request
     */
    public function __construct($method, $request)
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

        // do we have an API key?
        if (empty($_SERVER['HTTP_AUTHORIZATION'])) {
            throw new Exception('No API key received.');
        }
        // get info about user
        $Users = new Users();
        $Users->readFromApiKey($_SERVER['HTTP_AUTHORIZATION']);

        // load Entity
        if ($this->endpoint === 'experiments') {
            $this->Entity = new Experiments($Users->userData['team'], $Users->userData['userid'], $this->id);
        } elseif ($this->endpoint === 'items') {
            $this->Entity = new Database($Users->userData['team'], $Users->userData['userid'], $this->id);
        } else {
            throw new Exception('Bad endpoint.');
        }
    }

    /**
     * Read an entity
     *
     * @return array
     */
    public function getEntity()
    {
        $this->Entity->canOrExplode('read');

        return $this->Entity->entityData;
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
