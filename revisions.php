<?php
/**
 * revisions.php
 *
 * @author Nicolas CARPi <nicolas.carpi@curie.fr>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */
namespace Elabftw\Elabftw;

use Exception;

/**
 * Show history of body of experiment or db item
 *
 */
require_once 'app/init.inc.php';
$App->pageTitle = _('Revisions');

try {
    $errflag = false;

    if ($Request->query->get('type') === 'experiments') {
        $Entity = new Experiments($Users);

    } elseif ($Request->query->get('type') === 'items') {

        $Entity = new Database($Users);

    } else {
        throw new Exception('Bad type!');
    }

    $Entity->setId($Request->query->get('item_id'));
    $Entity->canOrExplode('write');

    $Revisions = new Revisions($Entity);
    $revisionsArr = $Revisions->readAll();

    $template = 'revisions.html';
    $renderArr = array(
        'Entity' => $Entity,
        'revisionsArr' => $revisionsArr
    );

} catch (Exception $e) {
    $template = 'error.html';
    $renderArr = array('error' => $e->getMessage());
}

echo $App->render($template, $renderArr);
