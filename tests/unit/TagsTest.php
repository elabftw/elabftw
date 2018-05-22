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
        $this->assertTrue((bool) Tools::checkId($id));

        $Database = new Database($this->Users, 1);
        $Tags = new Tags($Database);
        $id =$Tags->create('my tag');
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

    public function testGetList()
    {
        $res = $this->Experiments->Tags->getList('my');
        $this->assertEquals('my tag', $res[0]);
    }

    public function testDestroy()
    {
        $id = $this->Experiments->Tags->create('destroy me');
        $this->assertTrue($this->Experiments->Tags->destroy($id));
    }
}
