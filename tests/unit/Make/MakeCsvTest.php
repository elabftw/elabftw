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

class MakeCsvTest extends \PHPUnit\Framework\TestCase
{
    use TestsUtilsTrait;

    private MakeCsv $Make;

    protected function setUp(): void
    {
        $this->Make = new MakeCsv(
            array($this->getFreshExperiment(), $this->getFreshExperiment())
        );
    }

    public function testGetFileName(): void
    {
        $this->assertTrue(str_ends_with($this->Make->getFileName(), 'export.elabftw.csv'));
    }

    public function testGetCsv(): void
    {
        $this->assertIsString($this->Make->getFileContent());
    }

    public function testGetContentType(): void
    {
        $this->assertEquals('text/csv; charset=UTF-8', $this->Make->getContentType());
    }
}
