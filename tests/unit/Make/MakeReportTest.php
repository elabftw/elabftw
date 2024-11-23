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

use Elabftw\Models\Users;

class MakeReportTest extends \PHPUnit\Framework\TestCase
{
    private MakeReport $Make;

    protected function setUp(): void
    {
        $requester = new Users(1, 1);
        $this->Make = new MakeReport($requester);
    }

    public function testGetFileName(): void
    {
        $this->assertMatchesRegularExpression('/\d{4}-\d{2}-\d{2}-report.elabftw.csv/', $this->Make->getFileName());
    }

    public function testGetCsv(): void
    {
        $this->assertIsString($this->Make->getFileContent());
    }
}
