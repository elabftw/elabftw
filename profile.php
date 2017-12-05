<?php
/**
 * profile.php
 *
 * @author Nicolas CARPi <nicolas.carpi@curie.fr>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */
namespace Elabftw\Elabftw;

use Exception;
use Symfony\Component\HttpFoundation\Response;

/**
 * Display profile of current user
 *
 */
require_once 'app/init.inc.php';
$App->pageTitle = _('Profile');

try {
    // get total number of experiments
    $Entity = new Experiments($App->Users);
    $Entity->setUseridFilter();
    $itemsArr = $Entity->read();
    $count = count($itemsArr);

    // generate stats for the pie chart with experiments status
    // see https://developers.google.com/chart/interactive/docs/reference?csw=1#datatable-class
    $UserStats = new UserStats($App->Users, $count);
    $stats = array();
    // columns
    $stats['cols'] = array(
        array(
        'type' => 'string',
        'label' => 'Status'),
        array(
        'type' => 'number',
        'label' => 'Experiments number')
    );
    // rows
    foreach ($UserStats->percentArr as $status => $name) {
        $stats['rows'][] = array('c' => array(array('v' => $status), array('v' => $name)));
    }
    // now convert to json for JS usage
    $statsJson = json_encode($stats);

    // colors of the status
    $colors = array();
    // we just need to add the '#' at the beginning
    foreach ($UserStats->colorsArr as $color) {
        $colors[] = '#' . $color;
    }
    $colorsJson = json_encode($colors);

    $TagCloud = new TagCloud($App->Users->userid);

    $template = 'profile.html';
    $renderArr = array(
        'UserStats' => $UserStats,
        'TagCloud' => $TagCloud,
        'colorsJson' => $colorsJson,
        'statsJson' => $statsJson,
        'count' => $count
    );

} catch (Exception $e) {
    $App->Logs->create('Error', $Session->get('userid'), $e->getMessage());
    $template = 'error.html';
    $renderArr = array('error' => $e->getMessage());

} finally {
    $Response = new Response();
    $Response->prepare($Request);
    $Response->setContent($App->render($template, $renderArr));
    $Response->send();
}
