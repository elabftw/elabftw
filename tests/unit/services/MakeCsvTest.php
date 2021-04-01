<?php declare(strict_types=1);
/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

namespace Elabftw\Services;

use Elabftw\Models\Database;
use Elabftw\Models\Experiments;
use Elabftw\Models\Users;

class MakeCsvTest extends \PHPUnit\Framework\TestCase
{
    protected function setUp(): void
    {
        $this->MakeExp = new MakeCsv(new Experiments(new Users(1, 1)), '1 2 3');
        $this->MakeDb = new MakeCsv(new Database(new Users(1, 1)), '1 2 3');
    }

    public function testGetFileName()
    {
        $this->assertMatchesRegularExpression('/\d{8}-export.elabftw.csv/', $this->MakeExp->getFileName());
    }

    public function testGetCsvExp()
    {
        $csv = $this->MakeExp->getCsv();
    }

    public function testGetCsvDb()
    {
        $csv = $this->MakeDb->getCsv();
    }
}
