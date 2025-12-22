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

use Elabftw\Enums\EntityType;
use Elabftw\Enums\State;
use Elabftw\Exceptions\AppException;
use Elabftw\Models\Items;
use Elabftw\Models\ItemsTypes;
use Elabftw\Models\ResourcesCategories;
use Elabftw\Models\Templates;
use Elabftw\Params\DisplayParams;
use Exception;
use Symfony\Component\HttpFoundation\Response;

/**
 * Scheduler to book resources
 */
require_once 'app/init.inc.php';

$Response = new Response();

try {
    $Response->prepare($Request);
    $Items = new Items($App->Users);
    $ResourcesCategories = new ResourcesCategories($App->Teams);
    $Templates = new Templates($App->Users);
    $ItemsTypes = new ItemsTypes($App->Users);
    // only the bookable categories
    $bookableItemsArr = $Items->readBookable();
    $categoriesOfBookableItems = array_column($bookableItemsArr, 'category');
    $allCategories = $ResourcesCategories->readAll();
    $bookableCategories = array_filter(
        $allCategories,
        fn($a): bool => in_array($a['id'], $categoriesOfBookableItems, true),
    );
    $templatesDisplayParams = new DisplayParams($App->Users, EntityType::Templates, limit: 9999, states: array(State::Normal));
    $itemsDisplayParams = new DisplayParams($App->Users, EntityType::ItemsTypes, limit: 9999, states: array(State::Normal));
    $templatesArr = $Templates->readAllSimple($templatesDisplayParams);
    $itemsTemplatesArr = $ItemsTypes->readAllSimple($itemsDisplayParams);
    $template = 'scheduler.html';
    $renderArr = array(
        'bookableCategories' => $bookableCategories,
        'itemsArr' => $bookableItemsArr,
        'metadataGroups' => (new Metadata(null))->getGroups(),
        'itemsTemplatesArr' => $itemsTemplatesArr,
        'templatesArr' => $templatesArr,
        'pageTitle' => _('Scheduler'),
    );

    $Response->setContent($App->render($template, $renderArr));
} catch (AppException $e) {
    $Response = $e->getResponseFromException($App);
} catch (Exception $e) {
    $Response = $App->getResponseFromException($e);
} finally {
    $Response->send();
}
