<?php
namespace Elabftw\Models;

class ItemsTypesTest extends \PHPUnit\Framework\TestCase
{
    protected function setUp()
    {
        $this->ItemsTypes= new ItemsTypes(new Users(1));
    }

    public function testCreateUpdateDestroy()
    {
        $this->ItemsTypes->create('new', 'fffccc', 0, 'body');
        $itemsTypes = $this->ItemsTypes->readAll();
        $last = array_pop($itemsTypes);
        $this->ItemsTypes->update($last['category_id'], 'newname', 'fffccc', 1, 'newbody');
        $this->ItemsTypes->setId($last['category_id']);
        $this->assertEquals('newbody', $this->ItemsTypes->read($last['category_id']));
        $this->ItemsTypes->destroy($last['category_id']);
    }
}
