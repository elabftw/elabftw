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
use Elabftw\Exceptions\IllegalActionException;
use Elabftw\Traits\TestsUtilsTrait;

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

    public function testNoAdmin(): void
    {
        $Users = new Users(2, 1);
        $TeamTags = new TeamTags($Users);
        $this->expectException(IllegalActionException::class);
        $TeamTags->patch(Action::UpdateTag, array());
    }

    public function testNoAdminDestroy(): void
    {
        $Users = new Users(2, 1);
        $TeamTags = new TeamTags($Users);
        $this->expectException(IllegalActionException::class);
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

    public function testUpdateTag(): void
    {
        $id = $this->Tags->postAction(Action::Create, array('tag' => 'sometag!!'));
        $this->TeamTags->setId($id);
        $this->assertIsArray($this->TeamTags->patch(Action::UpdateTag, array('tag' => 'newcontent')));
        $tag = $this->TeamTags->readOne();
        $this->assertEquals('newcontent', $tag['tag']);
    }

    public function testDestroy(): void
    {
        $this->assertTrue($this->TeamTags->destroy());
    }
}
