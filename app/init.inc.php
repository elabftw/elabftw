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

    // this will throw an exception if the SQL structure is not imported yet
    // so we redirect to the install folder
    try {
        $Update = new Update($Config);
    } catch (Exception $e) {
        header('Location: install');
        throw new Exception('Redirecting to install folder');
    }

    // i18n (gettext)
    if ($Session->has('auth')) {
        $Users = new Users($Session->get('userid'), $Config);
        $locale = $Users->userData['lang'] . '.utf8';
    } else {
        $locale = $Update->Config->configArr['lang'] . '.utf8';
    }
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
    if (!$Update->Config->configArr['debug']) {
        $options = array('cache' => $cache);
    }
    $Twig = new \Twig_Environment($loader, $options);

    // custom twig filters |msg and |kdate
    $filterOptions = array('is_safe' => array('html'));
    $msgFilter = new \Twig_SimpleFilter('msg', '\Elabftw\Elabftw\Tools::displayMessage', $filterOptions);
    $dateFilter = new \Twig_SimpleFilter('kdate', '\Elabftw\Elabftw\Tools::formatDate', $filterOptions);
    $mdFilter = new \Twig_SimpleFilter('md2html', '\Elabftw\Elabftw\Tools::md2html', $filterOptions);
    $starsFilter = new \Twig_SimpleFilter('stars', '\Elabftw\Elabftw\Tools::showStars', $filterOptions);

    $Twig->addFilter($msgFilter);
    $Twig->addFilter($dateFilter);
    $Twig->addFilter($mdFilter);
    $Twig->addFilter($starsFilter);

    // i18n for twig
    $Twig->addExtension(new \Twig_Extensions_Extension_I18n());

    // run the update script if we have the wrong schema version
    if ($Update->Config->configArr['schema'] < $Update::REQUIRED_SCHEMA) {
        try {
            foreach ($Update->runUpdateScript() as $msg) {
                $Session->getFlashBag()->add('ok', $msg);
            }
        } catch (Exception $e) {
            $Session->getFlashBag()->add('ko', 'Error updating: ' . $e->getMessage());
        }
    }

    // pages where you don't need to be logged in
    // reset.php is in fact app/reset.php but we use basename so...
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
        $Auth = new Auth();
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
