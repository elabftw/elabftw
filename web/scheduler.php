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

use Elabftw\Exceptions\DatabaseErrorException;
use Elabftw\Exceptions\FilesystemErrorException;
use Elabftw\Exceptions\IllegalActionException;
use Elabftw\Exceptions\ImproperActionException;
use Elabftw\Models\Items;
use Elabftw\Models\ItemsTypes;
use Elabftw\Models\Scheduler;
use Exception;
use Symfony\Component\HttpFoundation\Response;

/**
 * Scheduler to book resources
 */
require_once 'app/init.inc.php';

// default response is error page with general error message
$Response = new Response();
$Response->prepare($Request);
$template = 'error.html';

try {
    $Items = new Items($App->Users);
    $ItemsTypes = new ItemsTypes($App->Users);
    $bookableItemData = array();
    $Scheduler = new Scheduler($Items);
    if ($App->Request->query->has('item') && $App->Request->query->get('item') !== 'all' && !empty($App->Request->query->get('item'))) {
        $Scheduler->Items->setId($App->Request->query->getInt('item'));
        $bookableItemData = $Scheduler->Items->readOne();
    }
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
        'bookableItemData' => $bookableItemData,
        'bookableItemsTypes' => $bookableItemsTypes,
        'itemsArr' => $bookableItemsArr,
        'pageTitle' => _('Scheduler'),
    );

    $Response->setContent($App->render($template, $renderArr));

} catch (ImproperActionException $e) {
    // show message to user
    $renderArr = array('error' => $e->getMessage());
    $Response->setContent($App->render($template, $renderArr));
} catch (IllegalActionException $e) {
    // log notice and show message
    $App->Log->notice('', array(array('userid' => $App->Session->get('userid')), array('IllegalAction', $e)));
    $renderArr = array('error' => Tools::error(true));
    $Response->setContent($App->render($template, $renderArr));
} catch (DatabaseErrorException | FilesystemErrorException $e) {
    // log error and show message
    $App->Log->error('', array(array('userid' => $App->Session->get('userid')), array('Error', $e)));
    $renderArr = array('error' => $e->getMessage());
    $Response->setContent($App->render($template, $renderArr));
} catch (Exception $e) {
    // log error and show general error message
    $App->Log->error('', array(array('userid' => $App->Session->get('userid')), array('Exception' => $e)));
    $renderArr = array('error' => Tools::error());
    $Response->setContent($App->render($template, $renderArr));
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
