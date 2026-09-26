<?php

declare(strict_types=1);

namespace Elabftw\Models;

use Elabftw\Enums\Action;
use Elabftw\Traits\TestsUtilsTrait;
use PHPUnit\Framework\TestCase;

final class StepGroupsTest extends TestCase
{
    use TestsUtilsTrait;

    private Experiments $Experiments;

    private StepGroups $StepGroups;

    private Steps $Steps;

    protected function setUp(): void
    {
        $this->Experiments = $this->getFreshExperiment();
        $this->StepGroups = new StepGroups($this->Experiments);
        $this->Steps = new Steps($this->Experiments);
    }

    public function testCreateRenameAssignAndDestroy(): void
    {
        $groupId = $this->StepGroups->postAction(Action::Create, array('title' => 'Preparation'));
        $stepId = $this->Steps->postAction(Action::Create, array('body' => 'Prepare sample', 'group_id' => $groupId));

        $step = new Steps($this->Experiments, $stepId)->readOne();
        $this->assertSame($groupId, (int) $step['group_id']);

        $group = new StepGroups($this->Experiments, $groupId);
        $updated = $group->patch(Action::Update, array('title' => 'Sample preparation'));
        $this->assertSame('Sample preparation', $updated['title']);

        $this->assertTrue($group->destroy());
        $step = new Steps($this->Experiments, $stepId)->readOne();
        $this->assertNull($step['group_id']);
    }

    public function testReorderGroupsAndMoveSteps(): void
    {
        $firstGroup = $this->StepGroups->postAction(Action::Create, array('title' => 'First'));
        $secondGroup = $this->StepGroups->postAction(Action::Create, array('title' => 'Second'));
        $firstStep = $this->Steps->postAction(Action::Create, array('body' => 'first step'));
        $secondStep = $this->Steps->postAction(Action::Create, array('body' => 'second step'));

        $this->Steps->patch(Action::Update, array('grouped_ordering' => array(
            array('group_id' => $firstGroup, 'step_ids' => array($firstStep)),
            array('group_id' => $secondGroup, 'step_ids' => array($secondStep)),
            array('group_id' => null, 'step_ids' => array()),
        )));

        $steps = $this->Steps->readAll();
        $this->assertSame($firstStep, (int) $steps[0]['id']);
        $this->assertSame($secondStep, (int) $steps[1]['id']);

        $this->StepGroups->patch(Action::Update, array('ordering' => array($secondGroup, $firstGroup)));
        $steps = $this->Steps->readAll();
        $this->assertSame($secondStep, (int) $steps[0]['id']);
        $this->assertSame($firstStep, (int) $steps[1]['id']);
    }

    public function testGroupsAreDuplicatedWithTemplateSteps(): void
    {
        $Template = $this->getFreshTemplate();
        $TemplateGroups = new StepGroups($Template);
        $groupId = $TemplateGroups->postAction(Action::Create, array('title' => 'Acquisition'));
        new Steps($Template)->postAction(Action::Create, array('body' => 'Acquire image', 'group_id' => $groupId));

        new Steps($Template)->duplicate($this->Experiments, $Template->id, $this->Experiments->id);

        $groups = new StepGroups($this->Experiments)->readAll();
        $steps = new Steps($this->Experiments)->readAll();
        $this->assertCount(1, $groups);
        $this->assertSame('Acquisition', $groups[0]['title']);
        $this->assertSame((int) $groups[0]['id'], (int) $steps[0]['group_id']);
    }
}
