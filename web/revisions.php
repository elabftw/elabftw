<?php

/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2012, 2022 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

declare(strict_types=1);

namespace Elabftw\Elabftw;

use Elabftw\Enums\EntityType;
use Elabftw\Exceptions\AppException;
use Elabftw\Models\Revisions;
use Exception;
use Symfony\Component\HttpFoundation\Response;

/**
 * Show history of body of experiment or db item
 */
require_once 'app/init.inc.php';

$Response = new Response();

try {
    $Response->prepare($App->Request);
    $Entity = EntityType::from($App->Request->query->getString('type'))->toInstance($App->Users);
    $Entity->setId($App->Request->query->getInt('item_id'));
    $Entity->canOrExplode('read');

    $revisionsArr = new Revisions(
        $Entity,
        (int) $App->Config->configArr['max_revisions'],
        (int) $App->Config->configArr['min_delta_revisions'],
        (int) $App->Config->configArr['min_days_revisions'],
    )->readAll();

    $template = 'revisions.html';
    $renderArr = array(
        'Entity' => $Entity,
        'pageTitle' => _('Revisions'),
        'revisionsArr' => $revisionsArr,
    );

    $Response->setContent($App->render($template, $renderArr));
} catch (AppException $e) {
    $Response = $e->getResponseFromException($App);
} catch (Exception $e) {
    $Response = $App->getResponseFromException($e);
} finally {
    $Response->send();
}
