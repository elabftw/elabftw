<?php declare(strict_types=1);
/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

namespace Elabftw\Models;

use DateInterval;
use DateTime;
use Elabftw\Enums\Action;
use Elabftw\Exceptions\DatabaseErrorException;
use Elabftw\Exceptions\ImproperActionException;

class SchedulerTest extends \PHPUnit\Framework\TestCase
{
    private Scheduler $Scheduler;

    private array $delta;

    private string $start;

    private string $end;

    protected function setUp(): void
    {
        $Users = new Users(1, 1);
        $Items = new Items($Users, 1);
        $d = new DateTime('now');
        $this->start = $d->format('c');
        $d->add(new DateInterval('PT2H'));
        $this->end = $d->format('c');
        $this->Scheduler = new Scheduler($Items, null, $this->start, $this->end);
        $this->delta = array(
            'years' => '0',
            'months' => '0',
            'days' => '1',
            'milliseconds' => '0',
        );
    }

    public function testGetPage(): void
    {
        $this->assertEquals('api/v2/event/', $this->Scheduler->getPage());
    }

    public function testCreate(): int
    {
        $id = $this->Scheduler->postAction(Action::Create, array('start' => $this->start, 'end' => $this->end, 'title' => 'Yep'));
        $this->assertIsInt($id);
        return $id;
    }

    public function testFailure(): void
    {
        $Users = new Users(1, 1);
        $Items = new Items($Users);
        $Scheduler = new Scheduler($Items, null, $this->start, $this->end);
        $this->expectException(ImproperActionException::class);
        $Scheduler->postAction(Action::Create, array());
    }

    public function testReadFromAnItem(): void
    {
        $this->assertIsArray($this->Scheduler->readAll());
        $Items = new Items(new Users(1, 1), 1);
        $this->Scheduler = new Scheduler($Items, null, $this->start, $this->end);
        $this->assertIsArray($this->Scheduler->readOne());
    }

    public function testPatchEpoch(): void
    {
        $Items = new Items(new Users(1, 1), 1);
        $id = $this->testCreate();
        $Scheduler = new Scheduler($Items, $id, $this->start, $this->end);
        $this->assertIsArray($Scheduler->patch(Action::Update, array('target' => 'start_epoch', 'epoch' => date('U'))));
        $this->assertIsArray($Scheduler->patch(Action::Update, array('target' => 'end_epoch', 'epoch' => date('U'))));
        $this->expectException(ImproperActionException::class);
        $Scheduler->patch(Action::Update, array('target' => 'oops', 'epoch' => date('U')));
        $this->expectException(ImproperActionException::class);
        $Scheduler->patch(Action::Update, array('target' => 'end_epoch', 'epoch' => ''));
    }

    public function testBind(): void
    {
        $this->Scheduler->setId($this->testCreate());
        $this->assertIsArray($this->Scheduler->patch(Action::Update, array('target' => 'experiment', 'id' => 3)));
        $this->assertIsArray($this->Scheduler->patch(Action::Update, array('target' => 'item_link', 'id' => 3)));
    }

    public function testBindIncorrect(): void
    {
        $this->Scheduler->setId($this->testCreate());
        $this->expectException(DatabaseErrorException::class);
        $this->Scheduler->patch(Action::Update, array('target' => 'experiment', 'id' => -12));
    }

    public function testUnbind(): void
    {
        $this->Scheduler->setId($this->testCreate());
        $this->assertIsArray($this->Scheduler->patch(Action::Update, array('target' => 'experiment', 'id' => null)));
        $this->assertIsArray($this->Scheduler->patch(Action::Update, array('target' => 'item_link', 'id' => null)));
    }

    public function testCanWriteAndWeAreAdmin(): void
    {
        $Users = new Users(2, 1);
        $Items = new Items($Users, 3);
        $Scheduler = new Scheduler($Items, null, $this->start, $this->end);
        // create with user, make sure it's in the future!
        $d = new DateTime('now');
        $d->add(new DateInterval('PT2H'));
        $start = $d->format('c');
        $d->add(new DateInterval('PT4H'));
        $end = $d->format('c');
        $id = $Scheduler->postAction(Action::Create, array('start' => $start, 'end' => $end, 'title' => 'Yep'));
        // write with admin
        $this->Scheduler->setId($id);
        $this->assertTrue($this->Scheduler->destroy());
    }

    public function testCanNotWrite(): void
    {
        $Users = new Users(2, 1);
        $Items = new Items($Users, 1);
        $d = new DateTime('now');
        $d->add(new DateInterval('PT2H'));
        $start = $d->format('c');
        $d->add(new DateInterval('PT4H'));
        $end = $d->format('c');
        $Scheduler = new Scheduler($Items, null, $start, $end);
        $Scheduler->setId($this->testCreate());
        // try write event created by admin as user
        $this->expectException(ImproperActionException::class);
        $Scheduler->patch(Action::Update, array('target' => 'experiment', 'id' => 3));
    }

    public function testUpdateStart(): void
    {
        $this->Scheduler->setId($this->testCreate());
        $this->Scheduler->patch(Action::Update, array('target' => 'start', 'delta' => $this->delta));
        $delta = array(
            'years' => '0',
            'months' => '0',
            'days' => '1',
            'milliseconds' => '1111',
        );
        $this->Scheduler->patch(Action::Update, array('target' => 'start', 'delta' => $delta));
    }

    public function testUpdateEnd(): void
    {
        $this->Scheduler->setId($this->testCreate());
        $this->Scheduler->patch(Action::Update, array('target' => 'end', 'delta' => $this->delta));
        $delta = array(
            'years' => '0',
            'months' => '0',
            'days' => '1',
            'milliseconds' => '1111',
        );
        $this->Scheduler->patch(Action::Update, array('target' => 'end', 'delta' => $delta));
    }

    public function testDestroy(): void
    {
        $id = $this->Scheduler->postAction(Action::Create, array('start' => '2016-07-22T19:42:00+02:00', 'end' => '2016-07-23T19:42:00+02:00', 'title' => 'Yep'));
        $this->Scheduler->setId($id);
        $this->assertTrue($this->Scheduler->destroy());
    }
}
