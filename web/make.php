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

use Elabftw\Exceptions\DatabaseErrorException;
use Elabftw\Exceptions\FilesystemErrorException;
use Elabftw\Exceptions\IllegalActionException;
use Elabftw\Exceptions\ImproperActionException;
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
// default response is error page with general error message
$Response = new Response();
$Response->prepare($Request);
$template = 'error.html';
$renderArr = array('error' => Tools::error());

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
            throw new IllegalActionException('Bad make what value');
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
    $Response->setContent($App->render($template, $renderArr));

} catch (ImproperActionException $e) {
    // show message to user
    $template = 'error.html';
    $renderArr = array('error' => $e->getMessage());
    $Response->setContent($App->render($template, $renderArr));

} catch (IllegalActionException $e) {
    // log notice and show message
    $App->Log->notice('', array(array('userid' => $App->Session->get('userid')), array('IllegalAction', $e)));
    $template = 'error.html';
    $renderArr = array('error' => Tools::error(true));
    $Response->setContent($App->render($template, $renderArr));

} catch (DatabaseErrorException | FilesystemErrorException $e) {
    // log error and show message
    $App->Log->error('', array(array('userid' => $App->Session->get('userid')), array('Error', $e)));
    $template = 'error.html';
    $renderArr = array('error' => $e->getMessage());
    $Response->setContent($App->render($template, $renderArr));

} catch (Exception $e) {
    // log error and show general error message
    $App->Log->error('', array(array('userid' => $App->Session->get('userid')), array('Exception' => $e)));
    $template = 'error.html';
    $renderArr = array('error' => Tools::error());
    $Response->setContent($App->render($template, $renderArr));

} finally {
    $Response->send();
}
