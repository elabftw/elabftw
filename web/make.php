<?php
/**
 * make.php
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
 * Create a csv, zip or pdf file
 *
 */

require_once 'app/init.inc.php';
$App->pageTitle = _('Export');

try {
    if ($Request->query->get('type') === 'experiments') {
        $Entity = new Experiments($App->Users);
    } else {
        $Entity = new Database($App->Users);
    }

    switch ($Request->query->get('what')) {
        case 'csv':
            $Make = new MakeCsv($Entity, $Request->query->get('id'));
            break;

        case 'zip':
            $Make = new MakeZip($Entity, $Request->query->get('id'));
            break;

        case 'pdf':
            $Entity->setId($Request->query->get('id'));
            $Entity->canOrExplode('read');
            $Make = new MakePdf($Entity);
            $Make->output();
            break;

        default:
            throw new Exception(Tools::error());
    }

    // the pdf is shown directly, but for csv or zip we want a download page
    if ($Request->query->get('what') === 'csv' || $Request->query->get('what') === 'zip') {

        $filesize = Tools::formatBytes(filesize($Make->filePath));

        $template = 'make.html';
        $renderArr = array(
            'what' => $Request->query->get('what'),
            'Make' => $Make,
            'filesize' => $filesize
        );
        $Response = new Response();
        $Response->prepare($Request);
        $Response->setContent($App->render($template, $renderArr));
        $Response->send();
    }

} catch (Exception $e) {
    $template = 'error.html';
    $renderArr = array('error' => $e->getMessage());
    $Response = new Response();
    $Response->prepare($Request);
    $Response->setContent($App->render($template, $renderArr));
    $Response->send();
}
