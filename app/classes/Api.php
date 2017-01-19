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
 *
 */
class Api
{
    /** http method GET POST PUT DELETE */
    public $method;

    /** the model (experiments/items) */
    private $endpoint;

    /** optional arguments */
    public $args = array();

    private $user;

    public function __construct()
    {
        header("Access-Control-Allow-Origin: *");
        header("Access-Control-Allow-Methods: *");
        header("Content-Type: application/json");

        $this->args = explode('/', rtrim($_GET['req'], '/'));
        $this->endpoint = array_shift($this->args);
        $this->method = $_SERVER['REQUEST_METHOD'];
        $Users = new Users();
        $this->user = $Users->readFromApiKey($_SERVER['HTTP_AUTHORIZATION']);
    }

    public function getMethod()
    {
        return json_encode(array('method', $this->method));
    }

    public function getEndpoint()
    {
        return json_encode(array('endpoint', $this->endpoint));
    }

    public function getArgs()
    {
        return json_encode(array('args', $this->args));
    }

    public function getEntity($id) {
        if ($this->endpoint === 'experiments') {
            $Entity = new Experiments($this->user['team'], $this->user['userid'], $id);
        } elseif ($this->endpoint === 'items') {
            $Entity = new Database($this->user['team'], $this->user['userid'], $id);
        } else {
            return json_encode(array('error', 'Bad endpoint.'));
        }

        if (!$Entity->canRead) {
            throw new Exception(Tools::error(true));
        }

        return json_encode($Entity->entityData);
    }
}
