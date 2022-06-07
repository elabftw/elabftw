<?php declare(strict_types=1);

namespace Elabftw\Models;

use Elabftw\Elabftw\ContentParams;
use Elabftw\Elabftw\OrderingParams;

class TodolistTest extends \PHPUnit\Framework\TestCase
{
    private Todolist $Todolist;

    protected function setUp(): void
    {
        $this->Todolist = new Todolist(1);
    }

    public function testCreate(): void
    {
        $content = 'write more tests';
        $this->assertIsInt($this->Todolist->create(new ContentParams($content)));
    }

    public function testRead(): void
    {
        $this->assertIsArray($this->Todolist->readAll());
    }

    public function testUpdate(): void
    {
        $this->Todolist->setId(1);
        $this->assertTrue($this->Todolist->update(new ContentParams('write way more tests')));
    }

    public function testUpdateOrdering(): void
    {
        $content = 'write more tests';
        $this->Todolist->create(new ContentParams($content));
        $this->Todolist->create(new ContentParams($content));
        $ordering = array('todoItem_3', 'todoItem_2', 'todoItem_4');
        $OrderingParams = new OrderingParams('todolist', $ordering);
        $this->Todolist->updateOrdering($OrderingParams);
    }

    public function testDestroy(): void
    {
        $this->Todolist->setId(1);
        $this->assertTrue($this->Todolist->destroy());
    }
}
