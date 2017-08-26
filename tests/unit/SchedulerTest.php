<?php
namespace Elabftw\Elabftw;

use PDO;

class SchedulerTest extends \PHPUnit_Framework_TestCase
{
    protected function setUp()
    {
        $Users = new Users(1);
        $Database = new Database($Users, 1);
        $this->Scheduler = new Scheduler($Database);
    }

    public function testCreate()
    {
        $this->assertTrue($this->Scheduler->create('2016-07-22T19:42:00', '2016-07-23T19:42:00', 'Yep'));
    }

    public function testUpdateStart()
    {
        $this->Scheduler->setId(1);
        $this->assertTrue($this->Scheduler->updateStart('2016-07-22T19:40:00', '2016-07-22T20:40:00'));
    }
    public function testUpdateEnd()
    {
        $this->Scheduler->setId(1);
        $this->assertTrue($this->Scheduler->updateEnd('2016-07-22T20:45:00'));
    }

    public function testDestroy()
    {
        $this->Scheduler->setId(1);
        $this->assertTrue($this->Scheduler->destroy());
    }
}
