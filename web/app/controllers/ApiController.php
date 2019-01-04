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
use Elabftw\Elabftw\Tools;
use Elabftw\Exceptions\DatabaseErrorException;
use Elabftw\Exceptions\FilesystemErrorException;
use Elabftw\Exceptions\IllegalActionException;
use Elabftw\Exceptions\ImproperActionException;
use Elabftw\Models\ApiKeys;
use Elabftw\Models\Database;
use Elabftw\Models\Experiments;
use Elabftw\Models\Uploads;
use Elabftw\Models\Users;
use Exception;
use Monolog\Logger;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * This file is called without any auth, so we don't load init.inc.php but only what we need
 */
require_once \dirname(__DIR__, 3) . '/config.php';
require_once \dirname(__DIR__, 3) . '/vendor/autoload.php';

$Response = new JsonResponse(array('error' => Tools::error()));

try {
    // create Request object
    $Request = Request::createFromGlobals();
    $Log = new Logger('elabftw');

    $ApiController = new ApiController($Request);
    $Response = $ApiController->getResponse();

} catch (ImproperActionException $e) {
    $Response->setData(array(
        'error' => $e->getMessage()
    ));

} catch (IllegalActionException $e) {
    $Log->notice('', array('IllegalAction', $e));
    $Response->setData(array(
        'error' => Tools::error(true)
    ));

} catch (DatabaseErrorException | FilesystemErrorException $e) {
    $Log->error('', array('Error', $e));
    $Response->setData(array(
        'error' => $e->getMessage()
    ));

} catch (Exception $e) {
    $Log->error('', array('Exception' => $e));

} finally {
    $Response->send();
}
