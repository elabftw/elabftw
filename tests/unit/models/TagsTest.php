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
use Elabftw\Models\Users\Users;
use Elabftw\Traits\TestsUtilsTrait;

class TagsTest extends \PHPUnit\Framework\TestCase
{
    use TestsUtilsTrait;

    private Users $Users;

    private Experiments $Experiments;

    protected function setUp(): void
    {
        $this->Users = $this->getRandomUserInTeam(1, admin: 1);
        $this->Experiments = $this->getFreshExperimentWithGivenUser($this->Users);
    }

    public function testGetApiPath(): void
    {
        $this->assertEquals(sprintf('api/v2/experiments/%d/tags/', $this->Experiments->id), $this->Experiments->Tags->getApiPath());
    }

    public function testCreate(): void
    {
        $this->Experiments->Tags->postAction(Action::Create, array('tag' => 'my tag'));
        $id = $this->Experiments->Tags->postAction(Action::Create, array('tag' => 'new tag'));
        $this->assertIsInt($id);
        // multi tags
        $id = $this->Experiments->Tags->postAction(Action::Create, array('tags' => array('tag A', 'tag B')));
        $this->assertIsInt($id);

        // no admin user
        $user = $this->getRandomUserInTeam(1);
        $Items = $this->getFreshItemWithGivenUser($user);
        $Tags = new Tags($Items);
        $id = $Tags->postAction(Action::Create, array('tag' => 'tag2222'));
        $this->assertIsInt($id);
        // now with no rights
        $Teams = new Teams($this->Users, $this->Users->userData['team']);
        $Teams->patch(Action::Update, array('user_create_tag' => 0));
        $this->expectException(ImproperActionException::class);
        $Tags->postAction(Action::Create, array('tag' => 'tag2i222'));
    }

    public function testReadAll(): void
    {
        $this->assertIsArray($this->Experiments->Tags->readAll());
        $id = $this->Experiments->Tags->postAction(Action::Create, array('tag' => 'new tag'));
        $this->Experiments->Tags->setId($id);
        $this->assertIsArray($this->Experiments->Tags->readOne());
        $Items = $this->getFreshItemWithGivenUser($this->Users);
        $Tags = new Tags($Items);
        $this->assertIsArray($Tags->readAll());
    }

    public function testCopyTags(): void
    {
        $id = $this->Experiments->postAction(Action::Create, array());
        $this->Experiments->Tags->copyTags($id, true);
        $newExperiments = new Experiments($this->Users, $id);
        $this->assertEquals($this->Experiments->readOne()['tags'], $newExperiments->entityData['tags']);
    }

    public function testUnreference(): void
    {
        $id = $this->Experiments->Tags->postAction(Action::Create, array('tag' => 'blahblahblah'));
        $Tags = new Tags($this->Experiments, $id);
        $Tags->patch(Action::Unreference, array());
        $this->expectException(ImproperActionException::class);
        $Tags->patch(Action::Timestamp, array());
    }

    public function testDestroy(): void
    {
        $this->assertTrue($this->Experiments->Tags->destroy());
    }
}
