<?php
namespace Elabftw\Elabftw;

use PDO;

class TodolistTest extends \PHPUnit_Framework_TestCase
{
    protected function setUp()
    {
        $this->Todolist = new Todolist(1);
    }

    public function testCreate()
    {
        $body = 'write more tests';
        $this->assertTrue((bool) Tools::checkId($this->Todolist->create($body)));
    }

    public function testReadAll()
    {
        $this->assertTrue(is_array($this->Todolist->readAll()));
    }

    public function testUpdate()
    {
        $this->assertTrue($this->Todolist->update(1, "write more unit tests"));
    }

    public function testUpdateOrdering()
    {
        $body = 'write more tests';
        $this->Todolist->create($body);
        $this->Todolist->create($body);
        $post = array(
            'ordering' => array('todoItem_3', 'todoItem_2', 'todoItem_4')
        );
        $this->assertTrue($this->Todolist->updateOrdering($post));
    }

    public function testDestroy()
    {
        $this->assertTrue($this->Todolist->destroy(1));
    }

    public function testDestroyAll()
    {
        $this->assertTrue($this->Todolist->destroyAll());
    }
}
