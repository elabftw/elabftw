<?php declare(strict_types=1);
/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

namespace Elabftw\Controllers;

use function count;
use Elabftw\Elabftw\App;
use Elabftw\Exceptions\IllegalActionException;
use Elabftw\Interfaces\ControllerInterface;
use Elabftw\Interfaces\FileMakerInterface;
use Elabftw\Interfaces\MpdfProviderInterface;
use Elabftw\Models\AbstractEntity;
use Elabftw\Models\Experiments;
use Elabftw\Models\Items;
use Elabftw\Models\Teams;
use Elabftw\Services\MakeCsv;
use Elabftw\Services\MakeJson;
use Elabftw\Services\MakeMultiPdf;
use Elabftw\Services\MakePdf;
use Elabftw\Services\MakeQrPdf;
use Elabftw\Services\MakeReport;
use Elabftw\Services\MakeStreamZip;
use Elabftw\Services\MpdfProvider;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Create zip, csv, pdf or report
 */
class MakeController implements ControllerInterface
{
    /** @var AbstractEntity $Entity */
    private $Entity;

    // an array of id to process
    private array $idArr = array();

    public function __construct(private App $App)
    {
        $this->Entity = new Items($this->App->Users);
        if ($this->App->Request->query->get('type') === 'experiments') {
            $this->Entity = new Experiments($this->App->Users);
        }
        // generate the id array
        if ($this->App->Request->query->has('category')) {
            $this->idArr = $this->Entity->getIdFromCategory((int) $this->App->Request->query->get('category'));
        } elseif ($this->App->Request->query->has('id')) {
            $this->idArr = explode(' ', (string) $this->App->Request->query->get('id'));
        }
    }

    public function getResponse(): Response
    {
        switch ($this->App->Request->query->get('what')) {
            case 'csv':
                return $this->makeCsv();

            case 'json':
                return $this->makeJson();

            case 'pdf':
                return $this->makePdf();

            case 'multiPdf':
                if (count($this->idArr) === 1) {
                    return $this->makePdf();
                }
                return $this->makeMultiPdf();

            case 'qrPdf':
                return $this->makeQrPdf();

            case 'report':
                if (!$this->App->Session->get('is_sysadmin')) {
                    throw new IllegalActionException('Non sysadmin user tried to generate report.');
                }
                return $this->makeReport();

            case 'zip':
                return $this->makeZip();

            default:
                throw new IllegalActionException('Bad make what value');
        }
    }

    private function makeCsv(): Response
    {
        return $this->getFileResponse(new MakeCsv($this->Entity, $this->idArr));
    }

    private function makeJson(): Response
    {
        return $this->getFileResponse(new MakeJson($this->Entity, $this->idArr));
    }

    private function makePdf(): Response
    {
        $this->Entity->setId((int) $this->App->Request->query->get('id'));
        $this->Entity->canOrExplode('read');
        return $this->getFileResponse(new MakePdf($this->getMpdfProvider(), $this->Entity, true));
    }

    private function makeMultiPdf(): Response
    {
        return $this->getFileResponse(new MakeMultiPdf($this->getMpdfProvider(), $this->Entity, $this->idArr));
    }

    private function makeQrPdf(): Response
    {
        return $this->getFileResponse(new MakeQrPdf($this->getMpdfProvider(), $this->Entity, $this->idArr));
    }

    private function makeReport(): Response
    {
        return $this->getFileResponse(new MakeReport(new Teams($this->App->Users)));
    }

    private function makeZip(): Response
    {
        $Make = new MakeStreamZip($this->Entity, $this->idArr);
        $Response = new StreamedResponse();
        $Response->headers->set('X-Accel-Buffering', 'no');
        $Response->headers->set('Content-Type', 'application/zip');
        $Response->headers->set('Cache-Control', 'no-store');
        $contentDisposition = $Response->headers->makeDisposition(ResponseHeaderBag::DISPOSITION_ATTACHMENT, $Make->getFileName(), 'elabftw-export.zip');
        $Response->headers->set('Content-Disposition', $contentDisposition);
        $Response->setCallback(function () use ($Make) {
            $Make->getZip();
        });
        return $Response;
    }

    private function getMpdfProvider(): MpdfProviderInterface
    {
        $userData = $this->App->Users->userData;
        return new MpdfProvider(
            $userData['fullname'],
            $userData['pdf_format'],
            (bool) $userData['pdfa'],
        );
    }

    private function getFileResponse(FileMakerInterface $Maker): Response
    {
        return new Response(
            $Maker->getFileContent(),
            200,
            array(
                'Content-Type' => $Maker->getContentType(),
                'Content-disposition' => 'inline; filename="' . $Maker->getFileName() . '"',
                'Cache-Control' => 'no-store',
                'Last-Modified' => gmdate('D, d M Y H:i:s') . ' GMT',
            )
        );
    }
}
