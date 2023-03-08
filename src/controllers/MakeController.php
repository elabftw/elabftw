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

use Elabftw\Enums\EntityType;
use Elabftw\Exceptions\IllegalActionException;
use Elabftw\Exceptions\ImproperActionException;
use Elabftw\Interfaces\ControllerInterface;
use Elabftw\Interfaces\MpdfProviderInterface;
use Elabftw\Interfaces\StringMakerInterface;
use Elabftw\Interfaces\ZipMakerInterface;
use Elabftw\Models\AbstractEntity;
use Elabftw\Models\Items;
use Elabftw\Models\Scheduler;
use Elabftw\Models\Teams;
use Elabftw\Models\Users;
use Elabftw\Services\MakeCsv;
use Elabftw\Services\MakeEln;
use Elabftw\Services\MakeJson;
use Elabftw\Services\MakeMultiPdf;
use Elabftw\Services\MakePdf;
use Elabftw\Services\MakeQrPdf;
use Elabftw\Services\MakeQrPng;
use Elabftw\Services\MakeReport;
use Elabftw\Services\MakeSchedulerReport;
use Elabftw\Services\MakeStreamZip;
use Elabftw\Services\MpdfProvider;
use Elabftw\Services\MpdfQrProvider;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\HttpFoundation\StreamedResponse;
use ZipStream\ZipStream;

/**
 * Create zip, csv, pdf or report
 */
class MakeController implements ControllerInterface
{
    private AbstractEntity $Entity;

    // an array of id to process
    private array $idArr = array();

    private bool $pdfa = false;

    public function __construct(private Users $Users, private Request $Request)
    {
    }

    public function getResponse(): Response
    {
        switch ($this->Request->query->get('format')) {
            case 'csv':
                $this->populateIdArr();
                return $this->makeCsv();

            case 'eln':
                $this->populateIdArr();
                return $this->makeEln();

            case 'json':
                $this->populateIdArr();
                return $this->makeJson();

            case 'pdfa':
                $this->pdfa = true;
                // no break
            case 'pdf':
                $this->populateIdArr();
                return $this->makePdf();

            case 'multipdf':
                $this->populateIdArr();
                if (count($this->idArr) === 1) {
                    return $this->makePdf();
                }
                return $this->makeMultiPdf();

            case 'qrpdf':
                $this->populateIdArr();
                return $this->makeQrPdf();

            case 'qrpng':
                $this->populateIdArr();
                return $this->makeQrPng();

            case 'report':
                if (!$this->Users->userData['is_sysadmin']) {
                    throw new IllegalActionException('Non sysadmin user tried to generate report.');
                }
                return $this->makeReport();

            case 'schedulerReport':
                if (!$this->Users->userData['is_admin']) {
                    throw new IllegalActionException('Non admin user tried to generate scheduler report.');
                }
                return $this->makeSchedulerReport();

            case 'zipa':
                $this->pdfa = true;
                // no break
            case 'zip':
                $this->populateIdArr();
                return $this->makeZip();

            default:
                throw new IllegalActionException('Bad make format value');
        }
    }

    private function populateIdArr(): void
    {
        $this->Entity = EntityType::from((string) $this->Request->query->get('type'))->toInstance($this->Users);
        // generate the id array
        if ($this->Request->query->has('category')) {
            $this->idArr = $this->Entity->getIdFromCategory((int) $this->Request->query->get('category'));
        } elseif ($this->Request->query->has('owner')) {
            // only admin can export a user, or it is ourself
            if (!$this->Users->userData['is_admin'] && $this->Request->query->getInt('owner') !== $this->Users->userData['userid']) {
                throw new IllegalActionException('User tried to export another user but is not admin.');
            }
            // being admin is good, but we also need to be in the same team as the requested user
            $Teams = new Teams($this->Users);
            $targetUserid = (int) $this->Request->query->get('owner');
            if (!$Teams->hasCommonTeamWithCurrent($targetUserid, $this->Users->userData['team'])) {
                throw new IllegalActionException('User tried to export another user but is not in same team.');
            }
            $this->idArr = $this->Entity->getIdFromUser($targetUserid);
        } elseif ($this->Request->query->has('id')) {
            $this->idArr = explode(' ', (string) $this->Request->query->get('id'));
        }
    }

    private function makeCsv(): Response
    {
        return $this->getFileResponse(new MakeCsv($this->Entity, $this->idArr));
    }

    private function getZipStreamLib(): ZipStream
    {
        return new ZipStream(sendHttpHeaders:false);
    }

    private function makeEln(): Response
    {
        return $this->makeStreamZip(new MakeEln($this->getZipStreamLib(), $this->Entity, $this->idArr));
    }

    private function makeJson(): Response
    {
        return $this->getFileResponse(new MakeJson($this->Entity, $this->idArr));
    }

    private function makePdf(): Response
    {
        $this->Entity->setId((int) $this->Request->query->get('id'));
        $this->Entity->canOrExplode('read');
        return $this->getFileResponse(new MakePdf($this->getMpdfProvider(), $this->Entity));
    }

    private function makeMultiPdf(): Response
    {
        return $this->getFileResponse(new MakeMultiPdf($this->getMpdfProvider(), $this->Entity, $this->idArr));
    }

    private function makeQrPdf(): Response
    {
        return $this->getFileResponse(new MakeQrPdf($this->getMpdfProvider(), $this->Entity, $this->idArr));
    }

    private function makeQrPng(): Response
    {
        // only works for 1 entry
        if (count($this->idArr) !== 1) {
            throw new ImproperActionException('QR PNG format is only suitable for one ID.');
        }
        return $this->getFileResponse(new MakeQrPng(new MpdfQrProvider(), $this->Entity, (int) $this->idArr[0], $this->Request->query->getInt('size')));
    }

    private function makeReport(): Response
    {
        return $this->getFileResponse(new MakeReport(new Teams($this->Users)));
    }

    private function makeSchedulerReport(): Response
    {
        $defaultStart = '2018-12-23T00:00:00+01:00';
        $defaultEnd = '2119-12-23T00:00:00+01:00';
        return $this->getFileResponse(new MakeSchedulerReport(
            new Scheduler(
                new Items($this->Users),
                null,
                (string) $this->Request->query->get('start', $defaultStart),
                (string) $this->Request->query->get('end', $defaultEnd),
            ),
        ));
    }

    private function makeZip(): Response
    {
        return $this->makeStreamZip(new MakeStreamZip($this->getZipStreamLib(), $this->Entity, $this->idArr, $this->pdfa));
    }

    private function makeStreamZip(ZipMakerInterface $Maker): Response
    {
        $Response = new StreamedResponse();
        $Response->headers->set('X-Accel-Buffering', 'no');
        $Response->headers->set('Content-Type', $Maker->getContentType());
        $Response->headers->set('Cache-Control', 'no-store');
        $contentDisposition = $Response->headers->makeDisposition(ResponseHeaderBag::DISPOSITION_ATTACHMENT, $Maker->getFileName(), 'elabftw-export.zip');
        $Response->headers->set('Content-Disposition', $contentDisposition);
        $Response->setCallback(function () use ($Maker) {
            $Maker->getStreamZip();
        });
        return $Response;
    }

    private function getMpdfProvider(): MpdfProviderInterface
    {
        $userData = $this->Users->userData;
        return new MpdfProvider(
            $userData['fullname'],
            $userData['pdf_format'],
            $this->pdfa,
        );
    }

    private function getFileResponse(StringMakerInterface $Maker): Response
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
