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

use Elabftw\Models\Users;
use ZipStream\ZipStream;

class MakeElnTest extends \PHPUnit\Framework\TestCase
{
    private MakeEln $Make;

    protected function setUp(): void
    {
        $slugs = array('experiments:1', 'items:2', 'experiments:3');
        $slugsArr = array_map('\Elabftw\Elabftw\EntitySlug::fromString', $slugs);
        $Users = new Users(1, 1);
        $Zip = $this->createMock(ZipStream::class);
        $this->Make = new MakeEln($Zip, $Users, $slugsArr);
    }

    public function testGetFileName(): void
    {
        $this->assertStringEndsWith('-export.eln', $this->Make->getFileName());
    }

    public function testGetElnExp(): void
    {
        $this->Make->getStreamZip();
    }
}
