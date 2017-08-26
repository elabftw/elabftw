<?php
namespace Elabftw\Elabftw;

use PDO;

class LinksTest extends \PHPUnit_Framework_TestCase
{
    protected function setUp()
    {
        $this->Users = new Users(1);
        $this->Experiments = new Experiments($this->Users, '1');
    }

    public function testCreateReadDestroy()
    {
        $this->assertTrue($this->Experiments->Links->create('1'));
        $link = $this->Experiments->Links->readAll();
        $this->assertTrue(is_array($link));
        $last = array_pop($link);
        $this->assertTrue($this->Experiments->Links->destroy($last['linkid']));
    }

    public function testCreateAndDestroyAll()
    {
        $this->assertTrue($this->Experiments->Links->create('1'));
        $this->assertTrue($this->Experiments->Links->destroyAll());
    }
}
