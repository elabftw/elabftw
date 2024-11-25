<?php

declare(strict_types=1);
/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

namespace Elabftw\Models;

use Elabftw\Enums\Action;
use Elabftw\Exceptions\ImproperActionException;

class ApiKeysTest extends \PHPUnit\Framework\TestCase
{
    private ApiKeys $ApiKeys;

    private Users2Teams $Users2Teams;

    protected function setUp(): void
    {
        $this->ApiKeys = new ApiKeys(new Users(1, 1));
        $this->Users2Teams = new Users2Teams(new Users(1, 1));
    }

    public function testCreateAndGetApiPathAndDestroy(): void
    {
        $id = $this->ApiKeys->postAction(Action::Create, array('name' => 'test key', 'canwrite' => 1));
        $this->assertIsInt($id);
        $this->assertIsString($this->ApiKeys->getApiPath());
        $this->assertMatchesRegularExpression('/\d+-[[:xdigit:]]{84}/', $this->ApiKeys->getApiPath());
        $this->ApiKeys->setId($id);
        $this->assertTrue($this->ApiKeys->destroy());
    }

    public function testPatchInvalidUpdate(): void
    {
        $this->expectException(ImproperActionException::class);
        $this->ApiKeys->patch(Action::Update, array());
    }

    public function testPatchInvalidArchive(): void
    {
        $this->expectException(ImproperActionException::class);
        $this->ApiKeys->patch(Action::Archive, array());
    }

    public function testReadOne(): void
    {
        $this->assertIsArray($this->ApiKeys->readOne());
    }

    public function testCreateKnown(): void
    {
        $this->ApiKeys->createKnown('phpunit');
        $this->assertIsArray($this->ApiKeys->readFromApiKey('phpunit'));
    }

    public function testInvalidKey(): void
    {
        $this->expectException(ImproperActionException::class);
        $this->ApiKeys->readFromApiKey('666-unknown key');
    }

    public function testReadAll(): void
    {
        $res = $this->ApiKeys->readAll();
        $this->assertIsArray($res);
        $this->assertSame('known key used from db:populate command', $res[1]['name']);
        $this->assertSame(1, $res[1]['can_write']);
    }

    public function testDestroyKeyOnCascade(): void
    {
        $tata = new Users(4, 2);
        $this->Users2Teams->addUserToTeams($tata->userData['userid'], array(3,4));
        $this->ApiKeys->createKnown('phpunit');
        $this->Users2Teams->rmUserFromTeams($tata->userData['userid'], array(3,4));
        // test apikey is deleted on cascade when user is removed from team
        $this->expectException(ImproperActionException::class);
        $this->ApiKeys->readFromApiKey('phpunit');
        $tata->destroy();
    }
}
