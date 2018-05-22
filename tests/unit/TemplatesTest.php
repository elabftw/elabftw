<?php
namespace Elabftw\Elabftw;

use PDO;

class TemplatesTest extends \PHPUnit\Framework\TestCase
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
    public function testReadAll()
    {
        $this->assertTrue(is_array($this->Templates->readAll()));
    }
    public function testUpdate()
    {
        $this->assertTrue($this->Templates->updateCommon('Plop'));
    }
    public function testDestroy()
    {
        $this->assertTrue($this->Templates->destroy(1, 1));
    }
}
