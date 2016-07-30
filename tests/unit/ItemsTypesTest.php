<?php
namespace Elabftw\Elabftw;

use PDO;

class ItemsTypesTest extends \PHPUnit_Framework_TestCase
{
    protected function setUp()
    {
        $this->ItemsTypes= new ItemsTypes(1);
    }

    public function testCreateUpdateDestroy()
    {
        $this->assertTrue($this->ItemsTypes->create('new', 'fffccc', 0, 'body'));
        $itemsTypes = $this->ItemsTypes->readAll();
        $last = array_pop($itemsTypes);
        $this->assertTrue($this->ItemsTypes->update($last['id'], 'newname', 'fffccc', 1, 'newbody'));
        $this->assertEquals('newbody', $this->ItemsTypes->read($last['id']));
        $this->assertTrue($this->ItemsTypes->destroy($last['id']));
        $last = array_pop($itemsTypes);
    }
}
