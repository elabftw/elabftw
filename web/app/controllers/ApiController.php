<?php declare(strict_types=1);
/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2012, 2022 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

namespace Elabftw\Elabftw;

use function dirname;
use Elabftw\Controllers\ApiController;
use Elabftw\Controllers\Apiv2Controller;
use Elabftw\Models\ApiKeys;
use Elabftw\Models\Users;

/**
 * Entrypoint for API requests. Nginx redirects all the /api/vN requests here.
 */
require_once dirname(__DIR__) . '/init.inc.php';

// verify the key and load user info
$ApiKeys = new ApiKeys(new Users());
$keyArr = $ApiKeys->readFromApiKey($App->Request->server->get('HTTP_AUTHORIZATION') ?? '');
$Users = new Users($keyArr['userid'], $keyArr['team']);
$canWrite = (bool) $keyArr['canWrite'];

if (str_contains($App->Request->server->get('QUERY_STRING'), 'api/v2')) {
    $Controller = new Apiv2Controller($Users, $App->Request, $canWrite);
} else {
    $Controller = new ApiController($Users, $App->Request, $canWrite);
}
$Response = $Controller->getResponse();
$Response->send();
