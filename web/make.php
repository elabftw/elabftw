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

use Elabftw\Exceptions\DatabaseErrorException;
use Elabftw\Exceptions\FilesystemErrorException;
use Elabftw\Exceptions\IllegalActionException;
use Elabftw\Exceptions\ImproperActionException;
use Elabftw\Models\Database;
use Elabftw\Models\Experiments;
use Elabftw\Models\Uploads;
use Elabftw\Models\Teams;
use Elabftw\Services\MakeCsv;
use Elabftw\Services\MakePdf;
use Elabftw\Services\MakeReport;
use Elabftw\Services\MakeStreamZip;
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
            $Response = new Response(
                $Make->getCsv(),
                200,
                array(
                    'Content-Encoding' => 'none',
                    'Content-Type' => 'text/csv; charset=UTF-8',
                    'Content-Disposition' => 'attachment; filename="' . $Make->getFileName() . '"',
                    'Content-Description' => 'File Transfer',
                )
            );
            $Response->send();
            break;

        case 'zip':
            $Make = new MakeStreamZip($Entity, $Request->query->get('id'));
            $Response = new StreamedResponse();
            $Response->headers->set('X-Accel-Buffering', 'no');
            $Response->headers->set('Content-Type', 'application/zip');
            $Response->headers->set('Cache-Control', '');
            $contentDisposition = $Response->headers->makeDisposition(ResponseHeaderBag::DISPOSITION_ATTACHMENT, 'elabftw-export.zip');
            $Response->headers->set('Content-Disposition', $contentDisposition);
            $Response->setCallback(function () use ($Make) {
                $Make->getZip();
            });
            $Response->send();
            break;

        case 'pdf':
            $Entity->setId((int) $Request->query->get('id'));
            $Entity->canOrExplode('read');
            $Make = new MakePdf($Entity);
            $Make->outputToBrowser();
            break;

        case 'report':
            if (!$App->Session->get('is_sysadmin')) {
                throw new IllegalActionException('Non sysadmin user tried to generate report.');
            }
            $Make = new MakeReport(new Teams($App->Users), new Uploads());
            $Response = new Response(
                $Make->getCsv(),
                200,
                array(
                    'Content-Encoding' => 'none',
                    'Content-Type' => 'text/csv; charset=UTF-8',
                    'Content-Disposition' => 'attachment; filename="' . $Make->getFileName() . '"',
                    'Content-Description' => 'File Transfer',
                )
            );
            $Response->send();
            break;

        default:
            throw new IllegalActionException('Bad make what value');
    }

} catch (ImproperActionException $e) {
    // show message to user
    $template = 'error.html';
    $renderArr = array('error' => $e->getMessage());
    $Response->setContent($App->render($template, $renderArr));
    $Response->send();

} catch (IllegalActionException $e) {
    // log notice and show message
    $App->Log->notice('', array(array('userid' => $App->Session->get('userid')), array('IllegalAction', $e)));
    $template = 'error.html';
    $renderArr = array('error' => Tools::error(true));
    $Response->setContent($App->render($template, $renderArr));
    $Response->send();

} catch (DatabaseErrorException | FilesystemErrorException $e) {
    // log error and show message
    $App->Log->error('', array(array('userid' => $App->Session->get('userid')), array('Error', $e)));
    $template = 'error.html';
    $renderArr = array('error' => $e->getMessage());
    $Response->setContent($App->render($template, $renderArr));
    $Response->send();

} catch (Exception $e) {
    // log error and show general error message
    $App->Log->error('', array(array('userid' => $App->Session->get('userid')), array('Exception' => $e)));
    $template = 'error.html';
    $renderArr = array('error' => Tools::error());
    $Response->setContent($App->render($template, $renderArr));
    $Response->send();
}
