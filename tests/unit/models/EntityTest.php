<?php declare(strict_types=1);
/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

namespace Elabftw\Models;

class EntityTest extends \PHPUnit\Framework\TestCase
{
    protected function setUp(): void
    {
        $this->Users = new Users(1, 1);
        $this->Experiments = new Experiments($this->Users, 1);
        $this->Database = new Database($this->Users, 1);
    }

    public function testTogglePin(): void
    {
        $this->Experiments->togglePin();
        $this->assertTrue($this->Experiments->isPinned());
        $this->assertCount(1, $this->Experiments->getPinned());
        $this->Experiments->togglePin();
        $this->assertCount(0, $this->Experiments->getPinned());

        $this->Database->togglePin();
        $this->assertTrue($this->Database->isPinned());
        $this->assertCount(1, $this->Database->getPinned());
        $this->Database->togglePin();
        $this->assertCount(0, $this->Database->getPinned());
    }
}
