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

use function _;
use function bin2hex;
use function hash;
use function password_verify;
use function preg_match;
use function random_bytes;

/**
 * Api keys CRUD class
 */
final class ApiKeys extends AbstractRest
{
    use SetIdTrait;

    private const int KEY_RANDOM_BYTES = 16;

    private const string LEGACY_KEY_PATTERN = '/\A([1-9][0-9]*)-(.+)\z/';

    public string $key = '';

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
        return $this->key;
    }

    /**
     * Create a known key so we can test against it in dev mode
     * It can also be used to create an initial sysadmin key
     * This function should only be called from the db:populate command
     */
    public function createKnown(string $apiKey): int
    {
        $this->key = $apiKey;
        return $this->insert('known key used from db:populate command', 1, $this->getTokenHash($apiKey));
    }

    /**
     * Read all keys for current user
     */
    #[Override]
    public function readAll(?QueryParamsInterface $queryParams = null): array
    {
        $sql = 'SELECT ak.id, ak.name, ak.created_at, ak.last_used_at, ak.can_write, ak.team, teams.name AS team_name
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
     */
    public function readFromApiKey(string $apiKey): array
    {
        $tokenHash = $this->getTokenHash($apiKey);
        $key = $this->readFromTokenHash($tokenHash);
        if ($key !== false) {
            return $key;
        }

        // Legacy keys are migrated to token_hash after their first successful use.
        $key = $this->readLegacyKey($apiKey);
        if ($key !== false) {
            $this->migrateLegacyKey((int) $key['id'], $tokenHash);
            return $key;
        }

        throw new UnauthorizedException(description: _('No corresponding API key found!'));
    }

    #[Override]
    public function destroy(): bool
    {
        $sql = 'DELETE FROM api_keys WHERE id = :id AND userid = :userid';
        $req = $this->Db->prepare($sql);
        $req->bindValue(':id', $this->id, PDO::PARAM_INT);
        $req->bindValue(':userid', $this->Users->requester->getUserid(), PDO::PARAM_INT);

        if ($res = $this->Db->execute($req)) {
            AuditLogs::create(new ApiKeyDeleted($this->Users->requester->getUserid(), $this->Users->getUserid()));
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
            AuditLogs::create(new ApiKeyDeleted($this->Users->requester->getUserid(), $this->Users->getUserid()));
        }
        return $res;
    }

    public function create(string $name, int $canwrite): int
    {
        $this->key = $this->generateKey();
        return $this->insert(Filter::title($name), $canwrite, $this->getTokenHash($this->key));
    }

    private function readFromTokenHash(string $tokenHash): array|false
    {
        $sql = 'SELECT ak.id, ak.userid, ak.can_write, ak.team,
                IF(ak.last_used_at IS NULL OR ak.last_used_at <= NOW() - INTERVAL 5 MINUTE, 1, 0) AS touch_required
            FROM api_keys AS ak
            INNER JOIN users AS u ON u.userid = ak.userid
            INNER JOIN users2teams AS u2t ON u2t.users_id = ak.userid AND u2t.teams_id = ak.team
            WHERE ak.token_hash = :token_hash
                AND u.validated = 1
                AND IFNULL(u.valid_until, \'3000-01-01\') > NOW()
                AND u2t.is_archived = 0
            LIMIT 1';
        $req = $this->Db->prepare($sql);
        $req->bindValue(':token_hash', $tokenHash, PDO::PARAM_LOB);
        $this->Db->execute($req);
        $key = $req->fetch();
        if ($key === false) {
            return false;
        }
        if ((bool) $key['touch_required']) {
            $this->touch((int) $key['id']);
        }
        unset($key['touch_required']);
        return $key;
    }

    private function readLegacyKey(string $apiKey): array|false
    {
        if (preg_match(self::LEGACY_KEY_PATTERN, $apiKey, $matches) !== 1) {
            return false;
        }

        $sql = 'SELECT ak.id, ak.hash, ak.userid, ak.can_write, ak.team
            FROM api_keys AS ak
            INNER JOIN users AS u ON u.userid = ak.userid
            INNER JOIN users2teams AS u2t ON u2t.users_id = ak.userid AND u2t.teams_id = ak.team
            WHERE ak.id = :id AND ak.hash IS NOT NULL
                AND u.validated = 1
                AND IFNULL(u.valid_until, \'3000-01-01\') > NOW()
                AND u2t.is_archived = 0
            LIMIT 1';
        $req = $this->Db->prepare($sql);
        $req->bindValue(':id', (int) $matches[1], PDO::PARAM_INT);
        $this->Db->execute($req);
        $key = $req->fetch();
        if ($key === false || !password_verify($matches[2], (string) $key['hash'])) {
            return false;
        }
        unset($key['hash']);
        return $key;
    }

    private function migrateLegacyKey(int $keyId, string $tokenHash): bool
    {
        $sql = 'UPDATE api_keys
            SET token_hash = :token_hash, hash = NULL, last_used_at = NOW()
            WHERE id = :id AND token_hash IS NULL AND hash IS NOT NULL';
        $req = $this->Db->prepare($sql);
        $req->bindValue(':token_hash', $tokenHash, PDO::PARAM_LOB);
        $req->bindValue(':id', $keyId, PDO::PARAM_INT);
        return $this->Db->execute($req);
    }

    private function touch(int $keyId): bool
    {
        $sql = 'UPDATE api_keys
            SET last_used_at = NOW()
            WHERE id = :id
                AND (last_used_at IS NULL OR last_used_at <= NOW() - INTERVAL 5 MINUTE)';
        $req = $this->Db->prepare($sql);
        $req->bindParam(':id', $keyId, PDO::PARAM_INT);
        return $this->Db->execute($req);
    }

    private function generateKey(): string
    {
        return bin2hex(random_bytes(self::KEY_RANDOM_BYTES));
    }

    private function getTokenHash(string $apiKey): string
    {
        return hash('sha256', $apiKey, true);
    }

    private function insert(string $name, int $canwrite, string $tokenHash): int
    {
        $sql = 'INSERT INTO api_keys (name, token_hash, can_write, userid, team)
            VALUES (:name, :token_hash, :can_write, :userid, :team)';
        $req = $this->Db->prepare($sql);
        $req->bindParam(':name', $name);
        $req->bindValue(':token_hash', $tokenHash, PDO::PARAM_LOB);
        $req->bindParam(':can_write', $canwrite, PDO::PARAM_INT);
        $req->bindParam(':userid', $this->Users->userData['userid'], PDO::PARAM_INT);
        $req->bindParam(':team', $this->Users->userData['team'], PDO::PARAM_INT);
        $res = $this->Db->execute($req);
        // must be executed before AuditLog request!
        $keyId = $this->Db->lastInsertId();
        if ($res) {
            AuditLogs::create(new ApiKeyCreated((int) $this->Users->requester->userid, (int) $this->Users->userid));
        }
        return $keyId;
    }
}
