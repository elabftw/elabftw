<?php declare(strict_types=1);
/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

namespace Elabftw\Models;

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

    public function testCreate(): void
    {
        $this->assertTrue($this->Revisions->create('Ohaiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiii'));
    }

    public function testReadAll(): void
    {
        $this->assertIsArray($this->Revisions->readAll());
    }

    public function testReadCount(): void
    {
        $this->assertIsInt($this->Revisions->readCount());
        $this->Revisions = new Revisions(new Items($this->Users, 1), 10, 100, 10);
        $this->assertIsInt($this->Revisions->readCount());
    }

    public function testRestore(): void
    {
        $Experiment = new Experiments($this->Users, 1);
        $new = $Experiment->create(0);
        $Experiment->setId($new);
        $this->Revisions = new Revisions($Experiment, 10, 100, 10);
        $this->Revisions->create('Ohai');
        $this->assertTrue($this->Revisions->restore($new));
    }

    public function testDestroy(): void
    {
        $this->Revisions->setId(1);
        $this->assertTrue($this->Revisions->destroy());
    }
}
