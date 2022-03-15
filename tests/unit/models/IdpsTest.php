<?php declare(strict_types=1);
/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

namespace Elabftw\Models;

class IdpsTest extends \PHPUnit\Framework\TestCase
{
    private Idps $Idps;

    protected function setUp(): void
    {
        $this->Idps= new Idps();
    }

    public function testCreateReadUpdateDestroy(): void
    {
        $id = $this->Idps->create(
            'Test idp',
            'https://test.example.org',
            'https://test.example.org/sso',
            'sso:binding',
            'https://test.example.org/slo',
            'slo:binding',
            'x509',
            'x509_new',
            '1',
            'emailattr',
            'teamattr',
            'fnameattr',
            'lnameattr'
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
            'x509_new',
            '1',
            'emailattr',
            'teamattr',
            'fnameattr',
            'lnameattr'
        );
        $idp = $this->Idps->getActive($id);
        $this->assertEquals('Updated', $idp['name']);
        $this->assertEquals('x509', $idp['x509']);
        $this->assertEquals('x509_new', $idp['x509_new']);
        $this->Idps->setId($id);
        $this->Idps->destroy();
    }

    public function testReadAll(): void
    {
        $this->assertIsArray($this->Idps->readAll());
    }
}
