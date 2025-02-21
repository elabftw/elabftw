<?php

/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

declare(strict_types=1);

namespace Elabftw\Controllers;

use Elabftw\AuditEvent\Export;
use Elabftw\Enums\Classification;
use Elabftw\Enums\EntityType;
use Elabftw\Enums\ExportFormat;
use Elabftw\Enums\ReportScopes;
use Elabftw\Exceptions\IllegalActionException;
use Elabftw\Exceptions\ImproperActionException;
use Elabftw\Interfaces\MpdfProviderInterface;
use Elabftw\Interfaces\ZipMakerInterface;
use Elabftw\Make\MakeCsv;
use Elabftw\Make\MakeEln;
use Elabftw\Make\MakeJson;
use Elabftw\Make\MakeMultiPdf;
use Elabftw\Make\MakePdf;
use Elabftw\Make\MakeProcurementRequestsCsv;
use Elabftw\Make\MakeQrPdf;
use Elabftw\Make\MakeQrPng;
use Elabftw\Make\MakeSchedulerReport;
use Elabftw\Make\MakeStreamZip;
use Elabftw\Make\ReportsHandler;
use Elabftw\Models\AuditLogs;
use Elabftw\Models\Items;
use Elabftw\Models\ProcurementRequests;
use Elabftw\Models\Scheduler;
use Elabftw\Models\Teams;
use Elabftw\Services\MpdfProvider;
use Elabftw\Services\MpdfQrProvider;
use Monolog\Handler\ErrorLogHandler;
use Monolog\Logger;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\HttpFoundation\StreamedResponse;
use ValueError;
use ZipStream\ZipStream;
use Override;

use function array_map;
use function count;

/**
 * Create zip, csv, pdf or report
 */
final class MakeController extends AbstractController
{
    private const int AUDIT_THRESHOLD = 12;

    private bool $pdfa = false;

    // @var array<AbstractEntity>
    private array $entityArr = array();

    #[Override]
    public function getResponse(): Response
    {
        $this->populateSlugs();
        $format = ExportFormat::Json;
        try {
            $format = ExportFormat::from($this->Request->query->getAlpha('format'));
        } catch (ValueError) {
        }
        switch ($format) {
            case ExportFormat::Csv:
                if (str_starts_with($this->Request->getPathInfo(), '/api/v2/teams/current/procurement_requests')) {
                    $ProcurementRequests = new ProcurementRequests(new Teams($this->requester), 1);
                    return (new MakeProcurementRequestsCsv($ProcurementRequests))->getResponse();
                }
                if (str_starts_with($this->Request->getPathInfo(), '/api/v2/reports')) {
                    return (new ReportsHandler($this->requester))->getResponse(
                        ReportScopes::tryFrom($this->Request->query->getString('scope')) ??
                        throw new ImproperActionException(sprintf('Invalid scope query parameter. Possible values are: %s.', ReportScopes::toCsList()))
                    );
                }
                return (new MakeCsv($this->entityArr))->getResponse();

            case ExportFormat::Eln:
                return $this->makeStreamZip(new MakeEln($this->getZipStreamLib(), $this->requester, $this->entityArr));

            case ExportFormat::Json:
                return (new MakeJson($this->entityArr))->getResponse();

            case ExportFormat::PdfA:
                $this->pdfa = true;
                // no break
            case ExportFormat::Pdf:
                return $this->makePdf();

            case ExportFormat::QrPdf:
                return (new MakeQrPdf($this->getMpdfProvider(), $this->requester, $this->entityArr))->getResponse();

            case ExportFormat::QrPng:
                $withTitle = true;
                // this is needed or omitting the query param will result in false, but we want the default to be with the title
                if ($this->Request->query->has('withTitle')) {
                    $withTitle = $this->Request->query->getBoolean('withTitle');
                }
                // only works for 1 entry
                if (count($this->entityArr) !== 1) {
                    throw new ImproperActionException('QR PNG format is only suitable for one ID.');
                }
                return (new MakeQrPng(
                    new MpdfQrProvider(),
                    $this->entityArr[0],
                    $this->Request->query->getInt('size'),
                    $withTitle,
                    $this->Request->query->getInt('titleLines'),
                    $this->Request->query->getInt('titleChars'),
                ))->getResponse();

            case ExportFormat::SchedulerReport:
                return $this->makeSchedulerReport();

            case ExportFormat::ZipA:
                $this->pdfa = true;
                // no break
            case ExportFormat::Zip:
                return $this->makeZip();

            default:
                throw new IllegalActionException('Bad make format value');
        }
    }

