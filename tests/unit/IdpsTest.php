<?php
namespace Elabftw\Models;

class IdpsTest extends \PHPUnit\Framework\TestCase
{
    protected function setUp(): void
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
            'x509',
            '1'
        );
        $this->Idps->update(
            $id,
            'Updated',
            'https://test.example.org',
            'https://test.example.org/sso',
            'sso:binding',
            'https://test.example.org/slo',
            'slo:binding',
            'x509',
            '1'
        );
        $idp = $this->Idps->read($id);
        $this->assertEquals('Updated', $idp['name']);
        $this->assertEquals('x509', $idp['x509']);
        $this->Idps->destroy($id);
    }

    public function testReadAll()
    {
        $this->assertIsArray($this->Idps->readAll());
    }
}
