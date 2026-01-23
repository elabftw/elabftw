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

use Elabftw\Elabftw\CreateUploadFromLocalFile;
use Elabftw\Enums\Action;
use Elabftw\Enums\Storage;
use Elabftw\Exceptions\ForbiddenException;
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
        $user = $this->getRandomUserInTeam(1);
        $this->getFreshExperimentWithGivenUser($user);
        $this->baseReqBody['users_experiments'] = array($user->userid);
        $this->assertBatchProcessed(Action::ForceLock, $this->baseReqBody);
    }

    public function testPostActionWithOwnershipUpdate(): void
    {
        // create an experiment to transfer
        $User = new Users(1, 1);
        $this->getFreshExperimentWithGivenUser($User);
        $this->baseReqBody['users_experiments'] = array($User->userid);
        $this->baseReqBody['userid'] = $User->userid;
        $this->baseReqBody['team'] = $User->team;
        $this->assertBatchProcessed(Action::UpdateOwner, $this->baseReqBody);
    }

    public function testPostActionTransferOwnerToWrongUserTeamCombination(): void
    {
        $user = $this->getRandomUserInTeam(1);
        $this->getFreshExperimentWithGivenUser($user);
        $this->baseReqBody['users_experiments'] = array($user->userid);
        $this->baseReqBody['userid'] = $user->userid;
        $this->baseReqBody['team'] = 99;
        $this->expectException(UnauthorizedException::class);
        $this->Batch->postAction(Action::UpdateOwner, $this->baseReqBody);
    }

    public function testBatchActionIsRestrictedToAdmins(): void
    {
        $user = $this->getRandomUserInTeam(1);
        $user->isAdmin = false;
        $this->Batch = new Batch($user);
        $this->baseReqBody['users_experiments'] = array($user->userid);
        $this->baseReqBody['userid'] = $user->userid;
        $this->baseReqBody['team'] = $user->team;
        $this->expectException(ForbiddenException::class);
        $this->Batch->postAction(Action::UpdateOwner, $this->baseReqBody);
    }

    public function testInvalidPostAction(): void
    {
        $this->baseReqBody['users_experiments'] = array(1, 2);
        $this->expectException(ImproperActionException::class);
        $this->Batch->postAction(Action::UpdateOwner, $this->baseReqBody);
    }

    public function testPostActionWithWrongOwnershipUpdate(): void
    {
        $this->baseReqBody['userid'] = null;
        $this->expectException(MissingRequiredKeyException::class);
        $this->Batch->postAction(Action::UpdateOwner, $this->baseReqBody);
    }

    // test Unarchive & Restore methods
    public function testSpecialActions(): void
    {
        $user = $this->getRandomUserInTeam(1);
        $exp = $this->getFreshExperimentWithGivenUser($user);
        // unarchive. First, archive the experiment
        $exp->patch(Action::Archive, array());
        $this->baseReqBody['users_experiments'] = array($user->userid);
        $this->assertBatchProcessed(Action::Unarchive, $this->baseReqBody);
        // restore. First, delete the experiment
        $exp->destroy();
        $this->baseReqBody['users_experiments'] = array($user->userid);
        $this->assertBatchProcessed(Action::Restore, $this->baseReqBody);
    }

    public function testBatchOwnershipTransferAlsoTransfersUploads(): void
    {
        $User1 = new Users(1, 1);
        $User2 = new Users(2, 1);
        $Experiment = $this->getFreshExperimentWithGivenUser($User1);
        // new upload
        $fixturesFs = Storage::FIXTURES->getStorage();
        $uploadPath = $fixturesFs->getPath() . '/example.png';
        $uploadId = $Experiment->Uploads->create(new CreateUploadFromLocalFile('example.png', $uploadPath, immutable: 0));
        $this->assertIsInt($uploadId);
        // Initial upload owner should match experiment owner
        $Upload = new Uploads($Experiment, $uploadId);
        $this->assertEquals($User1->userid, $Upload->uploadData['userid']);
        // batch transfer user 1's experiments to user 2
        $this->baseReqBody['users_experiments'] = array($User1->userid);
        $this->baseReqBody['userid'] = $User2->userid;
        $this->baseReqBody['team'] = $User1->team;
        $this->assertBatchProcessed(Action::UpdateOwner, $this->baseReqBody);
        // reload exp & upload
        $Experiment->setId($Experiment->id);
        $this->assertEquals($User2->userid, $Experiment->entityData['userid']);
        $UploadAfter = new Uploads($Experiment, $uploadId);
        $this->assertEquals($User2->userid, $UploadAfter->uploadData['userid']);
    }

    public function testGetApiPath(): void
    {
        $this->assertEquals('api/v2/batch/', $this->Batch->getApiPath());
    }

    // assert Batch action processes more than one entity. Tests must reflect useful situations
    protected function assertBatchProcessed(Action $action, array $reqBody): void
    {
        $processed = $this->Batch->postAction($action, $reqBody);
        $this->assertIsInt($processed);
        $this->assertGreaterThan(
            0,
            $processed,
            sprintf('Expected batch action %s to process at least one entity. Processed: %d', $action->value, $processed)
        );
    }
}
