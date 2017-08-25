<?php
/**
 * \Elabftw\Elabftw\App
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
use Symfony\Component\HttpFoundation\Session\Session;

/**
 * App
 *
 */
class App
{
    public $Request;
    public $Session;
    public $Config;
    public $Logs;

    public $Db;

    public $pageTitle = "Lab manager";
    public $selectedMenu = null;
    public $ok;
    public $ko;
    public $debug;
    public $todoItems = array();
    public $teamConfigArr = array();

    /**
     * Constructor
     *
     * @param Request $request
     */
    public function __construct(Request $request, Session $session, Config $config, Logs $logs)
    {
        $this->Db = Db::getConnection();
        $this->Request = $request;
        $this->Session = $session;
        $this->Config = $config;
        $this->Logs = $logs;

        $this->ok = $this->Session->getFlashBag()->get('ok', array());
        $this->ko = $this->Session->getFlashBag()->get('ko', array());
        $this->debug = (bool) $this->Config->configArr['debug'];
    }

    /**
     * Get the page generation time
     *
     */
    public function getGenerationTime()
    {
        return round((microtime(true) - $this->Request->server->get("REQUEST_TIME_FLOAT")), 5);
    }

    public function getMemoryUsage()
    {
        return memory_get_usage();
    }
}
