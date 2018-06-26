<?php
/**
 * \Elabftw\Elabftw\App
 *
 * @package   Elabftw\Elabftw
 * @author    Nicolas CARPi <nicolas.carpi@curie.fr>
 * @copyright 2012 Nicolas CARPi
 * @license   https://www.gnu.org/licenses/agpl-3.0.html AGPL-3.0
 * @see       https://www.elabftw.net Official website
 */
declare(strict_types=1);

namespace Elabftw\Elabftw;

use RuntimeException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session;

/**
 * This is a super class holding various global objects
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
    public $pageTitle = 'Lab manager';

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
     */
    public function __construct(
        Request $request,
        Config $config,
        Logs $logs
    ) {
        $this->Request = $request;
        $this->Config = $config;
        $this->Logs = $logs;
        $this->Users = new Users(null, new Auth($request), new Config());

        $this->Db = Db::getConnection();
        $this->Session = $this->Request->getSession();
        $this->Twig = $this->getTwig();

        $this->ok = $this->Session->getFlashBag()->get('ok', array());
        $this->ko = $this->Session->getFlashBag()->get('ko', array());
    }

    /**
     * Prepare the Twig object
     *
     * @return \Twig_Environment
     */
    private function getTwig(): \Twig_Environment
    {
        $elabRoot = \dirname(__DIR__, 2);
        $loader = new \Twig_Loader_Filesystem($elabRoot . '/src/templates');
        $cache = $elabRoot . '/cache/twig';
        if (!is_dir($cache) && !mkdir($cache) && !is_dir($cache)) {
            throw new RuntimeException('Unable to create the cache directory (' . $cache . ')');
        }
        $options = array();

        // enable cache if not in debug (dev) mode
        if (!$this->Config->configArr['debug']) {
            $options = array('cache' => $cache);
        }
        $TwigEnvironment = new \Twig_Environment($loader, $options);

        // custom twig filters
        $filterOptions = array('is_safe' => array('html'));
        $msgFilter = new \Twig_SimpleFilter('msg', '\Elabftw\Elabftw\Tools::displayMessage', $filterOptions);
        $dateFilter = new \Twig_SimpleFilter('kdate', '\Elabftw\Elabftw\Tools::formatDate', $filterOptions);
        $mdFilter = new \Twig_SimpleFilter('md2html', '\Elabftw\Elabftw\Tools::md2html', $filterOptions);
        $starsFilter = new \Twig_SimpleFilter('stars', '\Elabftw\Elabftw\Tools::showStars', $filterOptions);
        $bytesFilter = new \Twig_SimpleFilter('formatBytes', '\Elabftw\Elabftw\Tools::formatBytes', $filterOptions);

        $TwigEnvironment->addFilter($msgFilter);
        $TwigEnvironment->addFilter($dateFilter);
        $TwigEnvironment->addFilter($mdFilter);
        $TwigEnvironment->addFilter($starsFilter);
        $TwigEnvironment->addFilter($bytesFilter);

        // i18n for twig
        $TwigEnvironment->addExtension(new \Twig_Extensions_Extension_I18n());

        // add the version as a global var so we can have it for the ?v=x.x.x for js files
        $ReleaseCheck = new ReleaseCheck($this->Config);
        $TwigEnvironment->addGlobal('v', $ReleaseCheck::INSTALLED_VERSION);

        return $TwigEnvironment;
    }

    /**
     * Get the page generation time (called in the footer)
     *
     * @return float
     */
    public function getGenerationTime(): float
    {
        return round(microtime(true) - $this->Request->server->get('REQUEST_TIME_FLOAT'), 5);
    }

    /**
     * Get the current memory usage (called in the footer)
     *
     * @return int
     */
    public function getMemoryUsage(): int
    {
        return memory_get_usage();
    }

    /**
     * If the current user is authenticated, load Users with an id
     *
     * @param Users $users
     * @return void
     */
    public function loadUser(Users $users): void
    {
        $this->Users = $users;

        // todolist
        $Todolist = new Todolist($this->Users);
        $this->todoItems = $Todolist->readAll();

        // team config
        $Teams = new Teams($this->Users);
        $this->teamConfigArr = $Teams->read();
    }

    /**
     * Generate HTML from a twig template. The App object is injected into every template.
     *
     * @param string $template template located in app/tpl/
     * @param array $variables the variables injected in the template
     * @return string html
     */
    public function render(string $template, array $variables): string
    {
        return $this->Twig->render($template, array_merge(array('App' => $this), $variables));
    }
}
