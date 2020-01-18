<?php declare(strict_types=1);
/**
 * @author Nicolas CARPi <nicolas.carpi@curie.fr>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

namespace Elabftw\Models;

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
        $this->Experiments->Tags->create('my tag');
        $id = $this->Experiments->Tags->create('new tag');
        $this->assertTrue((bool) Check::id($id));

        $Database = new Database($this->Users, 1);
        $Tags = new Tags($Database);
        $id =$Tags->create('tag2222');
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
        $this->Experiments->Tags->update('my tag', 'new tag');
    }

    public function testDeduplicate()
    {
        $this->assertEquals(0, $this->Experiments->Tags->deduplicate('notduptag'));
        $this->assertEquals(1, $this->Experiments->Tags->deduplicate('new tag'));
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
        $id = $this->Experiments->Tags->create('destroy me');
        $this->Experiments->Tags->destroy($id);
    }
}
