<?php declare(strict_types=1);
/**
 * @author Nicolas CARPi <nicolas.carpi@curie.fr>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 */

namespace Elabftw\Elabftw;

use Elabftw\Exceptions\ImproperActionException;
use Elabftw\Exceptions\InvalidSchemaException;
use Elabftw\Models\Config;
use Elabftw\Models\Experiments;
use Elabftw\Models\Users;
use Exception;
use Monolog\Logger;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session;

/**
 * This must be included on top of every page.
 * It loads the config file, connects to the database,
 * includes functions and locale, tries to update the db schema and redirects anonymous visitors.
 */
require_once \dirname(__DIR__, 2) . '/vendor/autoload.php';

$Request = Request::createFromGlobals();
$Session = new Session();
$Session->start();
$Request->setSession($Session);

try {
    // CONFIG.PHP
    $configFilePath = \dirname(__DIR__, 2) . '/config.php';
    // redirect to install page if the config file is not here
    if (!is_readable($configFilePath)) {
        $url = Tools::getUrlFromRequest($Request) . '/install/index.php';
        // not pretty but gets the job done
        $url = str_replace('app/', '', $url);
        header('Location: ' . $url);
        throw new ImproperActionException('Redirecting to install folder');
    }
    require_once $configFilePath;
    // END CONFIG.PHP

    $App = new App($Request, $Session, new Config(), new Logger('elabftw'), new Csrf($Request, $Session));

    //-*-*-*-*-*-*-**-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-//
    //     ____          _                            //
    //    / ___|___ _ __| |__   ___ _ __ _   _ ___    //
    //   | |   / _ \ '__| '_ \ / _ \ '__| | | / __|   //
    //   | |___  __/ |  | |_) |  __/ |  | |_| \__ \   //
    //    \____\___|_|  |_.__/ \___|_|   \__,_|___/   //
    //                                                //
    //-*-*-*-*-*-*-**-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-//
    $Auth = new Auth($App->Request, $App->Session);

    // autologin as anon if it's allowed by sysadmin
    // don't do it if we have elabid in url
    if ($App->Config->configArr['open_science'] && !$App->Request->query->has('elabid')) {
        // only autologin on selected pages and if we are not authenticated with an account
        $autoAnon = array('experiments.php', 'database.php', 'search.php');
        if (\in_array(\basename($App->Request->getScriptName()), $autoAnon, true) && !$App->Request->getSession()->has('auth')) {
            $Auth->loginAsAnon((int) $App->Config->configArr['open_team'] ?? 1);
        }
    }

    // autologin if there is elabid for an experiment in view mode
    if ($App->Request->query->has('elabid')
        && \basename($App->Request->getScriptName()) === 'experiments.php'
        && $App->Request->query->get('mode') === 'view'
        && !$App->Request->getSession()->has('auth')) {

        // now we need to know in which team we autologin the user
        $Experiments = new Experiments(new Users(), (int) $App->Request->query->get('id'));
        $team = $Experiments->getTeamFromElabid($App->Request->query->get('elabid'));
        $Auth->loginAsAnon($team);
    }

    if ($Auth->needAuth() && !$Auth->tryAuth()) {
        // KICK USER TO LOGOUT PAGE THAT WILL REDIRECT TO LOGIN PAGE

        // maybe we clicked an email link and we want to be redirected to the page upon successful login
        // so we store the url in a cookie expiring in 5 minutes to redirect to it after login
        // don't store a redirect cookie if we have been logged out and the redirect is to a controller page
        if (!stripos($App->Request->getRequestUri(), 'controllers')) {
            \setcookie('redirect', $App->Request->getRequestUri(), time() + 300, '/', '', true, true);
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
        $App->loadUser(new Users((int) $App->Request->getSession()->get('userid')));
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
    if ($App->Request->getSession()->has('auth')) {
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
    bindtextdomain($domain, \dirname(__DIR__, 2) . '/src/langs');
    textdomain($domain);
    // END i18n
} catch (ImproperActionException | InvalidSchemaException | Exception $e) {
    // if something went wrong here it should stop whatever is after
    die($e->getMessage());
}
