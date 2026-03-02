<?php

declare(strict_types=1);
/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2023 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

namespace Elabftw\Models;

use Elabftw\Params\BaseQueryParams;
use Symfony\Component\HttpFoundation\InputBag;

class InfoTest extends \PHPUnit\Framework\TestCase
{
    private Info $Info;

    protected function setUp(): void
    {
        $this->Info = new Info();
    }

    public function testGetApiPath(): void
    {
        $this->assertEquals('api/v2/info/', $this->Info->getApiPath());
    }

    public function testRead(): void
    {
        $info = $this->Info->readOne();
        $this->assertTrue(is_array($info));
        $this->assertEquals(4, $info['experiments_timestamped_count']);
    }

    public function testHist(): void
    {
        // use the readAll() to hit the if, twice to get all branches: with and without columns
        $this->Info->readAll(new BaseQueryParams(new InputBag(array('hist' => 1))));
        $hist = $this->Info->readAll(new BaseQueryParams(new InputBag(array('hist' => 1, 'columns' => 12))));
        $this->assertIsArray($hist['items']);
        $this->assertIsArray($hist['experiments']);
        $this->assertIsArray($hist['users']);
    }
}
