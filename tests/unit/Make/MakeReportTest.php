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

    public function testCsvHeaderIsCorrect(): void
    {
        // getHeaders is protected so get them from the file content
        $csvContent = $this->Make->getFileContent();

        // first line of csv contains headers
        $lines = explode("\n", $csvContent);
        $headerLine = trim($lines[0]);
        // due to a UTF-8 BOM, strip the BOM before comparing
        $headerLine = preg_replace('/^\xEF\xBB\xBF/', '', $headerLine);

        $expectedHeaders = MakeReport::CSV_HEADERS;
        // csv escapes special characters so make sure we compare the correct string
        $expectedLine = implode(',', $expectedHeaders);

        $this->assertSame($expectedLine, $headerLine);
    }
}
