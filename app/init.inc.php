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
use Elabftw\Core\App;
use Elabftw\Core\Auth;
use Elabftw\Core\Config;
use Elabftw\Core\Users;
use Elabftw\Core\Logs;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session;

/**
 * This must be included on top of every page.
 * It loads the config file, connects to the database,
 * includes functions and locale, tries to update the db schema and redirects anonymous visitors.
 */
require_once dirname(dirname(__FILE__)) . '/vendor/autoload.php';

try {

    // create Request object
    $Request = Request::createFromGlobals();

    $Session = new Session();

    $Session->start();
    $Request->setSession($Session);

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

    // load config.php
    require_once $configFilePath;

    // Methods for login
    $Auth = new Auth($Request);

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


    // INIT APP OBJECT
    $App = new App($Request, $Config, new Logs(), $Users);

    // CONFIGURE GETTEXT
    $domain = 'messages';
    putenv("LC_ALL=$locale");
    $res = setlocale(LC_ALL, $locale);
    bindtextdomain($domain, ELAB_ROOT . "app/locale");
    textdomain($domain);
    // END i18n

    // UPDATE SQL SCHEMA
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
    if (!$Auth->checkAuth()) {
        // maybe we clicked an email link and we want to be redirected to the page upon successful login
        // so we store the url in a cookie expiring in 5 minutes to redirect to it after login
        setcookie('redirect', $Request->getRequestUri(), time() + 300, '/', null, true, true);

        header('location: app/logout.php');
        exit;
    }

} catch (Exception $e) {
    echo $e->getMessage();
}
