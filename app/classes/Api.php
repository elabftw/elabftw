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
        $this->method = $_SERVER['REQUEST_METHOD'];

        $Users = new Users();
        if (empty($_SERVER['HTTP_AUTHORIZATION'])) {
            throw new Exception('No API key received.');
        }
        $this->user = $Users->readFromApiKey($_SERVER['HTTP_AUTHORIZATION']);
        if (empty($this->user)) {
            throw new Exception('Invalid API key.');
        }
    }

    /**
     * Read an entity
     *
     */
    public function getEntity() {
        if ($this->endpoint === 'experiments') {
            $Entity = new Experiments($this->user['team'], $this->user['userid'], $this->id);
        } elseif ($this->endpoint === 'items') {
            $Entity = new Database($this->user['team'], $this->user['userid'], $this->id);
        } else {
            return json_encode(array('error', 'Bad endpoint.'));
        }

        if (is_null($this->id)) {
            $data = $Entity->readAll();
        } else {
            if (!$Entity->canRead) {
                throw new Exception(Tools::error(true));
            }
            $data = $Entity->entityData;
        }

        return json_encode($data);
    }
}
