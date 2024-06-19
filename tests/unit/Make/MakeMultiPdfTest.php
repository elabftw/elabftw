<?php

declare(strict_types=1);
/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

namespace Elabftw\Make;

use Elabftw\Elabftw\EntitySlug;
use Elabftw\Enums\EntityType;
use Elabftw\Models\Users;
use Elabftw\Services\MpdfProvider;
use Monolog\Handler\NullHandler;
use Monolog\Logger;

class MakeMultiPdfTest extends \PHPUnit\Framework\TestCase
{
    private MakeMultiPdf $MakePdf;

    protected function setUp(): void
    {
        $MpdfProvider = new MpdfProvider('Toto');
        $log = (new Logger('elabftw'))->pushHandler(new NullHandler());
        $requester = new Users(1, 1);
        $this->MakePdf = new MakeMultiPdf($log, $MpdfProvider, $requester, array(new EntitySlug(EntityType::Experiments, 3), new EntitySlug(EntityType::Experiments, 4)));
    }

    public function testGetFileName(): void
    {
        $this->assertStringContainsString('-elabftw-export.pdf', $this->MakePdf->getFileName());
    }
}
