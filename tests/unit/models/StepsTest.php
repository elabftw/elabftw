<?php declare(strict_types=1);
/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

namespace Elabftw\Models;

use Elabftw\Elabftw\CreateStep;
use Elabftw\Elabftw\DestroyParams;
use Elabftw\Elabftw\UpdateStepBody;
use Elabftw\Elabftw\UpdateStepFinished;

class StepsTest extends \PHPUnit\Framework\TestCase
{
    protected function setUp(): void
    {
        $this->Users = new Users(1);
        $this->Experiments = new Experiments($this->Users, 1);
        $this->Steps = $this->Experiments->Steps;
    }

    public function testCreate()
    {
        $this->Steps->create(new CreateStep('do this'));
    }

    public function testFinish()
    {
        $this->Steps->update(new UpdateStepFinished(1));
    }

    public function testRead()
    {
        $steps = $this->Steps->read();
        $this->assertTrue(is_array($steps));
    }

    public function testUpdate()
    {
        $id = $this->Steps->create(new CreateStep('do that'));
        $this->Steps->update(new UpdateStepBody($id, 'updated step body'));
        $ourStep = array_filter($this->Steps->read(), function ($s) use ($id) {
            return ((int) $s['id']) === $id;
        });
        $this->assertEquals(array_pop($ourStep)['body'], 'updated step body');
    }

    public function testDestroy()
    {
        $this->assertTrue($this->Steps->destroy(new DestroyParams(1)));
    }
}
