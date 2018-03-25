<?php
namespace Elabftw\Elabftw;

class DatabaseTest extends \PHPUnit\Framework\TestCase
{
    protected function setUp()
    {
        $this->Users = new Users(1);
        $this->Database= new Database($this->Users);
    }

    public function testCreateAndDestroy()
    {
        $new = $this->Database->create(1);
        $this->assertTrue((bool) Tools::checkId($new));
        $this->Database->setId($new);
        $this->assertTrue($this->Database->destroy());
    }

    public function testSetId()
    {
        $this->expectException(\TypeError::class);
        $this->Database->setId('alpha');
    }

    public function testRead()
    {
        $new = $this->Database->create(1);
        $this->Database->setId($new);
        $this->Database->canOrExplode('read');
        $this->assertTrue(is_array($this->Database->entityData));
        $this->assertEquals('Untitled', $this->Database->entityData['title']);
        $this->assertEquals(Tools::kdate(), $this->Database->entityData['date']);
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
        $this->Database->canOrExplode('read');
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
