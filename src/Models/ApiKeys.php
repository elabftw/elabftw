<?php

/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

declare(strict_types=1);

namespace Elabftw\Models;

use Elabftw\AuditEvent\ApiKeyCreated;
use Elabftw\AuditEvent\ApiKeyDeleted;
use Elabftw\Enums\Action;
use Elabftw\Exceptions\UnauthorizedException;
use Elabftw\Interfaces\QueryParamsInterface;
use Elabftw\Models\Users\Users;
use Elabftw\Services\Filter;
use Elabftw\Traits\SetIdTrait;
use Override;
use PDO;

use function bin2hex;
use function password_hash;
use function password_verify;
use function random_bytes;

/**
 * Api keys CRUD class
 */
final class ApiKeys extends AbstractRest
{
    use SetIdTrait;

    public string $key = '';

    public int $keyId = 0;

    public function __construct(private Users $Users, ?int $id = null)
    {
        parent::__construct();
        $this->setId($id);
    }

    #[Override]
    public function postAction(Action $action, array $reqBody): int
    {
        return $this->create($reqBody['name'] ?? 'An API key', $reqBody['canwrite'] ?? 0);
    }

    #[Override]
    public function getApiPath(): string
    {
        return sprintf('%d-%s', $this->keyId, $this->key);
    }

    /**
     * Create a known key so we can test against it in dev mode
     * It can also be used to create an initial sysadmin key
     * This function should only be called from the db:populate command
     */
    public function createKnown(string $apiKey): int
    {
        $hash = password_hash($apiKey, PASSWORD_BCRYPT);
        return $this->insert('known key used from db:populate command', 1, $hash);
    }

    /**
     * Read all keys for current user
     */
    #[Override]
    public function readAll(?QueryParamsInterface $queryParams = null): array
    {
        $sql = 'SELECT ak.id, ak.name, ak.created_at, ak.last_used_at, ak.hash, ak.can_write, ak.team, teams.name AS team_name
            FROM api_keys AS ak
            LEFT JOIN teams ON teams.id = ak.team
            WHERE ak.userid = :userid ORDER BY last_used_at DESC';
        $req = $this->Db->prepare($sql);
        $req->bindParam(':userid', $this->Users->userData['userid'], PDO::PARAM_INT);
        $this->Db->execute($req);

        return $req->fetchAll();
    }

    /**
     * Get a user from an API key
     * Note: at some point we should drop support for keys without id header
     * Id header avoids looping over all the keys to find the correct one
     */
    public function readFromApiKey(string $apiKey): array
    {
        $idFilter = '';
        // do we have key id information? old keys don't have it
        if (str_contains($apiKey, '-')) {
            // extract the keyId from the key
            $exploded = explode('-', $apiKey, 2);
            $this->keyId = (int) $exploded[0];
            // we reassign it to this variable so it's transparent for the code below
            $apiKey = $exploded[1] ?? '';
            $idFilter = ' WHERE id = :id';
        }
        $sql = 'SELECT id, hash, userid, can_write, team FROM api_keys' . $idFilter;
        $req = $this->Db->prepare($sql);
        if ($idFilter) {
            $req->bindParam(':id', $this->keyId, PDO::PARAM_INT);
        }
        $this->Db->execute($req);
        foreach ($req->fetchAll() as $key) {
            if (password_verify($apiKey, $key['hash'])) {
                $this->touch($key['id']);
                return $key;
            }
        }
        throw new UnauthorizedException(description: _('No corresponding API key found!'));
    }

    #[Override]
    public function destroy(): bool
    {
        $sql = 'DELETE FROM api_keys WHERE id = :id AND userid = :userid';
        $req = $this->Db->prepare($sql);
        $req->bindValue(':id', $this->id, PDO::PARAM_INT);
        $req->bindValue(':userid', $this->Users->requester->userid ?? 0, PDO::PARAM_INT);

        if ($res = $this->Db->execute($req)) {
            AuditLogs::create(new ApiKeyDeleted($this->Users->requester->userid ?? 0, $this->Users->userid ?? 0));
        }
        return $res;
    }

    /**
     * Remove keys of a user in a team
     */
    public function destroyInTeam(int $team): bool
    {
        $sql = 'DELETE FROM api_keys WHERE team = :team AND userid = :userid';
        $req = $this->Db->prepare($sql);
        $req->bindValue(':team', $team, PDO::PARAM_INT);
        $req->bindValue(':userid', $this->Users->requester->userid ?? 0, PDO::PARAM_INT);

        if ($res = $this->Db->execute($req)) {
            AuditLogs::create(new ApiKeyDeleted($this->Users->requester->userid ?? 0, $this->Users->userid ?? 0));
        }
        return $res;
    }

    public function create(string $name, int $canwrite): int
    {
        $hash = password_hash($this->generateKey(), PASSWORD_BCRYPT);
        return $this->insert(Filter::title($name), $canwrite, $hash);
    }

    private function touch(int $keyId): bool
    {
        $sql = 'UPDATE api_keys SET last_used_at = NOW() WHERE id = :id';
        $req = $this->Db->prepare($sql);
        $req->bindParam(':id', $keyId, PDO::PARAM_INT);
        return $this->Db->execute($req);
    }

    private function generateKey(): string
    {
        // keep it in the object so we can display it to the user after
        $this->key = bin2hex(random_bytes(42));
        return $this->key;
    }

    private function insert(string $name, int $canwrite, string $hash): int
    {
        $sql = 'INSERT INTO api_keys (name, hash, can_write, userid, team) VALUES (:name, :hash, :can_write, :userid, :team)';
        $req = $this->Db->prepare($sql);
        $req->bindParam(':name', $name);
        $req->bindParam(':hash', $hash);
        $req->bindParam(':can_write', $canwrite, PDO::PARAM_INT);
        $req->bindParam(':userid', $this->Users->userData['userid'], PDO::PARAM_INT);
        $req->bindParam(':team', $this->Users->userData['team'], PDO::PARAM_INT);
        $res = $this->Db->execute($req);
        // we store the id of the key in the object to serve it as part of the key
        // must be executed before AuditLog request!
        $this->keyId = $this->Db->lastInsertId();
        if ($res) {
            AuditLogs::create(new ApiKeyCreated((int) $this->Users->requester->userid, (int) $this->Users->userid));
        }
        return $this->keyId;
    }
}
