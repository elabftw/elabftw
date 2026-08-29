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
use Elabftw\Models\Users\Users;
use Elabftw\Params\TagParam;
use Elabftw\Traits\TestsUtilsTrait;

use function bin2hex;
use function count;
use function random_bytes;

class TeamTagsTest extends \PHPUnit\Framework\TestCase
{
    use TestsUtilsTrait;

    private Users $Users;

    private TeamTags $TeamTags;

    private Tags $Tags;

    protected function setUp(): void
    {
        $this->Users = new Users(1, 1);
        $this->TeamTags = new TeamTags($this->Users, 1);
        $this->Tags = new Tags($this->getFreshExperiment());
    }

    public function testGetApiPath(): void
    {
        $this->assertEquals('api/v2/teams/1/tags/', $this->TeamTags->getApiPath());
    }

    public function testCreate(): void
    {
        $this->assertIsInt($this->TeamTags->postAction(Action::Create, array('tag' => 'microscopy')));
    }

    public function testReadAll(): void
    {
        $this->assertIsArray($this->TeamTags->readAll());
        // TODO test with query
    }

    public function testDeletedEntitiesAreNotCounted(): void
    {
        $Experiments = $this->getFreshExperiment();
        $Tags = new Tags($Experiments);
        $tagId = $Tags->postAction(Action::Create, array('tag' => 'deleted entity count ' . bin2hex(random_bytes(8))));
        $this->TeamTags->setId($tagId);

        $this->assertSame(1, (int) $this->TeamTags->readOne()['item_count']);

        $Experiments->patch(Action::Destroy, array());

        $this->assertSame(0, (int) $this->TeamTags->readOne()['item_count']);

        foreach ($this->TeamTags->readAll() as $tag) {
            if ((int) $tag['id'] !== $tagId) {
                continue;
            }
            $this->assertSame(0, (int) $tag['item_count']);
            return;
        }
        $this->fail('Tag not found in team tags.');
    }

    public function testNoAdmin(): void
    {
        $Users = new Users(2, 1);
        $TeamTags = new TeamTags($Users);
        $this->expectException(ForbiddenException::class);
        $TeamTags->patch(Action::UpdateTag, array());
    }

    public function testNoAdminDestroy(): void
    {
        $Users = new Users(2, 1);
        $TeamTags = new TeamTags($Users);
        $this->expectException(ForbiddenException::class);
        $TeamTags->destroy();
    }

    public function testDeduplicate(): void
    {
        // we can't directly create two of the same, it needs to be edited from one with a typo first
        $this->Tags->postAction(Action::Create, array('tag' => 'duplicated'));
        $this->TeamTags->setId($this->Tags->postAction(Action::Create, array('tag' => 'duplikated')));
        $beforeCnt = count($this->TeamTags->readAll());
        $this->TeamTags->patch(Action::UpdateTag, array('tag' => 'duplicated'));
        $afterCnt = count($this->TeamTags->readAll());
        $this->assertEquals($beforeCnt - 1, $afterCnt);
    }

    public function testDeduplicateCaseSensitive(): void
    {
        $id = $this->Tags->postAction(Action::Create, array('tag' => 'CAPITAL'));
        $this->TeamTags->setId($id);
        $beforeCnt = count($this->TeamTags->readAll());
        $this->TeamTags->patch(Action::UpdateTag, array('tag' => 'capital'));
        $afterCnt = count($this->TeamTags->readAll());
        // at the end, we have the same number of tags because both have been merged
        $this->assertEquals($beforeCnt, $afterCnt);
        $tag = $this->TeamTags->readOne();
        $this->assertEquals('capital', $tag['tag']);
    }

    public function testUpdateTag(): void
    {
        $id = $this->Tags->postAction(Action::Create, array('tag' => 'sometag!!'));
        $this->TeamTags->setId($id);
        $this->assertIsArray($this->TeamTags->patch(Action::UpdateTag, array('tag' => 'newcontent')));
        $tag = $this->TeamTags->readOne();
        $this->assertEquals('newcontent', $tag['tag']);
    }

    public function testUpdateTagFromOtherTeam(): void
    {
        $attackerTag = 'cross team merge attacker';
        $victimTag = 'cross team merge victim';
        $this->TeamTags->create(new TagParam($attackerTag));

        $VictimUser = $this->getUserInTeam(2, 1);
        $VictimTags = new Tags($this->getFreshExperimentWithGivenUser($VictimUser));
        $victimTagId = $VictimTags->postAction(Action::Create, array('tag' => $victimTag));

        $this->TeamTags->setId($victimTagId);
        try {
            $this->TeamTags->patch(Action::UpdateTag, array('tag' => $attackerTag));
            $this->fail('Cross-team tag update should be rejected.');
        } catch (ForbiddenException) {
            $this->addToAssertionCount(1);
        }

        $VictimTeamTags = new TeamTags($VictimUser, $victimTagId);
        $this->assertSame($victimTag, $VictimTeamTags->readOne()['tag']);
        $this->assertSame($victimTagId, (int) $VictimTags->readAll()[0]['tag_id']);
    }

    public function testDestroy(): void
    {
        $this->assertTrue($this->TeamTags->destroy());
    }

    public function testDestroyTagFromOtherTeam(): void
    {
        $tag = 'cross team test';
        // create the tag in team 1
        $id = $this->TeamTags->create(new TagParam($tag));
        $this->TeamTags->setId($id);
        // now we try and delete it from team 2
        new TeamTags($this->getUserInTeam(2, 1), $id)->destroy();
        // tag has not been destroyed: we can still read it
        $this->assertSame($tag, $this->TeamTags->readOne()['tag']);
    }
}
