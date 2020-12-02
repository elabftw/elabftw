<?php declare(strict_types=1);
/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

namespace Elabftw\Models;

use Elabftw\Elabftw\ParamsProcessor;

class RevisionsTest extends \PHPUnit\Framework\TestCase
{
    protected function setUp(): void
    {
        $this->Users = new Users(1, 1);
        $this->Experiments = new Experiments($this->Users, 1);
        $this->Revisions = new Revisions($this->Experiments);
    }

    public function testCreate()
    {
        $this->Revisions->create('Ohaiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiii');
    }

    public function testReadAll()
    {
        $this->assertTrue(is_array($this->Revisions->readAll()));
    }

    public function testReadCount()
    {
        $this->assertIsInt($this->Revisions->readCount());
        $this->Revisions = new Revisions(new Database($this->Users, 1));
        $this->assertIsInt($this->Revisions->readCount());
    }

    public function testRestore()
    {
        $this->Experiment = new Experiments($this->Users, 1);
        $new = $this->Experiment->create(new ParamsProcessor(array('id' => 0)));
        $this->Experiment->setId($new);
        $this->Revisions = new Revisions($this->Experiment);
        $this->Revisions->create('Ohai');
        $this->Revisions->restore($new);
        //$this->Experiments->toggleLock();
        //$this->expectException(\Exception::class);
        //$this->Revisions->restore(2);
    }

    public function testDestroy()
    {
        $this->Revisions->destroy(1);
    }
}
