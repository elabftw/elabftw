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
use Elabftw\Traits\TestsUtilsTrait;

class StepsTest extends \PHPUnit\Framework\TestCase
{
    use TestsUtilsTrait;

    private Experiments $Experiments;

    private Steps $Steps;

    protected function setUp(): void
    {
        $this->Experiments = $this->getFreshExperiment();
        $this->Steps = $this->Experiments->Steps;
    }

    public function testCreateAndFinish(): void
    {
        $id = $this->Steps->postAction(Action::Create, array('body' => 'do this'));
        $this->assertIsInt($id);
        $this->Steps->setId($id);
        $step = $this->Steps->patch(Action::Finish, array());
        $this->assertEquals(1, $step['finished']);
    }

    public function testRead(): void
    {
        $this->assertIsArray($this->Steps->readAll());
    }

    public function testUpdate(): void
    {
        $id = $this->Steps->postAction(Action::Create, array('body' => 'some step'));
        $Steps = new Steps($this->Experiments, $id);
        $step = $Steps->patch(Action::Update, array('body' => 'updated step body'));
        $this->assertEquals('updated step body', $step['body']);
        // update deadline
        $Steps->patch(Action::Update, array('deadline' => '2022-03-23 13:37:00'));
        $Steps->patch(Action::Notif, array());
        // update finish time_time
        $Steps->patch(Action::Update, array('finished_time' => '2022-03-23 13:37:00'));
    }

    public function testDestroy(): void
    {
        $Steps = new Steps($this->Experiments, 1);
        $this->assertTrue($Steps->destroy());
    }
}
