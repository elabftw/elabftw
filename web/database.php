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

use Elabftw\Controllers\DatabaseController;
use Elabftw\Enums\EntityType;
use Elabftw\Exceptions\AppException;
use Elabftw\Models\Items;
use Elabftw\Services\AccessKeyHelper;
use Elabftw\Services\Filter;
use Exception;
use Symfony\Component\HttpFoundation\Response;

/**
 * Entry point for database things
 */
require_once 'app/init.inc.php';

$Response = new Response();

try {
    $id = Filter::intOrNull($Request->query->getInt('id'));
    $bypassReadPermission = false;
    // if we have an access_key we get the id from that
    if ($App->Request->query->has('access_key')) {
        // for that we fetch the id not from the id param but from the access_key, so we will get a valid id that corresponds to an entity
        // with this access_key
        $id = new AccessKeyHelper(EntityType::Items)->getIdFromAccessKey($App->Request->query->getString('access_key'));
        if ($id > 0) {
            $bypassReadPermission = true;
        }
    }
    $Response = new DatabaseController($App, new Items($App->Users, $id, bypassReadPermission: $bypassReadPermission))->getResponse();
} catch (AppException $e) {
    $Response = $e->getResponseFromException($App);
} catch (Exception $e) {
    $Response = $App->getResponseFromException($e);
} finally {
    // autologout if there is elabid in view mode
    // so we don't stay logged in as anon
    if ($App->Request->query->has('elabid')
        && $App->Request->query->get('mode') === 'view'
        && !$App->Request->getSession()->has('is_auth')) {
        $App->Session->invalidate();
    }
    $Response->send();
}
