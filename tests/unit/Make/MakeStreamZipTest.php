<?php

declare(strict_types=1);
/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2022 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

namespace Elabftw\Make;

use Elabftw\Elabftw\CreateUpload;
use Elabftw\Elabftw\EntitySlug;
use Elabftw\Enums\EntityType;
use Elabftw\Models\Experiments;
use Elabftw\Models\Users;
use ZipStream\ZipStream;

class MakeStreamZipTest extends \PHPUnit\Framework\TestCase
{
    private MakeStreamZip $Make;

    protected function setUp(): void
    {
        $slugs = array('experiments:1', 'items:2', 'experiments:3');
        $slugsArr = array_map('\Elabftw\Elabftw\EntitySlug::fromString', $slugs);
        $Users = new Users(1, 1);
        $Zip = $this->createMock(ZipStream::class);
        $this->Make = new MakeStreamZip($Zip, $Users, $slugsArr);
    }

    public function testGetFileName(): void
    {
        $this->assertEquals('export.elabftw.zip', $this->Make->getFileName());
    }

    public function testGetZipExp(): void
    {
        $this->Make->getStreamZip();
    }

    public function testGetZipOneExp(): void
    {
        $Experiments = new Experiments(new Users(1, 1), 1);
        // add two files with the same name to that experiment
        $filepath = dirname(__DIR__, 2) . '/_data/example.png';
        $filename = 'similar';
        $Experiments->Uploads->create(new CreateUpload($filename, $filepath));
        $Experiments->Uploads->create(new CreateUpload($filename, $filepath));
        $Zip = $this->createMock(ZipStream::class);
        $MakeExp = new MakeStreamZip($Zip, new Users(1, 1), array(new EntitySlug(EntityType::Experiments, 1)));
        $MakeExp->getStreamZip();
        $this->assertTrue(str_ends_with($MakeExp->getFileName(), '.zip'));
    }
}
