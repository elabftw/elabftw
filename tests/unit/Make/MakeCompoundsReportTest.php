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

use Elabftw\Models\Compounds;
use Elabftw\Models\Users\Users;
use Elabftw\Services\HttpGetter;
use Elabftw\Services\NullFingerprinter;
use GuzzleHttp\Client;

class MakeCompoundsReportTest extends \PHPUnit\Framework\TestCase
{
    private MakeCompoundsReport $Make;

    protected function setUp(): void
    {
        $requester = new Users(1, 1);
        $httpGetter = new HttpGetter(new Client(), verifyTls: false);
        $this->Make = new MakeCompoundsReport(new Compounds($httpGetter, $requester, new NullFingerprinter(), false));
    }

    public function testGetFileName(): void
    {
        $this->assertMatchesRegularExpression('/\d{4}-\d{2}-\d{2}-compounds.elabftw.csv/', $this->Make->getFileName());
    }

    public function testGetCsv(): void
    {
        $this->assertIsString($this->Make->getFileContent());
    }
}
