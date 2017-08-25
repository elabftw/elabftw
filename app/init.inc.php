<?php
/**
 * init.inc.php
 *
 * @author Nicolas CARPi <nicolas.carpi@curie.fr>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 */
namespace Elabftw\Elabftw;

use Exception;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session;

/**
 * This must be included on top of every page.
 * It loads the config file, connects to the database,
 * includes functions and locale, tries to update the db schema and redirects anonymous visitors.
 */
try {
    require_once dirname(dirname(__FILE__)) . '/vendor/autoload.php';

    // create Request object
    $Request = Request::createFromGlobals();

    $Session = new Session();
    if (!$Request->hasPreviousSession()) {
        $Session->start();
    }

    // add check for php version here also
    if (!function_exists('version_compare') || version_compare(PHP_VERSION, '5.6', '<')) {
        $message = "Your version of PHP isn't recent enough. Please update your php version to at least 5.6";
        throw new Exception($message);
    }

    // load the config file with info to connect to DB
    $configFilePath = dirname(dirname(__FILE__)) . '/config.php';
    // redirect to install page if the config file is not here
    if (!is_readable($configFilePath)) {
        header('Location: install');
        throw new Exception('Redirecting to install folder');
    }

    require_once $configFilePath;

    // the config table from mysql
    $Config = new Config();

    $Logs = new Logs();

    // Methods for login
    $Auth = new Auth();

    $App = new App($Request, $Session, $Config, $Logs);

    // this will throw an exception if the SQL structure is not imported yet
    // so we redirect to the install folder
    try {
        $Update = new Update($Config);
    } catch (Exception $e) {
        header('Location: install');
        throw new Exception('Redirecting to install folder');
    }

    if ($Session->has('auth')) {
        // generate full Users object with current userid
        $Users = new Users($Session->get('userid'), $Auth, $Config);
        // set lang based on user pref
        $locale = $Users->userData['lang'] . '.utf8';
        // TODOLIST
        $Todolist = new Todolist($Session->get('userid'));
        $App->todoItems = $Todolist->readAll();

        $Teams = new Teams($Users->userData['team']);
        $App->teamConfigArr = $Teams->read();

    } else {
        $Users = new Users();
        // load server configured lang if logged out
        $locale = $Update->Config->configArr['lang'] . '.utf8';
    }

    // CONFIGURE GETTEXT
    $domain = 'messages';
    putenv("LC_ALL=$locale");
    $res = setlocale(LC_ALL, $locale);
    bindtextdomain($domain, ELAB_ROOT . "app/locale");
    textdomain($domain);
    // END i18n

    // TWIG
    $loader = new \Twig_Loader_Filesystem(ELAB_ROOT . 'app/tpl');
    $cache = ELAB_ROOT . 'uploads/tmp';
    $options = array();

    // enable cache if not in debug (dev) mode
    if (!$Config->configArr['debug']) {
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

    // this is the variables needed to generate the base template
    $baseRenderArr = array(
        'App' => $App,
        'Users' => $Users
    );

    // END TWIG

    // UPDATE SQL SCHEMA
    if ($Config->configArr['schema'] < $Update::REQUIRED_SCHEMA) {
        try {
            // run the update script if we have the wrong schema version
            foreach ($Update->runUpdateScript() as $msg) {
                $Session->getFlashBag()->add('ok', $msg);
            }
        } catch (Exception $e) {
            $Session->getFlashBag()->add('ko', 'Error updating: ' . $e->getMessage());
        }
    }

    // pages where you don't need to be logged in
    // only the script name, not the path because we use basename() on it
    $nologinArr = array(
        'change-pass.php',
        'index.php',
        'login.php',
        'LoginController.php',
        'metadata.php',
        'register.php',
        'RegisterController.php',
        'ResetPasswordController.php'
    );

    if (!$Session->has('auth') && !in_array(basename($Request->getScriptName()), $nologinArr)) {
        // try to login with the cookie
        if (!$Auth->loginWithCookie($Request)) {
            // maybe we clicked an email link and we want to be redirected to the page upon successful login
            // so we store the url in a cookie expiring in 5 minutes to redirect to it after login
            setcookie('redirect', $Request->getRequestUri(), time() + 300, '/', null, true, true);

            header('location: app/logout.php');
            exit;
        }
    }

} catch (Exception $e) {
    echo $e->getMessage();
}
