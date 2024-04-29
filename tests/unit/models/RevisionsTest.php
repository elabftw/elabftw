<?php

declare(strict_types=1);
/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

namespace Elabftw\Models;

use Elabftw\Enums\Action;
use Elabftw\Exceptions\ImproperActionException;

class RevisionsTest extends \PHPUnit\Framework\TestCase
{
    private Users $Users;

    private Experiments $Experiments;

    private Revisions $Revisions;

    protected function setUp(): void
    {
        $this->Users = new Users(1, 1);
        $this->Experiments = new Experiments($this->Users, 7);
        $this->Revisions = new Revisions($this->Experiments, 10, 100, 10);
    }

    public function testGetPage(): void
    {
        $this->assertSame('api/v2/experiments/7/revisions/', $this->Revisions->getPage());
    }

    public function testCreate(): void
    {
        $this->assertIsInt($this->Revisions->postAction(Action::Create, array('body' => 'Ohaiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiii')));
    }

    public function testReadAll(): void
    {
        $this->assertIsArray($this->Revisions->readAll());
    }

    public function testRestore(): void
    {
        $Experiment = new Experiments($this->Users, 1);
        $new = $Experiment->create(0);
        $Experiment->setId($new);
        $this->Revisions = new Revisions($Experiment, 10, 100, 10);
        $id = $this->Revisions->postAction(Action::Create, array('body' => 'Ohai'));
        $this->Revisions->setId($id);
        $this->assertIsArray($this->Revisions->patch(Action::Replace, array()));
    }

    public function testRestoreLocked(): void
    {
        $Experiment = new Experiments($this->Users, 1);
        $new = $Experiment->create(0);
        $Experiment->setId($new);
        $this->Revisions = new Revisions($Experiment, 10, 100, 10);
        $id = $this->Revisions->postAction(Action::Create, array('body' => 'Ohai'));
        $this->Revisions->setId($id);
        $Experiment->patch(Action::Lock, array());
        $this->expectException(ImproperActionException::class);
        $this->Revisions->patch(Action::Replace, array());
    }

    public function testPrune(): void
    {
        $this->assertEquals(0, $this->Revisions->prune());
    }

    public function testDestroy(): void
    {
        $this->expectException(ImproperActionException::class);
        $this->Revisions->destroy();
    }
}
