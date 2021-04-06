<?php declare(strict_types=1);
/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

namespace Elabftw\Models;

use Elabftw\Elabftw\CreateTag;
use Elabftw\Elabftw\UpdateTag;
use Elabftw\Services\Check;

class TagsTest extends \PHPUnit\Framework\TestCase
{
    protected function setUp(): void
    {
        $this->Users = new Users(1, 1);
        $this->Experiments = new Experiments($this->Users, 1);
    }

    public function testCreate()
    {
        $this->Experiments->Tags->create(new CreateTag('my tag'));
        $id = $this->Experiments->Tags->create(new CreateTag('new tag'));
        $this->assertTrue((bool) Check::id($id));

        $Database = new Database($this->Users, 1);
        $Tags = new Tags($Database);
        $id =$Tags->create(new CreateTag('tag2222'));
        $this->assertTrue((bool) Check::id($id));
    }

    public function testReadAll()
    {
        $this->assertTrue(is_array($this->Experiments->Tags->readAll()));
        $res = $this->Experiments->Tags->readAll('my');
        $this->assertEquals('my tag', $res[0]['tag']);

        $Database = new Database($this->Users, 1);
        $Tags = new Tags($Database);
        $this->assertTrue(is_array($Tags->readAll()));
    }

    public function testUpdate()
    {
        $Tags = new Tags($this->Experiments, 1);
        $this->assertTrue($Tags->update(new UpdateTag('new super tag')));
    }

    public function testDeduplicate()
    {
        $Tags = new Tags($this->Experiments, 1);
        $this->assertEquals(0, $Tags->deduplicate());
        $this->Experiments->Tags->create(new CreateTag('correcttag'));
        $id = $this->Experiments->Tags->create(new CreateTag('typotag'));
        $Tags = new Tags($this->Experiments, $id);
        $Tags->update(new UpdateTag('correcttag'));
        $this->assertEquals(1, $Tags->deduplicate());
    }

    public function testUnreference()
    {
        $Tags = new Tags($this->Experiments, 1);
        $this->Experiments->Tags->unreference();
    }

    public function testGetList()
    {
        $res = $this->Experiments->Tags->getList('tag2');
        $this->assertEquals('tag2222', $res[0]);
    }

    public function testDestroy()
    {
        $id = $this->Experiments->Tags->create(new CreateTag('destroy me'));
        $Tags = new Tags($this->Experiments, $id);
        $this->Experiments->Tags->destroy();
    }
}
