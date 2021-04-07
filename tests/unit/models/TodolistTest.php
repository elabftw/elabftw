<?php declare(strict_types=1);

namespace Elabftw\Models;

use Elabftw\Elabftw\CreateTodoitem;
use Elabftw\Elabftw\OrderingParams;
use Elabftw\Elabftw\UpdateTodoitem;

class TodolistTest extends \PHPUnit\Framework\TestCase
{
    protected function setUp(): void
    {
        $this->Todolist = new Todolist(1);
    }

    public function testCreate()
    {
        $content = 'write more tests';
        $this->assertIsInt($this->Todolist->create(new CreateTodoitem($content)));
    }

    public function testRead()
    {
        $this->assertTrue(is_array($this->Todolist->read()));
    }

    public function testUpdate()
    {
        $this->Todolist->setId(1);
        $this->assertTrue($this->Todolist->update(new UpdateTodoitem('write way more tests')));
    }

    public function testUpdateOrdering()
    {
        $content = 'write more tests';
        $this->Todolist->create(new CreateTodoitem($content));
        $this->Todolist->create(new CreateTodoitem($content));
        $ordering = array('todoItem_3', 'todoItem_2', 'todoItem_4');
        $OrderingParams = new OrderingParams('todolist', $ordering);
        $this->Todolist->updateOrdering($OrderingParams);
    }

    public function testDestroy()
    {
        $this->Todolist->setId(1);
        $this->Todolist->destroy();
    }
}
