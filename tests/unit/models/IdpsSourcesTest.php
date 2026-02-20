<?php

declare(strict_types=1);

/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2024 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

namespace Elabftw\Models;

use DOMDocument;
use Elabftw\Enums\Action;
use Elabftw\Exceptions\IllegalActionException;
use Elabftw\Exceptions\ImproperActionException;
use Elabftw\Models\Users\Users;
use Elabftw\Services\HttpGetter;
use Elabftw\Services\Url2Xml;
use Elabftw\Services\Xml2Idps;
use GuzzleHttp\Psr7\Response;

class IdpsSourcesTest extends \PHPUnit\Framework\TestCase
{
    private IdpsSources $IdpsSources;

    private Users $requester;

    protected function setUp(): void
    {
        $this->requester = new Users(1, 1);
        $this->IdpsSources = new IdpsSources($this->requester);
    }

    public function testGetApiPath(): void
    {
        $this->assertEquals('api/v2/idps_sources/', $this->IdpsSources->getApiPath());
    }

    public function testNotSysadmin(): void
    {
        $IdpsSources = new IdpsSources(new Users(2, 1));
        $this->expectException(IllegalActionException::class);
        $IdpsSources->readAll();
    }

    public function testPatchNoId(): void
    {
        $this->expectException(ImproperActionException::class);
        $this->IdpsSources->patch(Action::Add, array());
    }

    public function testPatchIncorrectAction(): void
    {
        $this->expectException(ImproperActionException::class);
        $this->IdpsSources->setId(1);
        $this->IdpsSources->patch(Action::Add, array());
    }

    public function testCreate(): void
    {
        $params = array(
            'url' => 'https://source.example.fr/idps.xml/',
        );
        $id = $this->IdpsSources->postAction(Action::Create, $params);
        $this->assertIsInt($id);
        $this->assertIsArray($this->IdpsSources->readAll());
        $this->assertIsArray($this->IdpsSources->readAllAutoRefreshable());
        $this->IdpsSources->setId($id);
        $this->assertIsArray($this->IdpsSources->readOne());
        // try refresh now
        $Idps = new Idps($this->requester);
        $getterStub = $this->createStub(HttpGetter::class);
        $xmlContent = (string) file_get_contents(dirname(__DIR__, 2) . '/_data/idp-metadata.xml');
        $response = new Response(200, array(), $xmlContent);
        $getterStub->method('get')->willReturn($response);
        $source = $this->IdpsSources->readOne();
        $Url2Xml = new Url2Xml($getterStub, $source['url'], new DOMDocument());
        $dom = $Url2Xml->getXmlDocument();
        $Xml2Idps = new Xml2Idps($dom);
        $this->IdpsSources->refresh($Xml2Idps, $Idps);
        // now test toggleenable
        $this->IdpsSources->patch(Action::Validate, array());
        $this->IdpsSources->patch(Action::Finish, array());
        $this->assertTrue($this->IdpsSources->destroy());
    }
}
