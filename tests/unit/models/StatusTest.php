<?php

declare(strict_types=1);
/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

namespace Elabftw\Models;

use Elabftw\Enums\Action;
use Elabftw\Models\Users\Users;

use function mb_substr;

class StatusTest extends \PHPUnit\Framework\TestCase
{
    private ExperimentsStatus $Status;

    protected function setUp(): void
    {
        $this->Status = new ExperimentsStatus(new Teams(new Users(1, 1), 1), 1);
    }

    public function testCreate(): void
    {
        $title = 'New status';
        $color = '#29AEB9';
        $new = $this->Status->postAction(Action::Create, array('name' => $title, 'color' => $color));
        $this->assertIsInt($new);
        $this->Status->setId($new);
        $status = $this->Status->readOne();
        $this->assertIsArray($status);
        $this->assertSame($title, $status['title']);
        $this->assertSame(mb_substr($color, 1), $status['color']);
    }

    public function testGetApiPath(): void
    {
        $this->assertIsString($this->Status->getApiPath());
    }

    public function testUpdate(): void
    {
        $id = $this->Status->postAction(Action::Create, array('title' => 'Yop', 'color' => '#29AEB9', 'is_private' => 0));
        $Status = new ExperimentsStatus(new Teams(new Users(1, 1), 1), $id);
        $status = $Status->patch(Action::Update, array('title' => 'Updated', 'color' => '#121212'));
        $this->assertEquals('Updated', $status['title']);
        $this->assertEquals('121212', $status['color']);
    }

    public function testDestroy(): void
    {
        $id = $this->Status->postAction(Action::Create, array('title' => 'Yop', 'color' => '#29AEB9'));
        $Status = new ExperimentsStatus(new Teams(new Users(1, 1), 1), $id);
        $this->assertTrue($Status->destroy());
        $this->assertTrue($this->Status->destroy());
    }
}
