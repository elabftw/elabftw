<?php

declare(strict_types=1);
/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2022 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

namespace Elabftw\Models;

use Elabftw\Elabftw\OrderingParams;
use Elabftw\Enums\Action;

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
        $this->assertIsInt($this->Todolist->postAction(Action::Create, array('content' => $content)));
    }

    public function testRead(): void
    {
        $this->assertIsArray($this->Todolist->readAll());
    }

    public function testUpdate(): void
    {
        $this->Todolist->setId(1);
        $this->assertIsArray($this->Todolist->patch(Action::Update, array('content' => 'write way more tests')));
    }

    public function testUpdateOrdering(): void
    {
        $this->Todolist->postAction(Action::Create, array('content' => 'item 2'));
        $this->Todolist->postAction(Action::Create, array('content' => 'item 3'));
        $this->Todolist->postAction(Action::Create, array('content' => 'item 4'));
        $OrderingParams = new OrderingParams(array('ordering' => array('test_3', 'test_2', 'test_1'), 'table' => 'todolist'));
        $this->Todolist->updateOrdering($OrderingParams);
        $all = $this->Todolist->readAll();
        $this->assertEquals('item 4', $all[0]['body']);
    }

    public function testDestroy(): void
    {
        $this->assertTrue($this->Todolist->destroy());
    }
}
