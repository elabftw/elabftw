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
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;

class Url2XmlTest extends \PHPUnit\Framework\TestCase
{
    public function testGetEmptyResponse(): void
    {
        $mock = new MockHandler(array(
            new Response(200, array(), ''),
        ));
        $handlerStack = HandlerStack::create($mock);
        $client = new Client(array('handler' => $handlerStack));
        $getter = new HttpGetter($client);
        $Url2Xml = new Url2Xml($getter, 'https://test.example.com/metadata.xml', new DOMDocument());
        $this->expectException(ImproperActionException::class);
        $Url2Xml->getXmlDocument();
    }

    public function testGetNotXml(): void
    {
        $mock = new MockHandler(array(
            new Response(200, array(), '<?php echo $wtf;'),
        ));
        $handlerStack = HandlerStack::create($mock);
        $client = new Client(array('handler' => $handlerStack));
        $getter = new HttpGetter($client);
        $Url2Xml = new Url2Xml($getter, 'https://test.example.com/metadata.xml', new DOMDocument());
        $this->expectException(ImproperActionException::class);
        $Url2Xml->getXmlDocument();
    }

    public function testGetXmlDocument(): void
    {
        $content = (string) file_get_contents(dirname(__DIR__, 2) . '/_data/idp-metadata.xml');
        $mock = new MockHandler(array(
            new Response(200, array(), $content),
        ));
        $handlerStack = HandlerStack::create($mock);
        $client = new Client(array('handler' => $handlerStack));
        $getter = new HttpGetter($client);
        $Url2Xml = new Url2Xml($getter, 'https://test.example.com/metadata.xml', new DOMDocument());
        $dom = $Url2Xml->getXmlDocument();
        $this->assertInstanceOf(DOMDocument::class, $dom);
    }
}
