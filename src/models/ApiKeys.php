<?php declare(strict_types=1);
/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

namespace Elabftw\Models;

use function bin2hex;
use Elabftw\Elabftw\Db;
use Elabftw\Enums\Action;
use Elabftw\Exceptions\ImproperActionException;
use Elabftw\Interfaces\RestInterface;
use Elabftw\Services\Filter;
use Elabftw\Traits\SetIdTrait;
use function password_hash;
use function password_verify;
use PDO;
use function random_bytes;

/**
 * Api keys CRUD class
 */
class ApiKeys implements RestInterface
{
    use SetIdTrait;

    private Db $Db;

    private string $key = '';

    private int $keyId = 0;

    public function __construct(private Users $Users, ?int $id = null)
    {
        $this->Db = Db::getConnection();
        $this->setId($id);
    }

    public function postAction(Action $action, array $reqBody): int
    {
        return $this->create($reqBody['name'] ?? 'RTFM', $reqBody['canwrite'] ?? 0);
    }

    public function patch(Action $action, array $params): array
    {
        throw new ImproperActionException('No patch action for apikeys.');
    }

    public function getPage(): string
    {
        return sprintf('%d-%s', $this->keyId, $this->key);
    }

    /**
     * Create a known key so we can test against it in dev mode
     * This function should only be called from the db:populate command
     */
    public function createKnown(string $apiKey): int
    {
        $hash = password_hash($apiKey, PASSWORD_BCRYPT);
        return $this->insert('known key used for tests', 1, $hash);
    }

    /**
     * Read all keys for current user
     */
    public function readAll(): array
    {
        $sql = 'SELECT id, name, created_at, last_used_at, hash, can_write FROM api_keys WHERE userid = :userid AND team = :team ORDER BY last_used_at DESC';
        $req = $this->Db->prepare($sql);
        $req->bindParam(':userid', $this->Users->userData['userid'], PDO::PARAM_INT);
        $req->bindParam(':team', $this->Users->userData['team'], PDO::PARAM_INT);
        $this->Db->execute($req);

        return $req->fetchAll();
    }

    public function readOne(): array
    {
        return $this->readAll();
    }

    /**
     * Get a user from an API key
     * Note: at some point we should drop support for keys without id header
     */
    public function readFromApiKey(string $apiKey): array
    {
        $idFilter = '';
        // do we have userid information? old keys don't have it
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
        throw new ImproperActionException('No corresponding API key found!');
    }

    public function destroy(): bool
    {
        $sql = 'DELETE FROM api_keys WHERE id = :id';
        $req = $this->Db->prepare($sql);
        $req->bindValue(':id', $this->id, PDO::PARAM_INT);

        return $this->Db->execute($req);
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
        $this->Db->execute($req);

        // we store the id of the key in the object to serve it as part of the key
        $this->keyId = $this->Db->lastInsertId();
        return $this->keyId;
    }
}
