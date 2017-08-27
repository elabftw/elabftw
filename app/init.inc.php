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
use PDOException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session;

/**
 * This must be included on top of every page.
 * It loads the config file, connects to the database,
 * includes functions and locale, tries to update the db schema and redirects anonymous visitors.
 */
require_once dirname(dirname(__FILE__)) . '/vendor/autoload.php';

try {
    // CHECK PHP VERSION
    if (!function_exists('version_compare') || version_compare(PHP_VERSION, '5.6', '<')) {
        $message = "Your version of PHP isn't recent enough. Please update your php version to at least 5.6";
        throw new Exception($message);
    }

    // CREATE REQUEST OBJECT
    $Request = Request::createFromGlobals();

    // CREATE SESSION
    $Session = new Session();
    $Session->start();
    // and attach it to Request
    $Request->setSession($Session);

    // LOAD CONFIG.PHP
    $configFilePath = dirname(dirname(__FILE__)) . '/config.php';
    // redirect to install page if the config file is not here
    if (!is_readable($configFilePath)) {
        $url = 'https://' . $Request->getHttpHost() . '/install/index.php';
        header('Location: ' . $url);
        throw new Exception('Redirecting to install folder');
    }
    require_once $configFilePath;
    // END LOAD CONFIG.PHP

    // Methods for login
    $Auth = new Auth($Request);

    // the config table from mysql
    // It's the first SQL request
    // PDO will throw an exception if the SQL structure is not imported yet
    // so we redirect to the install folder
    try {
        $Config = new Config();
    } catch (PDOException $e) {
        $url = 'https://' . $Request->getHttpHost() . '/install/index.php';
        header('Location: ' . $url);
        throw new Exception('Redirecting to install folder');
    }

    // GET THE LANG
    if ($Request->getSession()->has('auth')) {
        // generate full Users object with current userid
        $Users = new Users($Request->getSession()->get('userid'), $Auth, $Config);
        // set lang based on user pref
        $locale = $Users->userData['lang'] . '.utf8';
    } else {
        $Users = new Users();
        // load server configured lang if logged out
        $locale = $Config->configArr['lang'] . '.utf8';
    }

    // CONFIGURE GETTEXT
    $domain = 'messages';
    putenv("LC_ALL=$locale");
    $res = setlocale(LC_ALL, $locale);
    bindtextdomain($domain, ELAB_ROOT . "app/locale");
    textdomain($domain);
    // END i18n


    // INIT APP OBJECT
    $App = new App($Request, $Config, new Logs(), $Users);

    // UPDATE SQL SCHEMA
    $Update = new Update($App->Config);
    try {
        $messages = $Update->runUpdateScript();
        if (is_array($messages)) {
            foreach ($messages as $msg) {
                $App->Session->getFlashBag()->add('ok', $msg);
            }
        }
    } catch (Exception $e) {
        $App->Session->getFlashBag()->add('ko', 'Error updating: ' . $e->getMessage());
    }

    // CERBERUS
    if (!$Auth->isAuth()) {
        // maybe we clicked an email link and we want to be redirected to the page upon successful login
        // so we store the url in a cookie expiring in 5 minutes to redirect to it after login
        setcookie('redirect', $Request->getRequestUri(), time() + 300, '/', null, true, true);

        // also don't redirect blindly to https because we might be in http (after install)
        $url = $Request->getScheme() . '://' . $Request->getHttpHost() . '/app/logout.php';
        header('Location: ' . $url);
        exit;
    }

} catch (Exception $e) {
    // if something went wrong here it should stop whatever is after
    die($e->getMessage());
}
