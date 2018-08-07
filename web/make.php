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
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\HttpFoundation\StreamedResponse;

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
            ini_set('max_execution_time', 300);
            // use experimental stream zip feature
            if ($Request->cookies->has('stream_zip')) {
                $Make = new MakeStreamZip($Entity, $Request->query->get('id'));
                $Response = new StreamedResponse();
                $Response->headers->set('X-Accel-Buffering', 'no');
                $Response->headers->set('Content-Type', 'application/zip');
                $Response->headers->set('Cache-Control', '');
                $contentDisposition = $Response->headers->makeDisposition(ResponseHeaderBag::DISPOSITION_ATTACHMENT, 'elabftw-export.zip');
                $Response->headers->set('Content-Disposition', $contentDisposition);
                $Response->setCallback(function() use($Make) {
                    $Make->output();
                });
                $Response->send();
            } else {
                $Make = new MakeZip($Entity, $Request->query->get('id'));
            }
            break;

        case 'pdf':
            $Entity->setId((int) $Request->query->get('id'));
            $Entity->canOrExplode('read');
            $Make = new MakePdf($Entity);
            $Make->outputToBrowser();
            break;

        default:
            throw new Exception(Tools::error());
    }

    // the pdf is shown directly, but for csv or zip we want a download page
    if (\in_array($Request->query->get('what'), array('csv', 'zip'), true) && !$Request->cookies->has('stream_zip')) {

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
