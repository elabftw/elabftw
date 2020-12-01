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
        $this->Steps->create(new ParamsProcessor(array('template' => 'do this')));
    }

    public function testFinish()
    {
        $this->Steps->finish(1);
    }

    public function testRead()
    {
        $steps = $this->Steps->read();
        $this->assertTrue(is_array($steps));
    }

    public function testUpdate()
    {
        $id = $this->Steps->create(new ParamsProcessor(array('template' => 'do that')));
        $this->Steps->update(new ParamsProcessor(array('id' => $id, 'template' => 'updated step body')));
        $ourStep = array_filter($this->Steps->read(), function ($s) use ($id) {
            return ((int) $s['id']) === $id;
        });
        $this->assertEquals(array_pop($ourStep)['body'], 'updated step body');
    }

    public function testDestroy()
    {
        $this->assertTrue($this->Steps->destroy(1));
    }
}
