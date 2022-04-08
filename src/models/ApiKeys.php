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

use Elabftw\Elabftw\Db;
use Elabftw\Exceptions\ImproperActionException;
use Elabftw\Interfaces\ContentParamsInterface;
use Elabftw\Interfaces\CreateApikeyParamsInterface;
use Elabftw\Interfaces\CrudInterface;
use Elabftw\Interfaces\ParamsInterface;
use Elabftw\Traits\SetIdTrait;
use function password_hash;
use function password_verify;
use PDO;

/**
 * Api keys CRUD class
 */
class ApiKeys implements CrudInterface
{
    use SetIdTrait;

    private Db $Db;

    public function __construct(private Users $Users, ?int $id = null)
    {
        $this->Db = Db::getConnection();
        $this->id = $id;
    }

    /**
     * Create a new key for current user
     */
    public function create(CreateApikeyParamsInterface $params): int
    {
        $hash = password_hash($params->getKey(), PASSWORD_BCRYPT);

        $sql = 'INSERT INTO api_keys (name, hash, can_write, userid, team) VALUES (:name, :hash, :can_write, :userid, :team)';
        $req = $this->Db->prepare($sql);
        $req->bindValue(':name', $params->getContent());
        $req->bindParam(':hash', $hash);
        $req->bindValue(':can_write', $params->getCanwrite(), PDO::PARAM_INT);
        $req->bindParam(':userid', $this->Users->userData['userid'], PDO::PARAM_INT);
        $req->bindParam(':team', $this->Users->userData['team'], PDO::PARAM_INT);
        $this->Db->execute($req);

        return $this->Db->lastInsertId();
    }

    /**
     * Create a known key so we can test against it in dev mode
     * This function should only be called from the dev:populate command
     */
    public function createKnown(string $apiKey): void
    {
        $hash = password_hash($apiKey, PASSWORD_BCRYPT);

        $sql = 'INSERT INTO api_keys (name, hash, can_write, userid, team) VALUES (:name, :hash, :can_write, :userid, :team)';
        $req = $this->Db->prepare($sql);
        $req->bindValue(':name', 'test key');
        $req->bindParam(':hash', $hash);
        $req->bindValue(':can_write', 1, PDO::PARAM_INT);
        $req->bindParam(':userid', $this->Users->userData['userid'], PDO::PARAM_INT);
        $req->bindParam(':team', $this->Users->userData['team'], PDO::PARAM_INT);
        $this->Db->execute($req);
    }

    /**
     * Read all keys for current user
     */
    public function read(ContentParamsInterface $params): array
    {
        $sql = 'SELECT id, name, created_at, hash, can_write FROM api_keys WHERE userid = :userid AND team = :team';
        $req = $this->Db->prepare($sql);
        $req->bindParam(':userid', $this->Users->userData['userid'], PDO::PARAM_INT);
        $req->bindParam(':team', $this->Users->userData['team'], PDO::PARAM_INT);
        $this->Db->execute($req);

        return $req->fetchAll();
    }

    /**
     * Get a user from an API key
     */
    public function readFromApiKey(string $apiKey): array
    {
        $sql = 'SELECT hash, userid, can_write, team FROM api_keys';
        $req = $this->Db->prepare($sql);
        $this->Db->execute($req);
        foreach ($req->fetchAll() as $key) {
            if (password_verify($apiKey, $key['hash'])) {
                return array('userid' => $key['userid'], 'canWrite' => $key['can_write'], 'team' => $key['team']);
            }
        }
        throw new ImproperActionException('No corresponding API key found!');
    }

    public function update(ParamsInterface $params): bool
    {
        return false;
    }

    public function destroy(): bool
    {
        $sql = 'DELETE FROM api_keys WHERE id = :id AND userid = :userid';
        $req = $this->Db->prepare($sql);
        $req->bindValue(':id', $this->id, PDO::PARAM_INT);
        $req->bindParam(':userid', $this->Users->userData['userid'], PDO::PARAM_INT);

        return $this->Db->execute($req);
    }
}
