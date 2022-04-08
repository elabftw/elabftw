<?php declare(strict_types=1);
/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

namespace Elabftw\Models;

use Elabftw\Elabftw\ContentParams;
use Elabftw\Elabftw\StepParams;

class StepsTest extends \PHPUnit\Framework\TestCase
{
    private Experiments $Experiments;

    private Steps $Steps;

    protected function setUp(): void
    {
        $this->Experiments = new Experiments(new Users(1, 1), 1);
        $this->Steps = $this->Experiments->Steps;
    }

    public function testCreate(): void
    {
        $this->Steps->create(new StepParams('do this'));
    }

    public function testFinish(): void
    {
        $this->Steps->update(new StepParams('', 'finished'));
    }

    public function testRead(): void
    {
        $this->assertIsArray($this->Steps->read(new ContentParams()));
    }

    public function testUpdate(): void
    {
        $id = $this->Steps->create(new StepParams('do that'));
        $Steps = new Steps($this->Experiments, $id);
        $Steps->update(new StepParams('updated step body', 'body'));
        $ourStep = array_filter($this->Steps->read(new ContentParams()), function ($s) use ($id) {
            return ((int) $s['id']) === $id;
        });
        $this->assertEquals(array_pop($ourStep)['body'], 'updated step body');
        // update deadline
        $Steps->update(new StepParams('2022-03-23 13:37:00', 'deadline'));
        $Steps->update(new StepParams('', 'deadline_notif'));
        // update finish time_time
        $Steps->update(new StepParams('2022-03-23 13:37:00', 'finished_time'));
    }

    public function testDestroy(): void
    {
        $Steps = new Steps($this->Experiments, 1);
        $this->assertTrue($Steps->destroy());
    }
}
