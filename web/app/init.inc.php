<?php declare(strict_types=1);
/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 */

namespace Elabftw\Elabftw;

use function basename;
use function dirname;
use Elabftw\Exceptions\DatabaseErrorException;
use Elabftw\Exceptions\ImproperActionException;
use Elabftw\Exceptions\InvalidSchemaException;
use Elabftw\Exceptions\UnauthorizedException;
use Elabftw\Models\Config;
use Elabftw\Models\Users;
use Elabftw\Services\LoginHelper;
use Exception;
use function in_array;
use Monolog\Logger;
use PDOException;
use function setcookie;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session;

/**
 * This must be included on top of every page.
 * It loads the config file, connects to the database,
 * includes functions and locale, tries to update the db schema and redirects anonymous visitors.
 */
require_once dirname(__DIR__, 2) . '/vendor/autoload.php';

$Request = Request::createFromGlobals();
$Session = new Session();
$Session->start();
$Request->setSession($Session);

try {
    // CONFIG.PHP
    // Make sure config.php is readable
    $configFilePath = dirname(__DIR__, 2) . '/config.php';
    if (!is_readable($configFilePath)) {
        throw new ImproperActionException('The config file is missing! Did you run the installer?');
    }
    require_once $configFilePath;
    // END CONFIG.PHP

    // INIT APP OBJECT
    // new Config will make the first SQL request
    // PDO will throw an exception if the SQL structure is not imported yet
    try {
        $App = new App($Request, $Session, new Config(), new Logger('elabftw'), new Csrf($Request, $Session));
    } catch (DatabaseErrorException | PDOException $e) {
        throw new ImproperActionException('The database structure is not loaded! Did you run the installer?');
    }
    //-*-*-*-*-*-*-**-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-//
    //     ____          _                            //
    //    / ___|___ _ __| |__   ___ _ __ _   _ ___    //
    //   | |   / _ \ '__| '_ \ / _ \ '__| | | / __|   //
    //   | |___  __/ |  | |_) |  __/ |  | |_| \__ \   //
    //    \____\___|_|  |_.__/ \___|_|   \__,_|___/   //
    //                                                //
    //-*-*-*-*-*-*-**-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-//
    $Auth = new Auth($App);
    if ($Auth->needAuth()) {
        try {
            // this will throw an UnauthorizedException if we don't have a valid auth
            $AuthResponse = $Auth->tryAuth();
            $LoginHelper = new LoginHelper($AuthResponse, $App->Session);
            $LoginHelper->login(false);
        } catch (UnauthorizedException $e) {
            // KICK USER TO LOGOUT PAGE THAT WILL REDIRECT TO LOGIN PAGE

            // maybe we clicked an email link and we want to be redirected to the page upon successful login
            // so we store the url in a cookie expiring in 5 minutes to redirect to it after login
            // don't store a redirect cookie if we have been logged out and the redirect is to a controller page
            if (!stripos($App->Request->getRequestUri(), 'controllers')) {
                $cookieOptions = array(
                    'expires' => time() + 300,
                    'path' => '/',
                    'domain' => '',
                    'secure' => true,
                    'httponly' => true,
                    'samesite' => 'Strict',
                );
                setcookie('redirect', $App->Request->getRequestUri(), $cookieOptions);
            }

            // used by ajax requests to detect a timed out session
            header('X-Elab-Need-Auth: 1');
            // don't send a GET app/logout.php if it's an ajax call because it messes up the jquery ajax
            if ($App->Request->headers->get('X-Requested-With') != 'XMLHttpRequest') {
                // NO DON'T USE  THE FULL URL HERE BECAUSE IF SERVER IS HTTP it will fail badly
                header('Location: app/logout.php?keep_redirect=1');
                exit;
            }
            throw new UnauthorizedException(_('Your session expired.'));
        }
    }

    // load the Users with a userid if we are auth and not anon
    if ($App->Session->has('is_auth') && $App->Session->get('userid') !== 0) {
        $App->loadUser(new Users(
            $App->Session->get('userid'),
            $App->Session->get('team'),
        ));
    }

    // ANONYMOUS
    if ($App->Session->get('is_anon') === 1) {
        // anon user only has access to a subset of pages
        $allowedPages = array('index.php', 'experiments.php', 'database.php', 'search.php', 'make.php');
        if (!in_array(basename($App->Request->getScriptName()), $allowedPages, true)) {
            throw new ImproperActionException('Anonymous user cannot access this page');
        }
        $Users = new Users();
        $Users->userData['team'] = $App->Session->get('team');
        $App->loadUser($Users);
        // create a fake Users object with default data for anon user
        $App->Users->userData['team'] = $App->Session->get('team');
        $App->Users->userData['limit_nb'] = 15;
        $App->Users->userData['anon'] = true;
        $App->Users->userData['fullname'] = 'Anon Ymous';
        $App->Users->userData['is_admin'] = 0;
        $App->Users->userData['is_sysadmin'] = 0;
        $App->Users->userData['show_team'] = 1;
        $App->Users->userData['show_team_templates'] = 0;
    }

    // START i18n
    // get the lang
    if ($App->Session->has('is_auth') && $App->Session->get('userid') !== 0) {
        // set lang based on user pref
        $locale = $App->Users->userData['lang'] . '.utf8';
    } else {
        // load server configured lang if logged out
        $locale = $App->Config->configArr['lang'] . '.utf8';
    }
    // configure gettext
    $domain = 'messages';
    putenv("LC_ALL=$locale");
    setlocale(LC_ALL, $locale);
    bindtextdomain($domain, dirname(__DIR__, 2) . '/src/langs');
    textdomain($domain);
    // END i18n
} catch (UnauthorizedException $e) {
    // do nothing here, controller will display the error
} catch (ImproperActionException | InvalidSchemaException | Exception $e) {
    // if something went wrong here it should stop whatever is after
    die($e->getMessage());
}
