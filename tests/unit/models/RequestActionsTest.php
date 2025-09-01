<?php

declare(strict_types=1);
/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @author Marcel Bolten github@marcelbolten.de
 * @copyright 2024 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

namespace Elabftw\Models;

use Elabftw\Enums\Action;
use Elabftw\Enums\RequestableAction;
use Elabftw\Exceptions\ImproperActionException;
use Elabftw\Models\Users\Users;
use Elabftw\Traits\TestsUtilsTrait;

class RequestActionsTest extends \PHPUnit\Framework\TestCase
{
    use TestsUtilsTrait;

    private Experiments $Experiments;

    private RequestActions $RequestActions;

    protected function setUp(): void
    {
        $Users = new Users(1, 1);
        $this->Experiments = $this->getFreshExperiment();
        $this->RequestActions = new RequestActions($Users, $this->Experiments);
    }

    public function testPostAction(): void
    {
        $targetUser = $this->getUserInTeam(2);
        $reqBody = array(
            'target_userid' => $targetUser->userid,
            'target_action' => RequestableAction::Archive->value,
        );
        $newRequestActionId = $this->RequestActions->postAction(
            Action::Create, // this action is irrelevant
            $reqBody,
        );
        $this->assertIsInt($newRequestActionId);
        // request the same again to trigger rejection
        $this->expectException(ImproperActionException::class);
        $this->RequestActions->postAction(
            Action::Create, // this action is irrelevant
            $reqBody,
        );
    }

    public function testPatch(): void
    {
        $this->expectException(ImproperActionException::class);
        $this->RequestActions->patch(Action::Create, array());
    }

    public function testReadAllFull(): void
    {
        $reqBody = array(
            'target_userid' => 2,
            'target_action' => RequestableAction::Archive->value,
        );
        $this->RequestActions->postAction(
            Action::Create,
            $reqBody,
        );
        $this->assertIsArray($this->RequestActions->readAllFull());
    }

    public function testRemove(): void
    {
        $targetUser = $this->getUserInTeam(2);
        $reqBody = array(
            'target_userid' => $targetUser->userid,
            'target_action' => RequestableAction::Archive->value,
        );
        $this->RequestActions->postAction(
            Action::Create,
            $reqBody,
        );
        $this->assertCount(1, $this->RequestActions->readAll());
        // do the action
        $RequestActions = new RequestActions($targetUser, $this->Experiments);
        $RequestActions->remove(RequestableAction::Archive);
        $this->assertEmpty($this->RequestActions->readAll());
    }

    public function testReadOne(): void
    {
        $this->RequestActions->setId(1);
        $this->assertIsArray($this->RequestActions->readOne());
    }

    public function testGetApiPath(): void
    {
        $this->assertEquals(sprintf('api/v2/experiments/%d/request_actions/', $this->Experiments->id), $this->RequestActions->getApiPath());
    }

    public function testDestroy(): void
    {
        $this->assertTrue($this->RequestActions->destroy());
    }
}
