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
 * curl -kL -X GET -H "Authorization: $API_KEY" "https://elabftw.example.org/app/api/v1/items/7"
 */
class Api
{
    /** http method GET POST PUT DELETE */
    public $method;

    /** the model (experiments/items) */
    private $endpoint;

    /** optional arguments, like the id */
    public $args = array();

    /** our user */
    private $user;

    public $id = null;

    private $Entity;

    /**
     * Get data for user from the API key
     *
     */
    public function __construct()
    {
        header("Access-Control-Allow-Origin: *");
        header("Access-Control-Allow-Methods: *");
        header("Content-Type: application/json");

        $this->args = explode('/', rtrim($_GET['req'], '/'));
        if (Tools::checkId(end($this->args))) {
            $this->id = end($this->args);
        }
        $this->endpoint = array_shift($this->args);
        $Users = new Users();
        if (empty($_SERVER['HTTP_AUTHORIZATION'])) {
            throw new Exception('No API key received.');
        }
        $this->user = $Users->readFromApiKey($_SERVER['HTTP_AUTHORIZATION']);
        if (empty($this->user)) {
            throw new Exception('Invalid API key.');
        }
        if ($this->endpoint === 'experiments') {
            $this->Entity = new Experiments($this->user['team'], $this->user['userid'], $this->id);
        } elseif ($this->endpoint === 'items') {
            $this->Entity = new Database($this->user['team'], $this->user['userid'], $this->id);
        } else {
            throw new Exception('Bad endpoint.');
        }
        $this->method = $_SERVER['REQUEST_METHOD'];

    }

    /**
     * Read an entity
     *
     */
    public function getEntity()
    {
        if (is_null($this->id)) {
            $data = $this->Entity->readAll();
        } else {
            if (!$Entity->canRead) {
                throw new Exception(Tools::error(true));
            }
            $data = $this->Entity->entityData;
        }

        return json_encode($data);
    }

    /**
     * Update an entity
     *
     */
    public function updateEntity()
    {
        if (is_null($this->id)) {
            throw new Exception('You need an id to update something!');
        }
        if (empty($_POST['title']) || empty($_POST['date']) || empty($_POST['body'])) {
            throw new Exception('Empty title, date or body sent.');
        }
        $this->Entity->update($_POST['title'], $_POST['date'], $_POST['body']);
    }
}
