<?php
/**
 * @author Nicolas CARPi <nicolas.carpi@curie.fr>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */
declare(strict_types=1);

namespace Elabftw\Models;

use Elabftw\Elabftw\Db;
use Elabftw\Exceptions\DatabaseErrorException;
use Elabftw\Exceptions\ImproperActionException;
use Elabftw\Interfaces\CrudInterface;
use PDO;

/**
 * Api keys
 */
class ApiKeys implements CrudInterface
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
    {
        $name = \filter_var($name, FILTER_SANITIZE_STRING);
        $apiKey = \bin2hex(\random_bytes(42));
        $hash = \password_hash($apiKey, PASSWORD_DEFAULT);

        $sql = 'INSERT INTO api_keys (name, hash, can_write, userid) VALUES (:name, :hash, :can_write, :userid)';
        $req = $this->Db->prepare($sql);
        $req->bindParam(':name', $name);
        $req->bindParam(':hash', $hash);
        $req->bindParam(':can_write', $canWrite, PDO::PARAM_INT);
        $req->bindParam(':userid', $this->Users->userData['userid'], PDO::PARAM_INT);

        if ($req->execute() !== true) {
            throw new DatabaseErrorException('Error while executing SQL query.');
        }

        return $apiKey;
    }

    /**
     * Read all keys for current user
     *
     * @return array
     */
    public function readAll(): array
    {
        $sql = 'SELECT id, name, created_at, hash, can_write FROM api_keys WHERE userid = :userid';
        $req = $this->Db->prepare($sql);
        $req->bindParam(':userid', $this->Users->userData['userid'], PDO::PARAM_INT);

        if ($req->execute() !== true) {
            throw new DatabaseErrorException('Error while executing SQL query.');
        }
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
        $sql = 'SELECT hash, userid, can_write FROM api_keys';
        $req = $this->Db->prepare($sql);
        if ($req->execute() !== true) {
            throw new DatabaseErrorException('Error while executing SQL query.');
        }
        $keysArr = $req->fetchAll();
        if ($keysArr === false) {
            $keysArr = array();
        }

        foreach ($keysArr as $key) {
            if (\password_verify($apiKey, $key['hash'])) {
                return array('userid' => $key['userid'], 'canWrite' => $key['can_write']);
            }
        }
        throw new ImproperActionException('No corresponding API key found!');
    }

    /**
     * Destroy an api key
     *
     * @param int $id id of the key
     * @return void
     */
    public function destroy(int $id): void
    {
        $sql = 'DELETE FROM api_keys WHERE id = :id';
        $req = $this->Db->prepare($sql);
        $req->bindParam(':id', $id, PDO::PARAM_INT);
        if ($req->execute() !== true) {
            throw new DatabaseErrorException('Error while executing SQL query.');
        }
    }
}
