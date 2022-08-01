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
use Elabftw\Exceptions\IllegalActionException;
use Elabftw\Exceptions\ImproperActionException;

class SchedulerTest extends \PHPUnit\Framework\TestCase
{
    private Scheduler $Scheduler;

    private int $id;

    private array $delta;

    protected function setUp(): void
    {
        $Users = new Users(1, 1);
        $Items = new Items($Users, 1);
        $this->Scheduler = new Scheduler($Items);
        $this->id = 1;
        $this->delta = array(
            'years' => '0',
            'months' => '0',
            'days' => '1',
            'milliseconds' => '0',
        );
    }

    public function testCreate(): int
    {
        $d = new DateTime('now');
        $start = $d->format('c');
        $d->add(new DateInterval('PT2H'));
        $end = $d->format('c');
        $this->id = $this->Scheduler->create($start, $end, 'Yep');
        return $this->id;
    }

    public function testReadAllFromTeam(): void
    {
        $d = new DateTime('now');
        $start = $d->format('c');
        $d->add(new DateInterval('P6D'));
        $end = $d->format('c');
        $this->assertIsArray($this->Scheduler->readAllFromTeam($start, $end));
    }

    public function testRead(): void
    {
        $d = new DateTime('now');
        $start = $d->format('c');
        $d->add(new DateInterval('P6D'));
        $end = $d->format('c');
        $this->assertIsArray($this->Scheduler->read($start, $end));
    }

    public function testBind(): void
    {
        $this->Scheduler->setId($this->testCreate());
        $this->assertTrue($this->Scheduler->bind(3, 'experiment'));
        $this->assertTrue($this->Scheduler->bind(3, 'item_link'));
    }

    public function testBindIncorrect(): void
    {
        $this->Scheduler->setId($this->testCreate());
        $this->expectException(IllegalActionException::class);
        $this->Scheduler->bind(3, 'blah');
    }

    public function testUnbind(): void
    {
        $this->Scheduler->setId($this->testCreate());
        $this->assertTrue($this->Scheduler->unbind('experiment'));
        $this->assertTrue($this->Scheduler->unbind('item_link'));
    }

    public function testCanWriteAndWeAreAdmin(): void
    {
        $Users = new Users(2, 1);
        $Items = new Items($Users, 1);
        $Scheduler = new Scheduler($Items);
        $d = new DateTime('tomorrow');
        $start = $d->format('c');
        $d->add(new DateInterval('PT2H'));
        $end = $d->format('c');
        // create with user
        $id = $Scheduler->create($start, $end, 'Yep');
        // write with admin
        $this->Scheduler->setId($id);
        $this->assertTrue($this->Scheduler->destroy());
    }

    public function testCanNotWrite(): void
    {
        $Users = new Users(2, 1);
        $Items = new Items($Users, 1);
        $Scheduler = new Scheduler($Items);
        $Scheduler->setId($this->testCreate());
        // try write event created by admin as user
        $this->expectException(ImproperActionException::class);
        $Scheduler->bind(3, 'experiment');
    }

    public function testUpdateStart(): void
    {
        $this->Scheduler->setId($this->testCreate());
        $this->Scheduler->updateStart($this->delta);
        $delta = array(
            'years' => '0',
            'months' => '0',
            'days' => '1',
            'milliseconds' => '1111',
        );
        $this->Scheduler->updateStart($delta);
    }

    public function testUpdateEnd(): void
    {
        $this->Scheduler->setId($this->testCreate());
        $this->Scheduler->updateEnd($this->delta);
        $delta = array(
            'years' => '0',
            'months' => '0',
            'days' => '1',
            'milliseconds' => '1111',
        );
        $this->Scheduler->updateEnd($delta);
    }

    public function testDestroy(): void
    {
        $id = $this->Scheduler->create('2016-07-22T19:42:00+02:00', '2016-07-23T19:42:00+02:00', 'Yep');
        $this->Scheduler->setId($id);
        $this->Scheduler->destroy();
    }
}
