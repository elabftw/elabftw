<?php

/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2025 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

declare(strict_types=1);

namespace Elabftw\Elabftw;

use Elabftw\Controllers\ExperimentsStatusController;
use Elabftw\Exceptions\AppException;
use Elabftw\Exceptions\ImproperActionException;
use Exception;
use Symfony\Component\HttpFoundation\Response;

/**
 * Experiments categories
 */
require_once 'app/init.inc.php';

$Response = new Response();

try {
    if ($App->Teams->teamArr['users_canwrite_experiments_status'] === 0 && !$App->Users->isAdmin) {
        throw new ImproperActionException(_('Sorry, edition of experiments status has been disabled for users by your team Admin.'));
    }
    $Response = new ExperimentsStatusController($App)->getResponse();
} catch (AppException $e) {
    $Response = $e->getResponseFromException($App);
} catch (Exception $e) {
    $Response = $App->getResponseFromException($e);
} finally {
    $Response->send();
}
