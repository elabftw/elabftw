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

use DOMDocument;
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
}
