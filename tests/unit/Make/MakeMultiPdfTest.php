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
use Elabftw\Models\Instance2Rors;
use Elabftw\Models\Teams2Rors;
use Elabftw\Models\Users2Rors;
use Elabftw\Models\Users\Users;
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
        $instance2Rors = new Instance2Rors();
        $teams2Rors = new Teams2Rors($requester->getTeam());
        $users2Rors = new Users2Rors($requester->getUserid());
        $this->MakePdf = new MakeMultiPdf(
            $log,
            $MpdfProvider,
            $requester,
            array(new EntitySlug(EntityType::Experiments, 3), new EntitySlug(EntityType::Experiments, 4)),
            $instance2Rors,
            $teams2Rors,
            $users2Rors,
        );
    }

    public function testGetFileName(): void
    {
        $this->assertStringContainsString('-elabftw-export.pdf', $this->MakePdf->getFileName());
    }
}
