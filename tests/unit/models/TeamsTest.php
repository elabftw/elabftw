<?php declare(strict_types=1);

namespace Elabftw\Models;

use Elabftw\Exceptions\ImproperActionException;

class TeamsTest extends \PHPUnit\Framework\TestCase
{
    protected function setUp(): void
    {
        $Users = new Users(1);
        $this->Teams= new Teams($Users);
    }

    public function testCreate()
    {
        $this->assertIsInt($this->Teams->create('Test team'));
    }

    public function testRead()
    {
        $this->assertTrue(is_array($this->Teams->read()));
    }

    public function testUpdate()
    {
        $post = array(
            'teamsUpdateFull' => 'true',
            'deletable_xp' => '1',
            'link_name' => 'Taggle',
            'link_href' => 'https://www.elabftw.net',
            'stampprovider' => 'http://zeitstempel.dfn.de/',
            'stampcert' => 'src/dfn-cert/pki.dfn.pem',
            'stamplogin' => '',
            'public_db' => '0',
            'stamppass' => 'something',
        );
        $this->Teams->update($post);

        // test without stamppass
        $post = array(
            'teamsUpdateFull' => 'true',
            'deletable_xp' => 1,
            'link_name' => 'Taggle',
            'link_href' => 'https://www.elabftw.net',
            'stampprovider' => 'http://zeitstempel.dfn.de/',
            'stampcert' => 'src/dfn-cert/pki.dfn.pem',
            'stamplogin' => '',
            'public_db' => '0',
            'stamppass' => '',
        );
        $this->Teams->update($post);

        // trigger Exception with bad file path
        /* TODO
        $this->expectException(\RuntimeException::class);
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
         */
    }

    public function testUpdateName()
    {
        $this->Teams->updateName(1, 'New name');
    }

    public function testDestroy()
    {
        $id = $this->Teams->create('Destroy me');
        $this->Teams->destroy($id);
        // try to destroy a team with data
        $this->expectException(ImproperActionException::class);
        $this->Teams->destroy(1);
    }

    public function testGetAllStats()
    {
        $stats = $this->Teams->getAllStats();
        $this->assertTrue(is_array($stats));
        $this->assertEquals(2, $stats['totusers']);
    }
}
