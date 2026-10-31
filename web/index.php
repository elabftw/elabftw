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

use Elabftw\Enums\EntityType;
use Elabftw\Enums\Entrypoint;
use Elabftw\Enums\Scope;
use Exception;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;

use function sprintf;

require_once 'app/init.inc.php';

$Response = new Response();

try {
    $location = '/' . (Entrypoint::tryFrom($App->Users->userData['entrypoint'] ?? 0) ?? Entrypoint::Dashboard)->toPage();
    // anonymous users cannot access the dashboard, send them to experiments with the widest scope
    if ($App->isAnonymous()) {
        $location = sprintf('/%s?scope=%d', EntityType::Experiments->toPage(), Scope::Everything->value);
    }
    $Response = new RedirectResponse($location);
} catch (Exception $e) {
    $Response = $App->getResponseFromException($e);
} finally {
    $Response->send();
}
