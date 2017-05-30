<?php
namespace Elabftw\Elabftw;

use PDO;

class TeamsTest extends \PHPUnit_Framework_TestCase
{
    protected function setUp()
    {
        $this->Teams= new Teams(1);
    }

    public function testCreate()
    {
        $this->assertInternalType('int', (int) $this->Teams->create('Test team'));
    }

    public function testRead()
    {
        $this->assertTrue(is_array($this->Teams->read()));
    }

    public function testUpdate()
    {
        $post = array(
            'teamsUpdateFull' => 'true',
            'deletable_xp' => 1,
            'link_name' => 'Taggle',
            'link_href' => 'https://www.elabftw.net',
            'stampprovider' => 'http://zeitstempel.dfn.de/',
            'stampcert' => 'app/dfn-cert/pki.dfn.pem',
            'stamplogin' => '',
            'stamppass' => 'something'
        );
        $this->assertTrue($this->Teams->update($post));

        // test without stamppass
        $post = array(
            'teamsUpdateFull' => 'true',
            'deletable_xp' => 1,
            'link_name' => 'Taggle',
            'link_href' => 'https://www.elabftw.net',
            'stampprovider' => 'http://zeitstempel.dfn.de/',
            'stampcert' => 'app/dfn-cert/pki.dfn.pem',
            'stamplogin' => '',
            'stamppass' => ''
        );
        $this->assertTrue($this->Teams->update($post));

        // trigger Exception with bad file path
        $this->expectException(\Exception::class);
        $post = array(
            'teamsUpdateFull' => 'true',
            'deletable_xp' => 1,
            'link_name' => 'Taggle',
            'link_href' => 'https://www.elabftw.net',
            'stampprovider' => 'http://zeitstempel.dfn.de/',
            'stampcert' => 'blah',
            'stamplogin' => '',
            'stamppass' => ''
        );
        $this->Teams->update($post);
    }

    public function testUpdateName()
    {
        $this->assertTrue($this->Teams->updateName(1, 'New name'));
    }

    public function testDestroy()
    {
        $this->assertTrue($this->Teams->destroy(2));
        // try to destroy a team with data
        $this->assertFalse($this->Teams->destroy(1));
    }

    public function testGetAllStats()
    {
        $stats = $this->Teams->getAllStats();
        $this->assertTrue(is_array($stats));
        $this->assertEquals(1, $stats['totusers']);
    }
}
