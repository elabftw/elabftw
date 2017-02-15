<?php
namespace Elabftw\Elabftw;

use PDO;

class TemplatesTest extends \PHPUnit_Framework_TestCase
{
    protected function setUp()
    {
        $this->Templates= new Templates(new Users(1));
    }

    public function testCreate()
    {
        $this->assertTrue($this->Templates->create('Test tpl', 'pwet', 1));
    }

    public function testRead()
    {
        $this->Templates->setId(1);
        $this->assertTrue(is_array($this->Templates->read(1)));
    }
    public function testReadFromUserid()
    {
        $this->assertTrue(is_array($this->Templates->readFromUserid()));
    }
    public function testUpdate()
    {
        $this->assertTrue($this->Templates->update('Plop'));
    }
    public function testDestroy()
    {
        $this->assertTrue($this->Templates->destroy(1, 1));
    }
}
