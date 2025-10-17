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

use Elabftw\Exceptions\AppException;
use Elabftw\Models\Items;
use Elabftw\Models\ItemsTypes;
use Elabftw\Models\Scheduler;
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
    $ItemsTypes = new ItemsTypes($App->Users);
    // only the bookable categories
    $bookableItemsArr = $Items->readBookable();
    $categoriesOfBookableItems = array_column($bookableItemsArr, 'category');
    $allItemsTypes = $ItemsTypes->readAll();
    $bookableItemsTypes = array_filter(
        $allItemsTypes,
        fn($a): bool => in_array($a['id'], $categoriesOfBookableItems, true),
    );
    $template = 'scheduler.html';
    $renderArr = array(
        'bookableItemsTypes' => $bookableItemsTypes,
        'itemsArr' => $bookableItemsArr,
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
