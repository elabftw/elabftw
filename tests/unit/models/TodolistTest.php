<?php declare(strict_types=1);

namespace Elabftw\Models;

use Elabftw\Elabftw\OrderingParams;
use Elabftw\Elabftw\ParamsProcessor;
use Elabftw\Services\Check;

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
        $this->assertTrue((bool) Check::id($this->Todolist->create(new ParamsProcessor(array('template' => $body)))));
    }

    public function testRead()
    {
        $this->assertTrue(is_array($this->Todolist->read()));
    }

    public function testUpdate()
    {
        $this->Todolist->update(new ParamsProcessor(array('id' => 1, 'template' => 'write more unit tests')));
    }

    public function testUpdateOrdering()
    {
        $body = 'write more tests';
        $this->Todolist->create(new ParamsProcessor(array('template' => $body)));
        $this->Todolist->create(new ParamsProcessor(array('template' => $body)));
        $ordering = array('todoItem_3', 'todoItem_2', 'todoItem_4');
        $OrderingParams = new OrderingParams('todolist', $ordering);
        $this->Todolist->updateOrdering($OrderingParams);
    }

    public function testDestroy()
    {
        $this->Todolist->destroy(1);
    }
}
