<?php declare(strict_types=1);
/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

namespace Elabftw\Services;

use Elabftw\Models\Teams;
use Elabftw\Models\Users;
use ReflectionClass;

class MakeReportTest extends \PHPUnit\Framework\TestCase
{
    private MakeReport $Make;

    protected function setUp(): void
    {
        $this->Make = new MakeReport(new Teams((new Users(1, 1))));
    }

    public function testGetFileName(): void
    {
        $this->assertMatchesRegularExpression('/\d{4}-\d{2}-\d{2}-report.elabftw.csv/', $this->Make->getFileName());
    }

    public function testHeaderMatchesColumnNames(): void
    {
        $header = $this->callMethod($this->Make, 'getHeader', array());
        $rows = array_keys($this->callMethod($this->Make, 'getRows', array())[0]);
        $this->assertEquals($header, $rows);
    }

    public function testGetCsv(): void
    {
        $this->assertIsString($this->Make->getFileContent());
    }

    /**
     * Helper method to call protected/private methods
     */
    protected function callMethod(mixed $obj, string $name, array $args): mixed
    {
        $class = new ReflectionClass($obj);
        $method = $class->getMethod($name);
        $method->setAccessible(true);
        return $method->invokeArgs($obj, $args);
    }
}
