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
use Elabftw\Traits\TestsUtilsTrait;

class StepsTest extends \PHPUnit\Framework\TestCase
{
    use TestsUtilsTrait;

    private Experiments $Experiments;

    private Templates $Templates;

    private Steps $Steps;

    protected function setUp(): void
    {
        $this->Experiments = $this->getFreshExperiment();
        $this->Templates = $this->getFreshTemplate();
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

    public function testCannotPatchImmutabilityFromExperiment(): void
    {
        $id = $this->Steps->postAction(Action::Create, array('body' => 'some random step'));
        $Steps = new Steps($this->Experiments, $id);
        $this->expectException(ImproperActionException::class);
        $Steps->patch(Action::Update, array('is_immutable' => '1'));
    }

    public function testBatchPatchImmutableSteps(): void
    {
        $this->Steps->postAction(Action::Create, array('body' => 'step1'));
        $this->Steps->postAction(Action::Create, array('body' => 'step2'));
        $Copy = new Steps($this->Experiments);
        $Copy->patch(Action::ForceLock, array());
        $immutableSteps = $Copy->readAll();
        $this->assertIsArray($immutableSteps);
        foreach ($immutableSteps as $i => $step) {
            $this->assertArrayHasKey('is_immutable', $step);
            $this->assertSame(1, (int) $step['is_immutable'], "Step $i not immutable");
        }
    }

    public function testCannotPatchImmutableStepsFromExperiment(): void
    {
        // create a template step and make it immutable
        $immutableStepId = $this->Templates->Steps->postAction(Action::Create, array('body' => 'locked from template', 'ordering' => 1));
        $templateStep = new Steps($this->Templates, $immutableStepId);
        $templateStep->patch(Action::Update, array('is_immutable' => '1'));
        // duplicate steps from template -> experiment
        $this->Experiments->Steps->duplicate($this->Templates->id, $this->Experiments->id, true);
        // find the copied step in the experiment
        $copied = array_values(array_filter($this->Experiments->Steps->readAll(), function ($step) {
            return $step['body'] === 'locked from template';
        }));
        $this->assertNotEmpty($copied, 'Copied step not found in experiment after duplicate()');
        $copiedStepId = (int) $copied[0]['id'];
        // now in the experiments, editing an immutable step must fail
        $expStep = new Steps($this->Experiments, $copiedStepId);
        $this->expectException(ImproperActionException::class);
        $expStep->patch(Action::Update, array('body' => 'updated body'));
    }

    public function testImmutableDoesNotBlockUpdateOnTemplates(): void
    {
        $id = $this->Templates->Steps->postAction(Action::Create, array('body' => 'some immutable template step'));
        $Steps = new Steps($this->Templates, $id);
        // template can set immutable
        $Steps->patch(Action::Update, array('is_immutable' => '1'));
        // template can patch the steps
        $step = $Steps->patch(Action::Update, array('body' => 'updated on template'));
        $this->assertEquals('updated on template', $step['body']);
    }

    public function testDestroy(): void
    {
        $Steps = new Steps($this->Experiments, 1);
        $this->assertTrue($Steps->destroy());
    }
}
