<?php declare(strict_types=1);
/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

namespace Elabftw\Models;

use Elabftw\Elabftw\EntityParams;

class RevisionsTest extends \PHPUnit\Framework\TestCase
{
    private Users $Users;

    private Experiments $Experiments;

    private Revisions $Revisions;

    protected function setUp(): void
    {
        $this->Users = new Users(1, 1);
        $this->Experiments = new Experiments($this->Users, 1);
        $this->Revisions = new Revisions($this->Experiments, 10, 100, 10);
    }

    public function testCreate(): void
    {
        $this->Revisions->create('Ohaiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiii');
    }

    public function testReadAll(): void
    {
        $this->assertTrue(is_array($this->Revisions->readAll()));
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
        $new = $Experiment->create(new EntityParams('0'));
        $Experiment->setId($new);
        $this->Revisions = new Revisions($Experiment, 10, 100, 10);
        $this->Revisions->create('Ohai');
        $this->Revisions->restore($new);
        //$this->Experiments->toggleLock();
        //$this->expectException(\Exception::class);
        //$this->Revisions->restore(2);
    }

    public function testDestroy(): void
    {
        $this->Revisions->setId(1);
        $this->Revisions->destroy();
    }
}
