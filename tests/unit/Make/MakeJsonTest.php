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

use Elabftw\Traits\TestsUtilsTrait;

class MakeJsonTest extends \PHPUnit\Framework\TestCase
{
    use TestsUtilsTrait;

    private MakeJson $Make;

    protected function setUp(): void
    {
        $this->Make = new MakeJson(
            array($this->getFreshExperiment(), $this->getFreshExperiment())
        );
    }

    public function testGetFileName(): void
    {
        $this->assertEquals('export-elabftw.json', $this->Make->getFileName());
    }

    public function testGetJson(): void
    {
        $this->assertIsString($this->Make->getFileContent());
    }
}
