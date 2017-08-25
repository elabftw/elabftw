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
 * This is a super class holding various global objects
 *
 */
class App
{
    /** @var Request $Request the request */
    public $Request;

    /** @var Session $Session the session */
    public $Session;

    /** @var Config $Config the config stored in sql */
    public $Config;

    /** @var Logs $Logs instance of Logs */
    public $Logs;

    /** @var Db $Db SQL Database */
    public $Db;

    /** @var string $pageTitle the title for the current page */
    public $pageTitle = "Lab manager";

    /** @var array $ok the ok messages from flashBag */
    public $ok;

    /** @var array $ko the ko messages from flashBag */
    public $ko;

    /** @var array $todoItems items on the todolist, populated if logged in */
    public $todoItems = array();

    /** @var array $teamConfigArr the config for the current team */
    public $teamConfigArr = array();

    /**
     * Constructor
     *
     * @param Request $request
     * @param Session $session
     * @param Config $config
     * @param Logs $logs
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
    }

    /**
     * Get the page generation time (called in the footer)
     *
     * @return float
     */
    public function getGenerationTime()
    {
        return round((microtime(true) - $this->Request->server->get("REQUEST_TIME_FLOAT")), 5);
    }

    /**
     * Get the current memory usage (called in the footer)
     *
     * @return int
     */
    public function getMemoryUsage()
    {
        return memory_get_usage();
    }
}
