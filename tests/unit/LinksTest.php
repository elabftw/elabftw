<?php
namespace Elabftw\Elabftw;

use PDO;

class LinksTest extends \PHPUnit_Framework_TestCase
{
    protected function setUp()
    {
        $this->Experiments = new Experiments(1, 1, 1);
        $this->Links= new Links($this->Experiments);
    }

    public function testCreateReadDestroy()
    {
        $this->assertTrue($this->Links->create(1));
        $link = $this->Links->read();
        $this->assertTrue(is_array($link));
        $last = array_pop($link);
        $this->assertTrue($this->Links->destroy($last['linkid']));
    }

    public function testCreateAndDestroyAll()
    {
        $this->assertTrue($this->Links->create(1));
        $this->assertTrue($this->Links->destroyAll());
    }
}
