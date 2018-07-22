<?php
namespace Elabftw\Elabftw;

class TagsTest extends \PHPUnit\Framework\TestCase
{
    protected function setUp()
    {
        $this->Users = new Users(1);
        $this->Experiments = new Experiments($this->Users, 1);
    }

    public function testCreate()
    {
        $id = $this->Experiments->Tags->create('my tag');
        $id = $this->Experiments->Tags->create('new tag');
        $this->assertTrue((bool) Tools::checkId($id));

        $Database = new Database($this->Users, 1);
        $Tags = new Tags($Database);
        $id =$Tags->create('tag2222');
        $this->assertTrue((bool) Tools::checkId($id));
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
        $this->assertTrue($this->Experiments->Tags->update('my tag', 'new tag'));
    }
    public function testDeduplicate()
    {
        $this->assertEquals(0, $this->Experiments->Tags->deduplicate('notduptag'));
        $this->assertEquals(1, $this->Experiments->Tags->deduplicate('new tag'));
    }
    public function testUnreference()
    {
        $this->assertTrue($this->Experiments->Tags->unreference(1));
    }

    public function testGetList()
    {
        $res = $this->Experiments->Tags->getList('tag2');
        $this->assertEquals('tag2222', $res[0]);
    }

    public function testDestroy()
    {
        $id = $this->Experiments->Tags->create('destroy me');
        $this->assertTrue($this->Experiments->Tags->destroy($id));
    }
}
