<?php
namespace Elabftw\Elabftw;

use PDO;

class BannedUsersTest extends \PHPUnit_Framework_TestCase
{
    protected function setUp()
    {
        $this->BannedUsers= new BannedUsers(new Config);
    }

    public function testCreate()
    {
        $fingerprint = md5('yep');
        $this->assertTrue($this->BannedUsers->create($fingerprint));
    }

    public function testReadAll()
    {
        $this->assertTrue(is_array($this->BannedUsers->readAll()));
    }
}
