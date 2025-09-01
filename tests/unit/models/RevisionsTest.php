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
use Elabftw\Models\Users\Users;
use Elabftw\Traits\TestsUtilsTrait;

class RevisionsTest extends \PHPUnit\Framework\TestCase
{
    use TestsUtilsTrait;

    private Users $Users;

    private Experiments $Experiments;

    private Revisions $Revisions;

    protected function setUp(): void
    {
        $this->Users = $this->getRandomUserInTeam(1);
        $this->Experiments = $this->getFreshExperimentWithGivenUser($this->Users);
        $this->Revisions = new Revisions($this->Experiments, 10, 100, 10);
    }

    public function testGetApiPath(): void
    {
        $this->assertSame(sprintf('api/v2/experiments/%d/revisions/', $this->Experiments->id), $this->Revisions->getApiPath());
    }

    public function testCreate(): void
    {
        $this->assertIsInt($this->Revisions->create('Ohaiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiii'));
    }

    public function testReadAll(): void
    {
        $this->assertIsArray($this->Revisions->readAll());
    }

    public function testRestore(): void
    {
        $id = $this->Revisions->create('Ohai');
        $this->Revisions->setId($id);
        $this->assertIsArray($this->Revisions->patch(Action::Replace, array()));
    }

    public function testRestoreLocked(): void
    {
        $id = $this->Revisions->create('Ohai');
        $this->Revisions->setId($id);
        $this->Experiments->patch(Action::Lock, array());
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
