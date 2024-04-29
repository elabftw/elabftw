<?php

/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2024 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

declare(strict_types=1);

namespace Elabftw\Models;

use Elabftw\AuditEvent\SignatureKeysCreated;
use Elabftw\Elabftw\Db;
use Elabftw\Elabftw\MinisignKeys;
use Elabftw\Enums\Action;
use Elabftw\Enums\ApiEndpoint;
use Elabftw\Enums\State;
use Elabftw\Exceptions\ImproperActionException;
use Elabftw\Interfaces\RestInterface;
use Elabftw\Traits\SetIdTrait;
use PDO;

/**
 * Signature keys CRUD class
 */
class SigKeys implements RestInterface
{
    use SetIdTrait;

    private Db $Db;

    private int $userid;

    public function __construct(private Users $Users, public ?int $id = null)
    {
        $this->Db = Db::getConnection();
        $this->userid = $this->Users->userData['userid'] ?? throw new ImproperActionException('Invalid userid!');
        $this->setId($id);
    }

    public function postAction(Action $action, array $reqBody): int
    {
        $key = MinisignKeys::generate($reqBody['passphrase'] ?? throw new ImproperActionException(_('The mandatory "passphrase" parameter was not provided!')));

        $this->destroy();
        $sql = 'INSERT INTO sig_keys (pubkey, privkey, userid) VALUES (:pubkey, :privkey, :userid)';
        $req = $this->Db->prepare($sql);
        $req->bindValue(':pubkey', $key->serializePk(), PDO::PARAM_STR);
        $req->bindValue(':privkey', $key->serializeSk(), PDO::PARAM_STR);
        $req->bindParam(':userid', $this->userid, PDO::PARAM_INT);
        $res = $req->execute();
        $keyId = $this->Db->lastInsertId();
        if ($res) {
            AuditLogs::create(new SignatureKeysCreated($key->getIdHex(), $this->userid, $this->userid));
        }
        return $keyId;
    }

    /**
     * This is the regenerate action
     */
    public function patch(Action $action, array $params): array
    {
        $this->id = $this->postAction(Action::Create, $params);
        return $this->readOne();
    }

    public function getPage(): string
    {
        return sprintf('api/v2/%s/', ApiEndpoint::SigKeys->value);
    }

    /**
     * Read all keys for current user, including the archived ones, with the active one first
     */
    public function readAll(): array
    {
        $sql = 'SELECT id, pubkey, privkey, created_at, last_used_at, userid, state
            FROM sig_keys WHERE userid = :userid ORDER BY state ASC';
        $req = $this->Db->prepare($sql);
        $req->bindParam(':userid', $this->userid, PDO::PARAM_INT);
        $this->Db->execute($req);

        return $req->fetchAll();
    }

    public function readOne(): array
    {
        $sql = 'SELECT id, pubkey, privkey, created_at, last_used_at, userid, state
            FROM sig_keys WHERE id = :id';
        $req = $this->Db->prepare($sql);
        $req->bindParam(':id', $this->id, PDO::PARAM_INT);
        $this->Db->execute($req);

        return $this->Db->fetch($req);
    }

    /**
     * Make all existing keys inactive (state:archived) for that user
     */
    public function destroy(): bool
    {
        $sql = 'UPDATE sig_keys SET state = :state WHERE userid = :userid';
        $req = $this->Db->prepare($sql);
        $req->bindParam(':userid', $this->userid, PDO::PARAM_INT);
        $req->bindValue(':state', State::Archived->value, PDO::PARAM_INT);
        return $this->Db->execute($req);
    }

    public function touch(): bool
    {
        $sql = 'UPDATE sig_keys SET last_used_at = NOW() WHERE userid = :userid AND state = :state LIMIT 1';
        $req = $this->Db->prepare($sql);
        $req->bindParam(':userid', $this->userid, PDO::PARAM_INT);
        $req->bindValue(':state', State::Normal->value, PDO::PARAM_INT);
        return $this->Db->execute($req);
    }
}
