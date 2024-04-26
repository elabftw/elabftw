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

use Elabftw\Models\Experiments;
use Elabftw\Models\Users;

class MakeJsonTest extends \PHPUnit\Framework\TestCase
{
    private MakeJson $Make;

    protected function setUp(): void
    {
        $idArr = array('1', '2', '3');
        $this->Make = new MakeJson(new Experiments(new Users(1, 1)), $idArr);
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
