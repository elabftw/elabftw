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

use Elabftw\Enums\Action;
use Elabftw\Exceptions\ImproperActionException;

class InfoTest extends \PHPUnit\Framework\TestCase
{
    private Info $Info;

    protected function setUp(): void
    {
        $this->Info = new Info();
    }

    public function testGetPage(): void
    {
        $this->assertEquals('api/v2/info/', $this->Info->getPage());
    }

    public function testPost(): void
    {
        $this->expectException(ImproperActionException::class);
        $this->Info->postAction(Action::Create, array());
    }

    public function testPatch(): void
    {
        $this->expectException(ImproperActionException::class);
        $this->Info->patch(Action::Create, array());
    }

    public function testDelete(): void
    {
        $this->expectException(ImproperActionException::class);
        $this->Info->destroy();
    }

    public function testRead(): void
    {
        $info = $this->Info->readOne();
        $this->assertTrue(is_array($info));
        $this->assertEquals(4, $info['experiments_timestamped_count']);
    }
}
