<?php declare(strict_types=1);
/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

namespace Elabftw\Models;

use Elabftw\Elabftw\ParamsProcessor;
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
        $this->Experiments->Tags->create(new ParamsProcessor(array('tag' => 'my tag')));
        $id = $this->Experiments->Tags->create(new ParamsProcessor(array('tag' => 'new tag')));
        $this->assertTrue((bool) Check::id($id));

        $Database = new Database($this->Users, 1);
        $Tags = new Tags($Database);
        $id =$Tags->create(new ParamsProcessor(array('tag' => 'tag2222')));
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
        $this->assertEquals('new super tag', $this->Experiments->Tags->update(new ParamsProcessor(array('id' => 1, 'tag' => 'new super tag'))));
    }

    public function testDeduplicate()
    {
        $this->assertEquals(0, $this->Experiments->Tags->deduplicate());
        $this->Experiments->Tags->create(new ParamsProcessor(array('tag' => 'correcttag')));
        $id = $this->Experiments->Tags->create(new ParamsProcessor(array('tag' => 'typotag')));
        $this->Experiments->Tags->update(new ParamsProcessor(array('id' => $id, 'tag' => 'correcttag')));
        $this->assertEquals(1, $this->Experiments->Tags->deduplicate());
    }

    public function testUnreference()
    {
        $this->Experiments->Tags->unreference(1);
    }

    public function testGetList()
    {
        $res = $this->Experiments->Tags->getList('tag2');
        $this->assertEquals('tag2222', $res[0]);
    }

    public function testDestroy()
    {
        $id = $this->Experiments->Tags->create(new ParamsProcessor(array('tag' => 'destroy me')));
        $this->Experiments->Tags->destroy($id);
    }
}
