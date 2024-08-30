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

class RequestActionsTest extends \PHPUnit\Framework\TestCase
{
    private Experiments $Experiments;

    private RequestActions $RequestActions;

    protected function setUp(): void
    {
        $Users = new Users(1, 1);
        $this->Experiments = new Experiments($Users, 1);
        $this->RequestActions = new RequestActions($Users, $this->Experiments);
    }

    public function testPostAction(): void
    {
        $reqBody = array(
            'target_userid' => 2,
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
        $this->assertIsArray($this->RequestActions->readAllFull());
    }

    public function testReadOne(): void
    {
        $this->RequestActions->setId(1);
        $this->assertIsArray($this->RequestActions->readOne());
    }

    public function testGetApiPath(): void
    {
        $this->assertEquals('api/v2/experiments/1/request_actions/', $this->RequestActions->getApiPath());
    }

    public function testDestroy(): void
    {
        $this->assertTrue($this->RequestActions->destroy());
    }
}
