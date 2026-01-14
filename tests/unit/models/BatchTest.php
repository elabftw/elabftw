<?php

declare(strict_types=1);

/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2024 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

namespace Elabftw\Models;

use Elabftw\Enums\Action;
use Elabftw\Exceptions\ImproperActionException;
use Elabftw\Exceptions\MissingRequiredKeyException;
use Elabftw\Exceptions\UnauthorizedException;
use Elabftw\Models\Users\Users;
use Elabftw\Traits\TestsUtilsTrait;

class BatchTest extends \PHPUnit\Framework\TestCase
{
    use TestsUtilsTrait;

    private Batch $Batch;

    private array $baseReqBody;

    protected function setUp(): void
    {
        $this->Batch = new Batch(new Users(1, 1));
        // Init parameters for batch actions
        $this->baseReqBody = array(
            'action' => Action::Create->value,
            'items_tags' => array(),
            'items_categories' => array(),
            'items_status' => array(),
            'experiments_categories' => array(),
            'experiments_status' => array(),
            'experiments_tags' => array(),
            'users_experiments' => array(),
            'users_resources' => array(),
            'team' => null,
            'userid' => null,
        );
    }

    protected function tearDown(): void
    {
        $this->baseReqBody = array();
    }

    public function testPostAction(): void
    {
        $this->baseReqBody['action'] = Action::ForceUnlock->value;
        $this->baseReqBody['items_tags'] = array(1, 2);
        $this->baseReqBody['items_types'] = array(1, 2);
        $this->baseReqBody['items_status'] = array(1, 2);
        $this->baseReqBody['experiments_categories'] = array(1, 2);
        $this->baseReqBody['experiments_status'] = array(1, 2);
        $this->baseReqBody['experiments_tags'] = array(1, 2);
        $this->baseReqBody['users_experiments'] = array(1, 2);
        $this->baseReqBody['users_resources'] = array(1, 2);
        $processed = $this->Batch->postAction(Action::UpdateOwner, $this->baseReqBody);
        $this->assertIsInt($processed);
        $this->assertGreaterThan(0, $processed);
    }

    public function testPostActionWithOwnershipUpdate(): void
    {
        // create an experiment to transfer
        $user = $this->getRandomUserInTeam(1);
        $this->getFreshExperimentWithGivenUser($user);
        $this->baseReqBody['action'] = Action::UpdateOwner->value;
        $this->baseReqBody['users_experiments'] = array($user->userid);
        $this->baseReqBody['userid'] = $user->userid;
        $this->baseReqBody['team'] = $user->team;
        $processed = $this->Batch->postAction(Action::UpdateOwner, $this->baseReqBody);
        $this->assertIsInt($processed);
        $this->assertGreaterThan(0, $processed);
    }

    public function testPostActionTransferOwnerToWrongUserTeamCombination(): void
    {
        $user = $this->getRandomUserInTeam(1);
        $this->getFreshExperimentWithGivenUser($user);
        $this->baseReqBody['action'] = Action::UpdateOwner->value;
        $this->baseReqBody['users_experiments'] = array($user->userid);
        $this->baseReqBody['userid'] = $user->userid;
        $this->baseReqBody['team'] = 99;
        $this->expectException(UnauthorizedException::class);
        $this->Batch->postAction(Action::UpdateOwner, $this->baseReqBody);
    }

    public function testInvalidPostAction(): void
    {
        $this->baseReqBody['action'] = Action::UpdateOwner->value;
        $this->baseReqBody['users_experiments'] = array(1, 2);
        $this->expectException(ImproperActionException::class);
        $this->Batch->postAction(Action::UpdateOwner, $this->baseReqBody);
    }

    public function testPostActionWithWrongOwnershipUpdate(): void
    {
        $this->baseReqBody['action'] = Action::UpdateOwner->value;
        $this->baseReqBody['userid'] = null;
        $this->expectException(MissingRequiredKeyException::class);
        $this->Batch->postAction(Action::UpdateOwner, $this->baseReqBody);
    }

    // test Unarchive & Restore methods
    public function testSpecialActions(): void
    {
        // unarchive
        $this->baseReqBody['action'] = Action::Unarchive->value;
        $this->baseReqBody['userid'] = 3;
        $this->assertIsInt($this->Batch->postAction(Action::Unarchive, $this->baseReqBody));
        // restore
        $this->baseReqBody['action'] = Action::Destroy->value;
        $this->baseReqBody['userid'] = 3;
        $this->assertIsInt($this->Batch->postAction(Action::Destroy, $this->baseReqBody));
        $this->baseReqBody['action'] = Action::Restore->value;
        $this->assertIsInt($this->Batch->postAction(Action::Restore, $this->baseReqBody));
    }

    public function testGetApiPath(): void
    {
        $this->assertEquals('api/v2/batch/', $this->Batch->getApiPath());
    }
}
