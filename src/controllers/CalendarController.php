<?php

/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @author Marcel Bolten <github@marcelbolten.de>
 * @copyright 2024 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

declare(strict_types=1);

namespace Elabftw\Controllers;

use Elabftw\Interfaces\ControllerInterface;
use Elabftw\Interfaces\StringMakerInterface;
use Elabftw\Make\MakeICal;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;

/**
 * Create ical/ics
 */
class CalendarController implements ControllerInterface
{
    public function __construct(protected Request $Request) {}

    public function getResponse(): Response
    {
        return $this->getFileResponse(new MakeICal($this->Request->query->getString('token')));
    }

    private function getFileResponse(StringMakerInterface $Maker): Response
    {
        return new Response(
            $Maker->getFileContent(),
            Response::HTTP_OK,
            array(
                'Content-Type' => $Maker->getContentType(),
                'Content-Size' => $Maker->getContentSize(),
                'Content-disposition' => 'inline; filename="' . $Maker->getFileName() . '"',
                'Cache-Control' => 'no-store',
                'Last-Modified' => gmdate('D, d M Y H:i:s') . ' GMT',
            )
        );
    }
}
