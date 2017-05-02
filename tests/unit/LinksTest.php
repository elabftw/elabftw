<?php
namespace Elabftw\Elabftw;

use PDO;

class LinksTest extends \PHPUnit_Framework_TestCase
{
    protected function setUp()
    {
        $_SESSION['userid'] = '1';
        $_SESSION['team_id'] = '1';
        $_SESSION['is_admin'] = '0';
        $this->Users = new Users(1);
        $this->Experiments = new Experiments($this->Users, '1');
        $this->Links= new Links($this->Experiments);
    }

    public function testCreateReadDestroy()
    {
        $this->assertTrue($this->Links->create('1'));
        $link = $this->Links->read();
        $this->assertTrue(is_array($link));
        $last = array_pop($link);
        $this->assertTrue($this->Links->destroy($last['linkid']));
    }

    public function testCreateAndDestroyAll()
    {
        $this->assertTrue($this->Links->create('1'));
        $this->assertTrue($this->Links->destroyAll());
    }
}
