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

/**
 * Entrypoint for API requests. Nginx redirects all the /api/vN requests here.
 */
require_once dirname(__DIR__) . '/init.inc.php';

if (str_contains($App->Request->server->get('QUERY_STRING'), 'api/v2')) {
    $Controller = new Apiv2Controller($App->Request);
} else {
    $Controller = new ApiController($App->Request);
}
$Response = $Controller->getResponse();
$Response->send();
