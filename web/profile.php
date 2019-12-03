<?php
/**
 * @author Nicolas CARPi <nicolas.carpi@curie.fr>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */
declare(strict_types=1);

namespace Elabftw\Elabftw;

use Elabftw\Models\Experiments;
use Exception;
use Symfony\Component\HttpFoundation\Response;

/**
 * Display profile of current user
 *
 */
require_once 'app/init.inc.php';
$App->pageTitle = _('Profile');

$Response = new Response();
$Response->prepare($Request);

try {
    // get total number of experiments
    $Entity = new Experiments($App->Users);
    $Entity->addFilter('experiments.userid', $App->Users->userData['userid']);
    $itemsArr = $Entity->read(false);
    $count = \count($itemsArr);

    // generate stats for the pie chart with experiments status
    // see https://developers.google.com/chart/interactive/docs/reference?csw=1#datatable-class
    $UserStats = new UserStats($App->Users, $count);
    $UserStats->makeStats();
    $stats = array();
    // columns
    $stats['cols'] = array(
        array(
            'type' => 'string',
            'label' => 'Status',
        ),
        array(
            'type' => 'number',
            'label' => 'Experiments number',
        ),
    );
    // rows
    foreach ($UserStats->percentArr as $name => $percent) {
        $stats['rows'][] = array('c' => array(array('v' => $name), array('v' => $percent)));
    }
    // now convert to json for JS usage
    $statsJson = json_encode($stats);
    $colorsJson = json_encode($UserStats->colorsArr);

    $template = 'profile.html';
    $renderArr = array(
        'UserStats' => $UserStats,
        'colorsJson' => $colorsJson,
        'statsJson' => $statsJson,
        'count' => $count,
    );
} catch (Exception $e) {
    $template = 'error.html';
    $renderArr = array('error' => $e->getMessage());
}

$Response->setContent($App->render($template, $renderArr));
$Response->send();
