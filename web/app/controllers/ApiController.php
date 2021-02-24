<?php
/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */
declare(strict_types=1);

namespace Elabftw\Elabftw;

use function dirname;
use Elabftw\Controllers\ApiController;
use Elabftw\Exceptions\DatabaseErrorException;
use Elabftw\Exceptions\FilesystemErrorException;
use Elabftw\Exceptions\IllegalActionException;
use Elabftw\Exceptions\ImproperActionException;
use Elabftw\Exceptions\UnauthorizedException;
use Elabftw\Models\Config;
use Exception;
use Monolog\Logger;
use PDOException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Session;

/**
 * This file is called without any auth, so we don't load init.inc.php but only what we need
 */
require_once dirname(__DIR__, 3) . '/config.php';
require_once dirname(__DIR__, 3) . '/vendor/autoload.php';

// create Request object
$Request = Request::createFromGlobals();
$Session = new Session();
$Session->start();
$Request->setSession($Session);
$Response = new Response(Tools::error(), 500);

try {
    // INIT APP OBJECT
    // new Config will make the first SQL request
    // PDO will throw an exception if the SQL structure is not imported yet
    try {
        $App = new App($Request, $Session, new Config(), new Logger('elabftw'), new Csrf($Request, $Session));
    } catch (PDOException $e) {
        throw new ImproperActionException('The database structure is not loaded! Did you run the installer?');
    }
    $ApiController = new ApiController($App);
    $Response = $ApiController->getResponse();
} catch (UnauthorizedException $e) {
    // send error 401 if it's lacking an Authorization header, with WWW-Authenticate header as per spec:
    // https://tools.ietf.org/html/rfc7235#section-3.1
    $Response = new Response($e->getMessage(), 401, array('WWW-Authenticate' => 'Bearer'));
} catch (ImproperActionException $e) {
    $Response = new Response($e->getMessage(), 400);
} catch (IllegalActionException $e) {
    $App->Log->notice('', array('IllegalAction' => $e));
    $Response = new Response(Tools::error(true), 403);
} catch (Exception | DatabaseErrorException | FilesystemErrorException $e) {
    $App->Log->error('', array('Exception' => $e));
    $Response = new Response(Tools::error(), 500);
} finally {
    $Response->send();
}