    private function shouldIncludeChangelog(): bool
    {
        $includeChangelog =  $this->pdfa;
        if ($this->Request->query->has('changelog')) {
            $includeChangelog = $this->Request->query->getBoolean('changelog');
        }
        return $includeChangelog;
    }

    private function populateSlugs(): void
    {
        try {
            $entityType = EntityType::from($this->Request->query->getString('type'));
        } catch (ValueError) {
            return;
        }
        $idArr = array();
        // generate the id array
        if ($this->Request->query->has('category')) {
            $entity = $entityType->toInstance($this->requester);
            $idArr = $entity->getIdFromCategory($this->Request->query->getInt('category'));
        } elseif ($this->Request->query->has('owner')) {
            // only admin can export a user, or it is ourself
            if (!$this->requester->isAdminOf($this->Request->query->getInt('owner'))) {
                throw new IllegalActionException('User tried to export another user but is not admin.');
            }
            // being admin is good, but we also need to be in the same team as the requested user
            $Teams = new Teams($this->requester);
            $targetUserid = $this->Request->query->getInt('owner');
            if (!$Teams->hasCommonTeamWithCurrent($targetUserid, $this->requester->userData['team'])) {
                throw new IllegalActionException('User tried to export another user but is not in same team.');
            }
            $entity = $entityType->toInstance($this->requester);
            $idArr = $entity->getIdFromUser($targetUserid);
        } elseif ($this->Request->query->has('id')) {
            $idArr = array_map(
                fn(string $id): int => (int) $id,
                explode(' ', $this->Request->query->getString('id')),
            );
        }
        foreach ($idArr as $id) {
            $this->entityArr[] = $entityType->toInstance($this->requester, $id);
        }

        // generate audit log event if exporting more than $threshold entries
        $count = count($this->entityArr);
        if ($count > self::AUDIT_THRESHOLD) {
            AuditLogs::create(new Export($this->requester->userid ?? 0, $count));
        }
    }

    private function getZipStreamLib(): ZipStream
    {
        return new ZipStream(sendHttpHeaders: false);
    }

    private function makePdf(): Response
    {
        $log = (new Logger('elabftw'))->pushHandler(new ErrorLogHandler());
        $classification = Classification::tryFrom($this->Request->query->getInt('classification', Classification::None->value)) ?? Classification::None;
        if (count($this->entityArr) === 1) {
            return (new MakePdf($log, $this->getMpdfProvider(), $this->requester, $this->entityArr, $this->shouldIncludeChangelog(), $classification))->getResponse();
        }
        return (new MakeMultiPdf($log, $this->getMpdfProvider(), $this->requester, $this->entityArr, $this->shouldIncludeChangelog()))->getResponse();
    }

    private function makeSchedulerReport(): Response
    {
        $defaultStart = '2018-12-23T00:00:00+01:00';
        $defaultEnd = '2119-12-23T00:00:00+01:00';
        return (new MakeSchedulerReport(
            new Scheduler(
                new Items($this->requester),
                null,
                $this->Request->query->getString('start', $defaultStart),
                $this->Request->query->getString('end', $defaultEnd),
            ),
        ))->getResponse();
    }

    private function makeZip(): Response
    {
        $classification = Classification::tryFrom($this->Request->query->getInt('classification', Classification::None->value)) ?? Classification::None;
        return $this->makeStreamZip(new MakeStreamZip(
            $this->getZipStreamLib(),
            $this->requester,
            $this->entityArr,
            $this->pdfa,
            $this->shouldIncludeChangelog(),
            $this->Request->query->getBoolean('json'),
            $classification,
        ));
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
        $userData = $this->requester->userData;
        return new MpdfProvider(
            $userData['fullname'],
            $userData['pdf_format'],
            $this->pdfa,
        );
    }
}
