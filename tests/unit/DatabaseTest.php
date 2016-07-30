<?php
namespace Elabftw\Elabftw;

use PDO;

class DatabaseTest extends \PHPUnit_Framework_TestCase
{
    protected function setUp()
    {
        $this->Database= new Database(1);
    }

    public function testCreateAndDestroy()
    {
        $new = $this->Database->create(1, 1);
        $this->assertTrue((bool) Tools::checkId($new));
        $this->Database->setId($new);
        $this->assertTrue($this->Database->destroy());
    }

    public function testSetId()
    {
        $this->setExpectedException('Exception');
        $this->Database->setId('alpha');
    }

    public function testRead()
    {
        $this->Database = new Database(1, 1);
        $item = $this->Database->read();
        $this->assertTrue(is_array($item));
        $this->assertEquals('Database item 1', $item['title']);
        $this->assertEquals('20160729', $item['date']);
        $this->Database = new Database(1, 9999);
        $this->setExpectedException('Exception');
        $this->Database->read();
    }

    public function testReadAll()
    {
        $this->assertTrue(is_array($this->Database->readAll()));
    }

    public function testUpdate()
    {
        $this->Database->setId(1);
        $this->assertTrue($this->Database->update('Database item 1', '20160729', 'body', 1));
    }

    public function testUpdateRating()
    {
        $this->Database->setId(1);
        $this->assertTrue($this->Database->updateRating(1));
    }

    public function testDuplicate()
    {
        $this->Database->setId(1);
        $this->assertTrue((bool) Tools::checkId($this->Database->duplicate(1)));
    }

    public function testToggleLock()
    {
        $this->Database->setId(1);

        // lock
        $this->assertTrue($this->Database->toggleLock());
        $item = $this->Database->read();
        $this->assertTrue((bool) $item['locked']);

        // unlock
        $this->assertTrue($this->Database->toggleLock());
        $item = $this->Database->read();
        $this->assertFalse((bool) $item['locked']);
    }
}
