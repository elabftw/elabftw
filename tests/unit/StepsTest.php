<?php
namespace Elabftw\Models;

class StepsTest extends \PHPUnit\Framework\TestCase
{
    protected function setUp()
    {
        $this->Users = new Users(1);
        $this->Experiments = new Experiments($this->Users, 1);
        $this->Steps = new Steps($this->Experiments);
    }

    public function testCreate()
    {
        $this->Steps->create('do this');
    }
    public function testFinish()
    {
        $this->Steps->finish(1);
    }
    public function testReadAll()
    {
        $this->assertTrue(is_array($this->Steps->readAll()));
    }
    public function testDestroy()
    {
        $this->Steps->destroy(1);
    }
}
