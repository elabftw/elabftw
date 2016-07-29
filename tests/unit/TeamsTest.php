<?php
namespace Elabftw\Elabftw;

use PDO;

class TeamsTest extends \PHPUnit_Framework_TestCase
{
    protected function setUp()
    {
        $this->Teams= new Teams();
    }

    public function testCreate()
    {
        $this->assertTrue($this->Teams->create('Test team'));
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
            'link_href' => 'http://www.elabftw.net',
            'stampprovider' => 'http://zeitstempel.dfn.de/',
            'stampcert' => 'vendor/pki.dfn.pem',
            'stamplogin' => '',
            'stamppass' => ''
        );
        $this->assertTrue($this->Teams->update($post, 1));
    }
}
