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

use function bin2hex;
use Elabftw\Elabftw\Db;
use Elabftw\Exceptions\ImproperActionException;
use Elabftw\Interfaces\DestroyableInterface;
use function filter_var;
use function password_hash;
use function password_verify;
use PDO;
use function random_bytes;

/**
 * Api keys
 */
class ApiKeys implements DestroyableInterface
{
    /** @var Db $Db SQL Database */
    private $Db;

    /** @var Users $Users instance of Users */
    private $Users;

    /**
     * Constructor
     *
     * @param Users $users instance of Users
     */
    public function __construct(Users $users)
    {
        $this->Db = Db::getConnection();
        $this->Users = $users;
    }

    /**
     * Create a new key for current user
     *
     * @param string $name the friendly name of the key
     * @param int $canWrite readonly or readwrite?
     * @return string the key
     */
    public function create(string $name, int $canWrite): string
    //public function create(ParamsProcessor $params): string
    {
        $name = filter_var($name, FILTER_SANITIZE_STRING);
        $apiKey = bin2hex(random_bytes(42));
        $hash = password_hash($apiKey, PASSWORD_BCRYPT);

        $sql = 'INSERT INTO api_keys (name, hash, can_write, userid, team) VALUES (:name, :hash, :can_write, :userid, :team)';
        $req = $this->Db->prepare($sql);
        $req->bindParam(':name', $name);
        $req->bindParam(':hash', $hash);
        $req->bindParam(':can_write', $canWrite, PDO::PARAM_INT);
        $req->bindParam(':userid', $this->Users->userData['userid'], PDO::PARAM_INT);
        $req->bindParam(':team', $this->Users->userData['team'], PDO::PARAM_INT);
        $this->Db->execute($req);

        return $apiKey;
    }

    /**
     * Create a known key so we can test against it in dev mode
     * This function should only be called from the dev:populate command
     *
     * @param string $apiKey
     * @return void
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
     *
     * @return array
     */
    public function readAll(): array
    {
        $sql = 'SELECT id, name, created_at, hash, can_write FROM api_keys WHERE userid = :userid AND team = :team';
        $req = $this->Db->prepare($sql);
        $req->bindParam(':userid', $this->Users->userData['userid'], PDO::PARAM_INT);
        $req->bindParam(':team', $this->Users->userData['team'], PDO::PARAM_INT);
        $this->Db->execute($req);
        $res = $req->fetchAll();
        if ($res === false) {
            return array();
        }
        return $res;
    }

    /**
     * Get a user from an API key
     *
     * @param string $apiKey
     * @return array
     */
    public function readFromApiKey(string $apiKey): array
    {
        $sql = 'SELECT hash, userid, can_write, team FROM api_keys';
        $req = $this->Db->prepare($sql);
        $this->Db->execute($req);
        $keysArr = $req->fetchAll();
        if ($keysArr === false) {
            $keysArr = array();
        }

        foreach ($keysArr as $key) {
            if (password_verify($apiKey, $key['hash'])) {
                return array('userid' => $key['userid'], 'canWrite' => $key['can_write'], 'team' => $key['team']);
            }
        }
        throw new ImproperActionException('No corresponding API key found!');
    }

    /**
     * Destroy an api key
     */
    public function destroy(int $id): bool
    {
        $sql = 'DELETE FROM api_keys WHERE id = :id';
        $req = $this->Db->prepare($sql);
        $req->bindParam(':id', $id, PDO::PARAM_INT);
        return $this->Db->execute($req);
    }
}
