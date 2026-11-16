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
use Elabftw\Exceptions\ForbiddenException;
use Elabftw\Exceptions\ResourceNotFoundException;
use Elabftw\Exceptions\ImproperActionException;
use Elabftw\Models\Users\Users;
use Elabftw\Params\OrderingParams;
use Elabftw\Traits\TestsUtilsTrait;

use function mb_substr;

class StatusTest extends \PHPUnit\Framework\TestCase
{
    use TestsUtilsTrait;

    private ExperimentsStatus $Status;

    protected function setUp(): void
    {
        $this->Status = new ExperimentsStatus(new Teams(new Users(1, 1), 1), 1);
    }

    public function testCreate(): void
    {
        $title = 'New status';
        $color = '#29aeb9';
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
        $id = $this->Status->postAction(Action::Create, array('title' => 'Yop', 'color' => '#29aeb9', 'is_private' => 0));
        $Status = new ExperimentsStatus(new Teams(new Users(1, 1), 1), $id);
        $status = $Status->patch(Action::Update, array('title' => 'Updated', 'color' => '#121212'));
        $this->assertEquals('Updated', $status['title']);
        $this->assertEquals('121212', $status['color']);
    }

    public function testDestroy(): void
    {
        $id = $this->Status->postAction(Action::Create, array('title' => 'Yop', 'color' => '#29aeb9'));
        $Status = new ExperimentsStatus(new Teams(new Users(1, 1), 1), $id);
        $this->assertTrue($Status->destroy());
        $this->assertTrue($this->Status->destroy());
    }

    public function testCannotReadStatusFromAnotherTeamThroughCurrentTeam(): void
    {
        $id = $this->Status->postAction(Action::Create, array('title' => 'Yop', 'color' => '#29aeb9'));
        $otherStatus = new ExperimentsStatus(new Teams($this->getRandomUserInTeam(2), 2), $id);
        $this->expectException(ResourceNotFoundException::class);
        $otherStatus->readOne();
    }

    public function testCannotReadOtherTeam(): void
    {
        $this->expectException(ImproperActionException::class);

        $otherTeam = new Teams($this->getRandomUserInTeam(2), 1);
        $otherTeam->readOne();
    }

    public function testUpdateNonAccessibleStatusThroughCurrentTeam(): void
    {
        $id = $this->Status->postAction(Action::Create, array('title' => 'Yop', 'color' => '#29aeb9'));
        $otherStatus = new ExperimentsStatus(new Teams($this->getRandomUserInTeam(2), 2), $id);
        $this->expectException(ResourceNotFoundException::class);
        $otherStatus->patch(Action::Update, array('title' => 'Coucou'));
    }

    public function testUpdateOrdering(): void
    {
        $first = $this->Status->postAction(Action::Create, array('name' => 'first'));
        $second = $this->Status->postAction(Action::Create, array('name' => 'second'));
        $this->Status->updateOrdering(new OrderingParams(array(
            'table' => 'experiments_status',
            'ordering' => array('status_' . $second, 'status_' . $first),
        )));
        $this->assertSame(0, $this->getOrdering($second));
        $this->assertSame(1, $this->getOrdering($first));
    }

    public function testCannotReorderStatusFromAnotherTeam(): void
    {
        $first = $this->Status->postAction(Action::Create, array('name' => 'first'));
        $second = $this->Status->postAction(Action::Create, array('name' => 'second'));
        $this->Status->updateOrdering(new OrderingParams(array(
            'table' => 'experiments_status',
            'ordering' => array('status_' . $first, 'status_' . $second),
        )));
        $attacker = $this->getRandomUserInTeam(2);
        $attackerStatus = new ExperimentsStatus(new Teams($attacker, 2));
        try {
            $attackerStatus->updateOrdering(new OrderingParams(array(
                'table' => 'experiments_status',
                'ordering' => array('status_' . $second, 'status_' . $first),
            )));
            $this->fail('A user from another team reordered statuses.');
        } catch (ForbiddenException) {
            $this->assertSame(0, $this->getOrdering($first));
            $this->assertSame(1, $this->getOrdering($second));
        }
    }

    public function testCannotReorderWithoutTeam(): void
    {
        $id = $this->Status->postAction(Action::Create, array('name' => 'noteam'));
        $Status = new ExperimentsStatus(new Teams(new Users(1, 1)));
        $this->expectException(ForbiddenException::class);
        $Status->updateOrdering(new OrderingParams(array(
            'table' => 'experiments_status',
            'ordering' => array('status_' . $id),
        )));
    }

    private function getOrdering(int $id): int
    {
        return (int) new ExperimentsStatus(new Teams(new Users(1, 1), 1), $id)->readOne()['ordering'];
    }
}
