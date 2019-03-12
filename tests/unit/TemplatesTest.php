<?php
namespace Elabftw\Models;

class TemplatesTest extends \PHPUnit\Framework\TestCase
{
    protected function setUp()
    {
        $this->Templates= new Templates(new Users(1));
    }

    public function testCreate()
    {
        $this->Templates->create('Test tpl', 'pwet', 1);
    }

    public function testRead()
    {
        $this->Templates->setId(1);
        $this->assertTrue(is_array($this->Templates->read(1)));
    }
    public function testDuplicate()
    {
        $this->Templates->setId(1);
        $this->assertInternalType("int", $this->Templates->duplicate());
    }
    public function testReadAll()
    {
        $this->assertTrue(is_array($this->Templates->readAll()));
    }
    public function testReadFromTeam()
    {
        $this->assertTrue(is_array($this->Templates->readFromTeam()));
    }
    public function testReadCommonBody()
    {
        $this->Templates->Users->userData['use_markdown'] = 1;
        $this->assertEquals('', $this->Templates->readCommonBody());
    }
    public function testUpdateCommon()
    {
        $this->Templates->updateCommon('Plop');
    }
    public function testUpdateTpl()
    {
        $this->Templates->updateTpl(1, 'my tpl', 'Plop');
    }
    public function testDestroy()
    {
        $this->Templates->destroy();
    }
    public function testToggleLock()
    {
        $this->Templates->toggleLock();
    }
}
