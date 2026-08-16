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

use Elabftw\Elabftw\Db;
use Elabftw\Enums\Action;
use Elabftw\Exceptions\ImproperActionException;
use Elabftw\Exceptions\UnauthorizedException;
use Elabftw\Models\Users\Users;
use PDO;

use function bin2hex;
use function hash;
use function password_hash;
use function random_bytes;
use function sprintf;
use function str_repeat;

class ApiKeysTest extends \PHPUnit\Framework\TestCase
{
    private ApiKeys $ApiKeys;

    private Db $Db;

    protected function setUp(): void
    {
        $this->Db = Db::getConnection();
        $this->ApiKeys = new ApiKeys(new Users(1, 1));
    }

    public function testCreateAndGetApiPathAndDestroy(): void
    {
        $id = $this->ApiKeys->postAction(Action::Create, array('name' => 'test key', 'canwrite' => 1));
        $apiKey = $this->ApiKeys->getApiPath();
        $this->assertIsInt($id);
        $this->assertMatchesRegularExpression('/\A[[:xdigit:]]{32}\z/', $apiKey);

        $storage = $this->readStorage($id);
        $this->assertNull($storage['hash']);
        $this->assertSame(hash('sha256', $apiKey, true), $storage['token_hash']);
        $this->assertIsArray($this->ApiKeys->readFromApiKey($apiKey));

        $this->ApiKeys->setId($id);
        $this->assertTrue($this->ApiKeys->destroy());
    }

    public function testDestroyInTeam(): void
    {
        $this->assertTrue($this->ApiKeys->destroyInTeam(2));
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
        $id = $this->ApiKeys->createKnown('phpunit');
        $this->assertIsArray($this->ApiKeys->readFromApiKey('phpunit'));
        $this->ApiKeys->setId($id);
        $this->assertTrue($this->ApiKeys->destroy());
    }

    public function testInvalidKey(): void
    {
        $this->expectException(UnauthorizedException::class);
        $this->ApiKeys->readFromApiKey(str_repeat('0', 32));
    }

    public function testLegacyKeyRequiresIdAndMigratesOnFirstUse(): void
    {
        $secret = bin2hex(random_bytes(42));
        $id = $this->createLegacyKey($secret);

        $unauthorized = false;
        try {
            $this->ApiKeys->readFromApiKey($secret);
        } catch (UnauthorizedException) {
            $unauthorized = true;
        }
        $this->assertTrue($unauthorized, 'Legacy keys without their database id must be rejected.');

        $apiKey = sprintf('%d-%s', $id, $secret);
        $this->assertIsArray($this->ApiKeys->readFromApiKey($apiKey));

        $storage = $this->readStorage($id);
        $this->assertNull($storage['hash']);
        $this->assertSame(hash('sha256', $apiKey, true), $storage['token_hash']);
        $this->assertIsArray($this->ApiKeys->readFromApiKey($apiKey));

        $this->ApiKeys->setId($id);
        $this->assertTrue($this->ApiKeys->destroy());
    }

    public function testLastUsedAtIsTouchedAtMostEveryFiveMinutes(): void
    {
        $id = $this->ApiKeys->postAction(Action::Create, array('name' => 'touch test'));
        $apiKey = $this->ApiKeys->getApiPath();

        $this->setLastUsedAt($id, 1);
        $before = $this->readStorage($id)['last_used_at'];
        $this->ApiKeys->readFromApiKey($apiKey);
        $this->assertSame($before, $this->readStorage($id)['last_used_at']);

        $this->setLastUsedAt($id, 6);
        $before = $this->readStorage($id)['last_used_at'];
        $this->ApiKeys->readFromApiKey($apiKey);
        $this->assertNotSame($before, $this->readStorage($id)['last_used_at']);

        $this->ApiKeys->setId($id);
        $this->assertTrue($this->ApiKeys->destroy());
    }

    public function testReadAll(): void
    {
        $res = $this->ApiKeys->readAll();
        $this->assertIsArray($res);
        $knownKey = null;
        foreach ($res as $key) {
            $this->assertArrayNotHasKey('hash', $key);
            $this->assertArrayNotHasKey('token_hash', $key);
            if ($key['name'] === 'known key used from db:populate command') {
                $knownKey = $key;
            }
        }
        $this->assertIsArray($knownKey);
        $this->assertSame(1, $knownKey['can_write']);
    }

    public function testDestroyKeyOnCascade(): void
    {
        $tataId = 4;
        $Users2Teams = new Users2Teams(new Users(1, 1));
        $Users2Teams->addUserToTeams($tataId, array(3,4));
        // create new ApiKeys with user in team 3
        $ApiKeys = new ApiKeys(new Users(4, 3));
        $ApiKeys->createKnown('in team 3');
        $Users2Teams->rmUserFromTeams($tataId, array(3,4));
        // Ensure apikey is removed as well
        $this->expectException(UnauthorizedException::class);
        $ApiKeys->readFromApiKey('in team 3');
    }

    private function createLegacyKey(string $secret): int
    {
        $sql = 'INSERT INTO api_keys (name, hash, can_write, userid, team)
            VALUES (:name, :hash, 1, 1, 1)';
        $req = $this->Db->prepare($sql);
        $req->bindValue(':name', 'legacy test key');
        $req->bindValue(':hash', password_hash($secret, PASSWORD_BCRYPT));
        $this->Db->execute($req);
        return $this->Db->lastInsertId();
    }

    private function readStorage(int $id): array
    {
        $sql = 'SELECT hash, token_hash, last_used_at FROM api_keys WHERE id = :id';
        $req = $this->Db->prepare($sql);
        $req->bindValue(':id', $id, PDO::PARAM_INT);
        $this->Db->execute($req);
        return $req->fetch();
    }

    private function setLastUsedAt(int $id, int $minutesAgo): void
    {
        $sql = sprintf('UPDATE api_keys
            SET last_used_at = NOW() - INTERVAL %d MINUTE
            WHERE id = :id', $minutesAgo);
        $req = $this->Db->prepare($sql);
        $req->bindValue(':id', $id, PDO::PARAM_INT);
        $this->Db->execute($req);
    }
}
