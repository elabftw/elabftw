<?php declare(strict_types=1);
/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

namespace Elabftw\Models;

use Elabftw\Elabftw\ContentParams;
use Elabftw\Elabftw\TagParams;
use Elabftw\Services\Check;

class TagsTest extends \PHPUnit\Framework\TestCase
{
    private Users $Users;

    private Experiments $Experiments;

    protected function setUp(): void
    {
        $this->Users = new Users(1, 1);
        $this->Experiments = new Experiments($this->Users, 1);
    }

    public function testCreate(): void
    {
        $this->Experiments->Tags->create(new TagParams('my tag'));
        $id = $this->Experiments->Tags->create(new TagParams('new tag'));
        $this->assertTrue((bool) Check::id($id));

        $Items = new Items($this->Users, 1);
        $Tags = new Tags($Items);
        $id =$Tags->create(new TagParams('tag2222'));
        $this->assertTrue((bool) Check::id($id));
    }

    public function testReadAll(): void
    {
        $this->assertTrue(is_array($this->Experiments->Tags->readAll()));
        $res = $this->Experiments->Tags->readAll('my');
        $this->assertEquals('my tag', $res[0]['tag']);

        $Items = new Items($this->Users, 1);
        $Tags = new Tags($Items);
        $this->assertTrue(is_array($Tags->readAll()));
    }

    public function testUpdate(): void
    {
        $Tags = new Tags($this->Experiments, 1);
        $this->assertTrue($Tags->update(new TagParams('new super tag')));
    }

    public function testDeduplicate(): void
    {
        $Tags = new Tags($this->Experiments, 1);
        $this->assertEquals(0, $Tags->deduplicate());
        $this->Experiments->Tags->create(new TagParams('correcttag'));
        $id = $this->Experiments->Tags->create(new TagParams('typotag'));
        $Tags = new Tags($this->Experiments, $id);
        $Tags->update(new TagParams('correcttag'));
        $this->assertEquals(1, $Tags->deduplicate());
    }

    public function testUnreference(): void
    {
        $Tags = new Tags($this->Experiments, 1);
        $Tags->update(new TagParams('', 'unreference'));
    }

    public function testGetList(): void
    {
        $res = $this->Experiments->Tags->read(new ContentParams('tag2', 'list'));
        $this->assertEquals('tag2222', $res[0]);
    }

    public function testDestroy(): void
    {
        $id = $this->Experiments->Tags->create(new TagParams('destroy me'));
        $Tags = new Tags($this->Experiments, $id);
        $Tags->destroy();
    }
}
