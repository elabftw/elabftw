<?php
/**
 * @author Nicolas CARPi <nicolas.carpi@curie.fr>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */
declare(strict_types=1);

namespace Elabftw\Controllers;

use Elabftw\Elabftw\App;
use Elabftw\Exceptions\IllegalActionException;
use Elabftw\Interfaces\ControllerInterface;
use Elabftw\Models\AbstractEntity;
use Elabftw\Models\Database;
use Elabftw\Models\Experiments;
use Elabftw\Models\Uploads;
use Elabftw\Models\Teams;
use Elabftw\Services\MakeCsv;
use Elabftw\Services\MakePdf;
use Elabftw\Services\MakeReport;
use Elabftw\Services\MakeStreamZip;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Create zip, csv, pdf or report
 */
class MakeController implements ControllerInterface
{
    /** @var App $App */
    private $App;

    /** @var AbstractEntity $Entity */
    private $Entity;

    /**
     * Constructor
     *
     * @param App $app
     */
    public function __construct(App $app)
    {
        $this->App = $app;
        $this->Entity = new Database($this->App->Users);
        if ($this->App->Request->query->get('type') === 'experiments') {
            $this->Entity = new Experiments($this->App->Users);
        }
    }

    /**
     * Get the response
     *
     * @return Response
     */
    public function getResponse(): Response
    {
        switch ($this->App->Request->query->get('what')) {
            case 'csv':
                return $this->makeCsv();

            case 'pdf':
                return $this->makePdf();

            case 'report':
                return $this->makeReport();

            case 'zip':
                return $this->makeZip();

            default:
                throw new IllegalActionException('Bad make what value');
        }
    }

    /**
     * Create a CSV export
     *
     * @return Response
     */
    private function makeCsv(): Response
    {
        $Make = new MakeCsv($this->Entity, $this->App->Request->query->get('id'));
        return new Response(
            $Make->getCsv(),
            200,
            array(
                'Content-Encoding' => 'none',
                'Content-Type' => 'text/csv; charset=UTF-8',
                'Content-Disposition' => 'attachment; filename="' . $Make->getFileName() . '"',
                'Content-Description' => 'File Transfer',
                'Cache-Control' => 'no-store',
            )
        );
    }

    /**
     * Create a PDF export
     *
     * @return Response
     */
    private function makePdf(): Response
    {
        $this->Entity->setId((int) $this->App->Request->query->get('id'));
        $this->Entity->canOrExplode('read');
        $Make = new MakePdf($this->Entity);
        return new Response(
            $Make->getPdf(),
            200,
            array(
                'Content-Type' => 'application/pdf',
                'Content-disposition' => 'inline; filename="' . $Make->getFileName() . '"',
                'Cache-Control' => 'no-store',
                'Last-Modified: ' . gmdate('D, d M Y H:i:s') . ' GMT',
            )
        );
    }

    /**
     * Create a CSV report (only for sysadmin)
     *
     * @return Response
     */
    private function makeReport(): Response
    {
        if (!$this->App->Session->get('is_sysadmin')) {
            throw new IllegalActionException('Non sysadmin user tried to generate report.');
        }
        $Make = new MakeReport(new Teams($this->App->Users), new Uploads());
        return new Response(
            $Make->getCsv(),
            200,
            array(
                'Content-Encoding' => 'none',
                'Content-Type' => 'text/csv; charset=UTF-8',
                'Content-Disposition' => 'attachment; filename="' . $Make->getFileName() . '"',
                'Content-Description' => 'File Transfer',
                'Cache-Control' => 'no-store',
            )
        );
    }

    /**
     * Create a ZIP export
     *
     * @return Response
     */
    private function makeZip(): Response
    {
        $Make = new MakeStreamZip($this->Entity, $this->App->Request->query->get('id'));
        $Response = new StreamedResponse();
        $Response->headers->set('X-Accel-Buffering', 'no');
        $Response->headers->set('Content-Type', 'application/zip');
        $Response->headers->set('Cache-Control', 'no-store');
        $contentDisposition = $Response->headers->makeDisposition(ResponseHeaderBag::DISPOSITION_ATTACHMENT, $Make->getFileName());
        $Response->headers->set('Content-Disposition', $contentDisposition);
        $Response->setCallback(function () use ($Make) {
            $Make->getZip();
        });
        return $Response;
    }
}
