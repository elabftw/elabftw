<?php
namespace Elabftw\Elabftw;

use PDO;

class EntityTest extends \PHPUnit_Framework_TestCase
{
    protected function setUp()
    {
        $this->Entity= new Entity();
    }

    public function testSetId()
    {
        $this->Entity->setId(1);
        $this->assertEquals(1, $this->Entity->id);
        $this->Entity->setId('1');
        $this->assertEquals(1, $this->Entity->id);
    }

    public function testSetLimit()
    {
        $this->Entity->setLimit('15');
        $this->assertEquals('LIMIT 15', $this->Entity->limit);
        $this->Entity->setLimit(15);
        $this->assertEquals('LIMIT 15', $this->Entity->limit);
    }
}
