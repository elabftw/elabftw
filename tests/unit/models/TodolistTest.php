<?php declare(strict_types=1);

namespace Elabftw\Models;

use Elabftw\Elabftw\Tools;

class TodolistTest extends \PHPUnit\Framework\TestCase
{
    protected function setUp(): void
    {
        $this->Users = new Users(1);
        $this->Todolist = new Todolist($this->Users);
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
        $this->Todolist->update(1, 'write more unit tests');
    }

    public function testUpdateOrdering()
    {
        $body = 'write more tests';
        $this->Todolist->create($body);
        $this->Todolist->create($body);
        $post = array(
            'ordering' => array('todoItem_3', 'todoItem_2', 'todoItem_4'),
            'table' => 'todolist',
        );
        $this->Todolist->updateOrdering($this->Users, $post);
    }

    public function testDestroy()
    {
        $this->Todolist->destroy(1);
    }

    public function testDestroyAll()
    {
        $this->Todolist->destroyAll();
    }
}
