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
use Elabftw\Models\Users\Users;

class BatchTest extends \PHPUnit\Framework\TestCase
{
    private Batch $Batch;

    private array $baseReqBody;

    protected function setUp(): void
    {
        $this->Batch = new Batch(new Users(1, 1));
        // Default values for $reqBody
        $this->baseReqBody = array(
            'action' => Action::Create->value,
            'items_tags' => array(),
            'items_types' => array(),
            'items_status' => array(),
            'experiments_categories' => array(),
            'experiments_status' => array(),
            'experiments_tags' => array(),
            'users' => array(),
            // Only used if Action::UpdateOwner
            'target_owner' => null,
        );
    }

    public function testPostAction(): void
    {
        $reqBody = $this->baseReqBody;
        $reqBody['action'] = Action::ForceUnlock->value;
        $reqBody['items_tags'] = array(1, 2);
        $reqBody['items_types'] = array(1, 2);
        $reqBody['items_status'] = array(1, 2);
        $reqBody['experiments_categories'] = array(1, 2);
        $reqBody['experiments_status'] = array(1, 2);
        $reqBody['experiments_tags'] = array(1, 2);
        $reqBody['users'] = array(1, 2);
        $this->assertIsInt($this->Batch->postAction(Action::Create, $reqBody));
    }

    public function testPostActionWithOwnershipUpdate(): void
    {
        $reqBody = $this->baseReqBody;
        $reqBody['action'] = Action::UpdateOwner->value;
        $reqBody['target_owner'] = 2;
        $this->assertIsInt($this->Batch->postAction(Action::UpdateOwner, $reqBody));
    }

    public function testInvalidPostAction(): void
    {
        $reqBody = $this->baseReqBody;
        $reqBody['action'] = Action::UpdateOwner->value;
        $reqBody['users'] = array(1, 2);
        // On batch, cannot update owner action without 'target_owner'
        $this->expectException(ImproperActionException::class);
        $this->Batch->postAction(Action::UpdateOwner, $reqBody);
    }

    // test Unarchive & Restore methods
    public function testSpecialActions(): void
    {
        $reqBody = $this->baseReqBody;
        // unarchive
        $reqBody['action'] = Action::Unarchive->value;
        $reqBody['target_owner'] = 3;
        $this->assertIsInt($this->Batch->postAction(Action::Unarchive, $reqBody));
        // restore
        $reqBody['action'] = Action::Destroy->value;
        $reqBody['target_owner'] = 3;
        $this->assertIsInt($this->Batch->postAction(Action::Destroy, $reqBody));
        $reqBody['action'] = Action::Restore->value;
        $this->assertIsInt($this->Batch->postAction(Action::Restore, $reqBody));
    }

    public function testGetApiPath(): void
    {
        $this->assertEquals('api/v2/batch/', $this->Batch->getApiPath());
    }
}
