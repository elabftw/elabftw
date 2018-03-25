<?php
namespace Elabftw\Elabftw;

class ApiTest extends \PHPUnit\Framework\TestCase
{
    protected function setUp()
    {
        $this->Users = new Users(1);
        $this->Users->generateApiKey();
    }

    public function testCreateExperiment()
    {
        $Api = new Api(new Experiments($this->Users, null));
        $content = $Api->createExperiment();
        $this->assertTrue((bool) Tools::checkId($content['id']));
    }

    public function testGetEntity()
    {
        $Entity = new Experiments($this->Users);
        $id = $Entity->create();
        $Entity->setId($id);
        $Api = new Api($Entity);
        $content = $Api->getEntity();
        $this->assertEquals('Untitled', $content['title']);
    }

    public function testUpdateEntity()
    {
        $Entity = new Experiments($this->Users);
        $id = $Entity->create();
        $Entity->setId($id);
        $Api = new Api($Entity);
        $content = $Api->updateEntity('New title', '20170817', 'New body');
        $this->assertEquals('success', $content['result']);
        // update an entity without write access
        /* TODO
        $Entity = new Experiments(new Users(2), $id);
        $Api = new Api($Entity);
        $content = $Api->updateEntity('New title', '20170817', 'New body');
        $this->assertEquals('Error', $content[1]);
         */
    }
}
