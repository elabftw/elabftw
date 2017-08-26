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

    /** @var Users $Users instance of Users */
    public $Users;

    /** @var Db $Db SQL Database */
    public $Db;

    /** @var \Twig_Environment $Twig instance of Twig */
    public $Twig;

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
     * @param Config $config
     * @param Logs $logs
     * @param Users $users
     */
    public function __construct(
        Request $request,
        Config $config,
        Logs $logs,
        Users $users
    ) {
        $this->Request = $request;
        $this->Config = $config;
        $this->Logs = $logs;
        $this->Users = $users;

        $this->Db = Db::getConnection();
        $this->Session = $this->Request->getSession();
        $this->Twig = $this->getTwig();

        $this->ok = $this->Session->getFlashBag()->get('ok', array());
        $this->ko = $this->Session->getFlashBag()->get('ko', array());

        if ($this->Session->has('auth')) {
            // todolist
            $Todolist = new Todolist($this->Session->get('userid'));
            $this->todoItems = $Todolist->readAll();

            // team config
            $Teams = new Teams($this->Users);
            $this->teamConfigArr = $Teams->read();
        }
    }

    /**
     * Prepare the Twig object
     *
     * @return \Twig_Environment
     */
    private function getTwig()
    {
        $loader = new \Twig_Loader_Filesystem(ELAB_ROOT . 'app/tpl');
        $cache = ELAB_ROOT . 'uploads/tmp';
        $options = array();

        // enable cache if not in debug (dev) mode
        if (!$this->Config->configArr['debug']) {
            $options = array('cache' => $cache);
        }
        $Twig = new \Twig_Environment($loader, $options);

        // custom twig filters
        $filterOptions = array('is_safe' => array('html'));
        $msgFilter = new \Twig_SimpleFilter('msg', '\Elabftw\Elabftw\Tools::displayMessage', $filterOptions);
        $dateFilter = new \Twig_SimpleFilter('kdate', '\Elabftw\Elabftw\Tools::formatDate', $filterOptions);
        $mdFilter = new \Twig_SimpleFilter('md2html', '\Elabftw\Elabftw\Tools::md2html', $filterOptions);
        $starsFilter = new \Twig_SimpleFilter('stars', '\Elabftw\Elabftw\Tools::showStars', $filterOptions);
        $bytesFilter = new \Twig_SimpleFilter('formatBytes', '\Elabftw\Elabftw\Tools::formatBytes', $filterOptions);

        $Twig->addFilter($msgFilter);
        $Twig->addFilter($dateFilter);
        $Twig->addFilter($mdFilter);
        $Twig->addFilter($starsFilter);
        $Twig->addFilter($bytesFilter);

        // i18n for twig
        $Twig->addExtension(new \Twig_Extensions_Extension_I18n());

        return $Twig;
    }

    /**
     * Generate HTML from a twig template
     *
     * @param string $template template located in app/tpl/
     * @param array $variables the variables injected in the template
     * @return string html
     */
    public function render($template, $variables)
    {
        return $this->Twig->render($template, array_merge(array('App' => $this), $variables));
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
