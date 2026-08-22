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
use Elabftw\Exceptions\ForbiddenException;
use Elabftw\Exceptions\ImproperActionException;
use Elabftw\Exceptions\ResourceNotFoundException;
use Elabftw\Models\Users\AuthenticatedUser;
use Elabftw\Params\OrderingParams;
use Elabftw\Traits\TestsUtilsTrait;

use function array_filter;
use function array_values;
use function count;

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
        $this->Steps = new Steps($this->Experiments);
    }

    public function testCreateAndFinish(): void
    {
        $id = $this->Steps->postAction(Action::Create, array('body' => 'do this'));
        $this->assertIsInt($id);
        $this->Steps->setId($id);
        $step = $this->Steps->patch(Action::Finish, array());
        $this->assertEquals(1, $step['finished']);
    }

    public function testReadAll(): void
    {
        $this->assertIsArray($this->Steps->readAll());
    }

    public function testReadOne(): void
    {
        $body = 'do this';
        $id = $this->Steps->postAction(Action::Create, array('body' => $body));
        $this->assertIsInt($id);
        $this->Steps->setId($id);
        $result = $this->Steps->readOne();
        $this->assertEquals($result['body'], $body);
    }

    public function testCannotReadOneFromAnotherExperiment(): void
    {
        $id = $this->Steps->postAction(Action::Create, array('body' => 'do this'));
        $OtherExperiments = $this->getFreshExperiment();
        $OtherSteps = new Steps($OtherExperiments);
        $OtherSteps->setId($id);
        $this->expectException(ResourceNotFoundException::class);
        $OtherSteps->readOne();
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

    public function testUpdateOrdering(): void
    {
        $first = $this->Steps->postAction(Action::Create, array('body' => 'first'));
        $second = $this->Steps->postAction(Action::Create, array('body' => 'second'));
        $changelogCount = count((new Changelog($this->Experiments))->readAll());

        $this->Steps->updateOrdering(new OrderingParams(array(
            'table' => 'experiments_steps',
            'ordering' => array('step_' . $second, 'step_' . $first),
        )));

        $steps = $this->Steps->readAll();
        $this->assertSame($second, (int) $steps[0]['id']);
        $this->assertSame($first, (int) $steps[1]['id']);
        $this->assertCount($changelogCount + 1, (new Changelog($this->Experiments))->readAll());
    }

    public function testCannotReorderStepFromAnotherExperiment(): void
    {
        $victimStep = $this->Steps->postAction(Action::Create, array('body' => 'victim'));
        $otherSteps = new Steps($this->getFreshExperiment());
        $params = new OrderingParams(array(
            'table' => 'experiments_steps',
            'ordering' => array('step_' . $victimStep),
        ));

        try {
            $otherSteps->updateOrdering($params);
            $this->fail('A step from another experiment was reordered.');
        } catch (ImproperActionException) {
            $this->assertSame($victimStep, (int) $this->Steps->readAll()[0]['id']);
        }
    }

    public function testCannotReorderPrivateExperimentFromAnotherTeam(): void
    {
        $victimExperiment = $this->getFreshExperimentWithGivenUser(new AuthenticatedUser(2, 1));
        $victimSteps = new Steps($victimExperiment);
        $first = $victimSteps->postAction(Action::Create, array('body' => 'first'));
        $second = $victimSteps->postAction(Action::Create, array('body' => 'second'));
        $attacker = $this->getUserInTeam(2);
        $params = new OrderingParams(array(
            'table' => 'experiments_steps',
            'ordering' => array('step_' . $second, 'step_' . $first),
        ));

        try {
            $attackerSteps = new Steps(new Experiments($attacker, $victimExperiment->id));
            $attackerSteps->updateOrdering($params);
            $this->fail('A user from another team reordered private experiment steps.');
        } catch (ForbiddenException) {
            $steps = $victimSteps->readAll();
            $this->assertSame($first, (int) $steps[0]['id']);
            $this->assertSame($second, (int) $steps[1]['id']);
        }
    }

    public function testCannotReorderImmutableExperimentStep(): void
    {
        $first = $this->Steps->postAction(Action::Create, array('body' => 'first'));
        $second = $this->Steps->postAction(Action::Create, array('body' => 'second'));
        $this->Steps->patch(Action::ForceLock, array());
        $params = new OrderingParams(array(
            'table' => 'experiments_steps',
            'ordering' => array('step_' . $second, 'step_' . $first),
        ));

        try {
            $this->Steps->updateOrdering($params);
            $this->fail('Immutable experiment steps were reordered.');
        } catch (ImproperActionException) {
            $steps = $this->Steps->readAll();
            $this->assertSame($first, (int) $steps[0]['id']);
            $this->assertSame($second, (int) $steps[1]['id']);
        }
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
        $immutableStepId = new Steps($this->Templates)->postAction(Action::Create, array('body' => 'locked from template', 'ordering' => 1));
        $templateStep = new Steps($this->Templates, $immutableStepId);
        $templateStep->patch(Action::Update, array('is_immutable' => '1'));
        // duplicate steps from template -> experiment
        new Steps($this->Templates)->duplicate($this->Experiments, $this->Templates->id, $this->Experiments->id);
        // find the copied step in the experiment
        $copied = array_values(array_filter(new Steps($this->Experiments)->readAll(), function ($step) {
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
        $id = new Steps($this->Templates)->postAction(Action::Create, array('body' => 'some immutable template step'));
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
