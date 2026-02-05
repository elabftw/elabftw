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

use Elabftw\Controllers\DatabaseController;
use Elabftw\Exceptions\AppException;
use Elabftw\Exceptions\UnauthorizedException;
use Elabftw\Models\ItemsTypes;
use Elabftw\Services\Filter;
use Exception;
use Symfony\Component\HttpFoundation\Response;

/**
 * Resources templates main page
 */
require_once 'app/init.inc.php';

$Response = new Response();
try {
    if ($App->Teams->teamArr['users_canwrite_resources_templates'] === 0 && !$App->Users->isAdmin) {
        throw new UnauthorizedException(_('Sorry, edition of resources templates has been disabled for users by your team Admin.'));
    }
    $Response->prepare($Request);
    $Response = new DatabaseController($App, new ItemsTypes($App->Users, Filter::intOrNull($Request->query->getInt('id'))))->getResponse();
} catch (AppException $e) {
    $Response = $e->getResponseFromException($App);
} catch (Exception $e) {
    $Response = $App->getResponseFromException($e);
} finally {
    $Response->send();
}
