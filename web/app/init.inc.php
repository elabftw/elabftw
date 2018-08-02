<?php
/**
 * app/init.inc.php
 *
 * @author Nicolas CARPi <nicolas.carpi@curie.fr>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 */
namespace Elabftw\Elabftw;

use Exception;
use Monolog\Logger;
use PDOException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session;

/**
 * This must be included on top of every page.
 * It loads the config file, connects to the database,
 * includes functions and locale, tries to update the db schema and redirects anonymous visitors.
 */
require_once \dirname(__DIR__, 2) . '/vendor/autoload.php';

try {
    // CREATE REQUEST OBJECT
    $Request = Request::createFromGlobals();

    // CREATE SESSION
    $Session = new Session();
    $Session->start();
    // and attach it to Request
    $Request->setSession($Session);

    // LOAD CONFIG.PHP
    $configFilePath = \dirname(__DIR__, 2) . '/config.php';
    // redirect to install page if the config file is not here
    if (!is_readable($configFilePath)) {
        $url = Tools::getUrlFromRequest($Request) . '/install/index.php';
        // not pretty but gets the job done
        $url = str_replace('app/', '', $url);
        header('Location: ' . $url);
        throw new Exception('Redirecting to install folder');
    }
    require_once $configFilePath;
    // END LOAD CONFIG.PHP

    // INIT APP OBJECT
    // new Config will make the first SQL request
    // PDO will throw an exception if the SQL structure is not imported yet
    // so we redirect to the install folder
    try {
        $App = new App($Session, $Request, new Config(), new Logger('elabftw'));
    } catch (PDOException $e) {
        $url = Tools::getUrlFromRequest($Request) . '/install/index.php';
        header('Location: ' . $url);
        throw new Exception('Redirecting to install folder');
    }

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
        $App->Session->getFlashBag()->add('ko', 'Error updating: ' . $e->getMessage() . " (" . $e->getLine() . ")");
    }

    //-*-*-*-*-*-*-**-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-//
    //     ____          _                            //
    //    / ___|___ _ __| |__   ___ _ __ _   _ ___    //
    //   | |   / _ \ '__| '_ \ / _ \ '__| | | / __|   //
    //   | |___  __/ |  | |_) |  __/ |  | |_| \__ \   //
    //    \____\___|_|  |_.__/ \___|_|   \__,_|___/   //
    //                                                //
    //-*-*-*-*-*-*-**-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-//

    // autologin as anon if it's allowed by sysadmin
    if ($App->Config->configArr['open_science']) {
        // only autologin on selected pages and if we are not authenticated with an account
        $autoAnon = array('experiments.php', 'database.php', 'search.php');
        if (\in_array(\basename($Request->getScriptName()), $autoAnon, true) && !$App->Request->getSession()->has('auth')) {
            $App->Users->Auth->loginAsAnon($App->Config->configArr['open_team'] ?? 1);
        }
    }

    if ($App->Users->Auth->needAuth() && !$App->Users->Auth->tryAuth()) {
        // KICK USER TO LOGOUT PAGE THAT WILL REDIRECT TO LOGIN PAGE

        // maybe we clicked an email link and we want to be redirected to the page upon successful login
        // so we store the url in a cookie expiring in 5 minutes to redirect to it after login
        // don't store a redirect cookie if we have been logged out and the redirect is to a controller page
        if (!stripos($Request->getRequestUri(), 'controllers')) {
            setcookie('redirect', $Request->getRequestUri(), time() + 300, '/', null, true, true);
        }

        // used by ajax requests to detect a timed out session
        header('X-Elab-Need-Auth: 1');
        // don't send a GET app/logout.php if it's an ajax call because it messes up the jquery ajax
        if ($App->Request->headers->get('X-Requested-With') != 'XMLHttpRequest') {
            // NO DON'T USE  THE FULL URL HERE BECAUSE IF SERVER IS HTTP it will fail badly
            header('Location: app/logout.php');
        }
        exit;
    }

    // load the Users with a userid if we are auth
    if ($App->Request->getSession()->has('auth')) {
        $App->loadUser(new Users($Request->getSession()->get('userid'), $App->Users->Auth, $App->Config));
    }

    // ANONYMOUS
    if ($App->Request->getSession()->has('anon')) {
        $Users = new Users();
        $Users->userData['team'] = $App->Request->getSession()->get('team');
        $App->loadUser($Users);
        $App->Users->userData['team'] = $App->Request->getSession()->get('team');
        $App->Users->userData['limit_nb'] = 15;
        $App->Users->userData['anon'] = true;
        $App->Users->userData['fullname'] = 'Anon Ymous';
        $App->Users->userData['is_admin'] = 0;
        $App->Users->userData['is_sysadmin'] = 0;
    }

    // GET THE LANG
    if ($Request->getSession()->has('auth')) {
        // generate full Users object with current userid
        // set lang based on user pref
        $locale = $App->Users->userData['lang'] . '.utf8';
    } else {
        // load server configured lang if logged out
        $locale = $App->Config->configArr['lang'] . '.utf8';
    }

    // CONFIGURE GETTEXT
    $domain = 'messages';
    putenv("LC_ALL=$locale");
    setlocale(LC_ALL, $locale);
    bindtextdomain($domain, \dirname(__DIR__, 2) . "/src/langs");
    textdomain($domain);
    // END i18n

} catch (Exception $e) {
    // if something went wrong here it should stop whatever is after
    die($e->getMessage());
}
