<?php

declare(strict_types=1);

/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2024 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

namespace Elabftw\Services;

use DateTimeImmutable;
use DateTimeZone;
use DOMDocument;
use Elabftw\Enums\Storage;
use Elabftw\Exceptions\ImproperActionException;

class Xml2IdpsTest extends \PHPUnit\Framework\TestCase
{
    public function testGetIdpsFromDomNoEntityDescriptor(): void
    {
        $content = '<?xml version="1.0" encoding="UTF-8"?><elab:Ftw></elab:Ftw>';
        $dom = new DOMDocument();
        $dom->loadXML($content);
        $Xml2Idps = new Xml2Idps($dom);
        $this->expectException(ImproperActionException::class);
        $Xml2Idps->getIdpsFromDom();
    }

    public function testGetIdpsFromDom(): void
    {
        $content = (string) file_get_contents(dirname(__DIR__, 2) . '/_data/idp-metadata.xml');
        $dom = new DOMDocument();
        $dom->loadXML($content);
        $Xml2Idps = new Xml2Idps($dom);
        $idps = $Xml2Idps->getIdpsFromDom();
        $this->assertIsArray($idps);
        $this->assertEquals(2, count($idps));
    }

    public function testProcessCert(): void
    {
        $cert = Storage::FIXTURES->getStorage()->getFs()->read('x509.crt');
        $res = Xml2Idps::processCert($cert);
        $this->assertSame(Filter::pem($cert), Filter::pem($res[0]));
        $this->assertSame('6973a83ff6d8dbfa409159dcf2be83acf5322243c3bd53d476ccd10f7f08ca89', $res[1]);
        $this->assertEquals(new DateTimeImmutable('2022-02-10T08:55:32', new DateTimeZone('UTC')), $res[2]);
        $this->assertEquals(new DateTimeImmutable('2027-02-10T08:55:32', new DateTimeZone('UTC')), $res[3]);
    }
}
