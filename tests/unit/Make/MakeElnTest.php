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

use Elabftw\Enums\Storage;
use Elabftw\Models\Users\Users;
use Elabftw\Traits\TestsUtilsTrait;
use ZipStream\ZipStream;

class MakeElnTest extends \PHPUnit\Framework\TestCase
{
    use TestsUtilsTrait;

    private MakeEln $Make;

    protected function setUp(): void
    {
        $targets = array(
            $this->getFreshExperiment(),
            $this->getFreshExperiment(),
            $this->getFreshItem(),
        );
        $Users = new Users(1, 1);
        $Zip = $this->createMock(ZipStream::class);
        $this->Make = new MakeEln($Zip, $Users, Storage::EXPORTS->getStorage(), $targets);
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
