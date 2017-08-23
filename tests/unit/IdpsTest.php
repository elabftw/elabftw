<?php
namespace Elabftw\Elabftw;

class IdpsTest extends \PHPUnit_Framework_TestCase
{
    protected function setUp()
    {
        $this->Idps= new Idps();
    }

    public function testCreateReadUpdateDestroy()
    {
        $id = $this->Idps->create(
            'Test idp',
            'https://test.example.org',
            'https://test.example.org/sso',
            'sso:binding',
            'https://test.example.org/slo',
            'slo:binding',
            'x509'
        );
        $this->assertTrue($this->Idps->update(
            $id,
            'Updated',
            'https://test.example.org',
            'https://test.example.org/sso',
            'sso:binding',
            'https://test.example.org/slo',
            'slo:binding',
            'x509'
        ));
        $idp = $this->Idps->read($id);
        $this->assertEquals('Updated', $idp['name']);
        $this->assertEquals('x509', $idp['x509']);
        $this->assertTrue($this->Idps->destroy($id));
    }

    public function testReadAll()
    {
        $this->assertTrue(is_array($this->Idps->readAll()));
    }
}
