<?php
/**
 * @author Nicolas CARPi <nicolas.carpi@curie.fr>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */
declare(strict_types=1);

namespace Elabftw\Elabftw;

use Elabftw\Controllers\ApiController;
use Elabftw\Exceptions\DatabaseErrorException;
use Elabftw\Exceptions\FilesystemErrorException;
use Elabftw\Exceptions\IllegalActionException;
use Elabftw\Exceptions\ImproperActionException;
use Exception;
use Monolog\Handler\ErrorLogHandler;
use Monolog\Logger;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * This file is called without any auth, so we don't load init.inc.php but only what we need
 */
require_once \dirname(__DIR__, 3) . '/config.php';
require_once \dirname(__DIR__, 3) . '/vendor/autoload.php';

try {
    // create Request object
    $Request = Request::createFromGlobals();
    $Log = new Logger('elabftw');
    $Log->pushHandler(new ErrorLogHandler());

    $ApiController = new ApiController($Request);
    $Response = $ApiController->getResponse();
} catch (ImproperActionException $e) {
    $Response = new Response($e->getMessage(), 400);
} catch (IllegalActionException $e) {
    $Log->notice('', array('IllegalAction' => $e));
    $Response = new Response(Tools::error(true), 403);
} catch (Exception | DatabaseErrorException | FilesystemErrorException $e) {
    $Log->error('', array('Exception' => $e));
    $Response = new Response(Tools::error(), 500);
} finally {
    $Response->send();
}
