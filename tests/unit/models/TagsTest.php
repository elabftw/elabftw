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

class TagsTest extends \PHPUnit\Framework\TestCase
{
    private Users $Users;

    private Experiments $Experiments;

    protected function setUp(): void
    {
        $this->Users = new Users(1, 1);
        $this->Experiments = new Experiments($this->Users, 1);
    }

    public function testGetApiPath(): void
    {
        $this->assertEquals('api/v2/experiments/1/tags/', $this->Experiments->Tags->getApiPath());
    }

    public function testCreate(): void
    {
        $this->Experiments->Tags->postAction(Action::Create, array('tag' => 'my tag'));
        $id = $this->Experiments->Tags->postAction(Action::Create, array('tag' => 'new tag'));
        $this->assertIsInt($id);

        // no admin user
        $Users = new Users(2, 1);
        $Items = new Items($Users, 1);
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
        $this->Experiments->Tags->setId(1);
        $this->assertIsArray($this->Experiments->Tags->readOne());
        $Items = new Items($this->Users, 1);
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
