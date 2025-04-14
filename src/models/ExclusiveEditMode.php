<?php

/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @author Marcel Bolten <github@marcelbolten.de>
 * @copyright 2024 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

declare(strict_types=1);

namespace Elabftw\Models;

use Elabftw\Elabftw\Db;
use Elabftw\Enums\Action;
use Elabftw\Exceptions\ImproperActionException;
use PDO;

use function sprintf;

/**
 * For dealing with exclusive edit mode aka write lock
 */
final class ExclusiveEditMode
{
    // time after which we consider the lock stale and ignore it
    private const int EXPIRATION_MINUTES = 42;

    private Db $Db;

    public function __construct(private AbstractEntity $Entity)
    {
        $this->Db = Db::getConnection();
    }

    public function readOne(): array
    {
        if ($this->Entity->id === null) {
            return array();
        }
        // failsafe: is_stale is 1 if the entry is locked for longer than EXPIRATION_MINUTES
        $sql = sprintf(
            'SELECT locked_by,
                CONCAT(users.firstname, " ", users.lastname) AS locked_by_human,
                locked_at,
                IF(locked_at IS NOT NULL AND locked_at < DATE_SUB(NOW(), INTERVAL %d MINUTE), 1, 0) AS is_stale
                FROM %s_edit_mode as entity
                LEFT JOIN users ON (entity.locked_by = users.userid)
                WHERE entity_id = :entity_id',
            self::EXPIRATION_MINUTES,
            $this->Entity->entityType->value,
        );
        $req = $this->Db->prepare($sql);
        $req->bindParam(':entity_id', $this->Entity->id, PDO::PARAM_INT);
        $this->Db->execute($req);
        // don't use Db->fetch() because it's fine to return nothing
        return $req->fetch() ?: array();
    }

    /**
     * Add two failsafe if the entry stays locked for some reason (removal couldn't be fired on window unload)
     * 1. if it's same user, let them in anyway
     * 2. if the lock is older than 42 minutes, let them in too
     */
    public function isActive(): bool
    {
        $data = $this->readOne();
        if (empty($data)
            || $data['locked_by'] === $this->Entity->Users->userData['userid']
            || $data['is_stale']
        ) {
            return false;
        }
        return true;
    }

    public function canPatchOrExplode(Action $action): null
    {
        if ($this->isActive()) {
            $data = $this->readOne();
            // everyone can ...
            if ($action === Action::Pin
                || $action === Action::AccessKey
            ) {
                return null;
            }
            throw new ImproperActionException(sprintf(
                _('This entry is being edited by %s.'),
                $data['locked_by_human'],
            ));
        }
        return null;
    }

    public function activate(): bool
    {
        $this->Entity->canOrExplode('write');
        // destroy any leftover first: prevents inserting with same primary id (entity_id)
        $this->destroy();
        $sql = sprintf(
            'INSERT INTO %s_edit_mode (locked_by, entity_id) VALUES (:userid, :entity_id)',
            $this->Entity->entityType->value,
        );
        $req = $this->Db->prepare($sql);
        $req->bindParam(':userid', $this->Entity->Users->userData['userid'], PDO::PARAM_INT);
        $req->bindParam(':entity_id', $this->Entity->id, PDO::PARAM_INT);
        return $this->Db->execute($req);
    }

    public function destroy(): bool
    {
        $sql = sprintf(
            'DELETE FROM %1$s_edit_mode WHERE entity_id = :entity_id',
            $this->Entity->entityType->value,
        );
        $req = $this->Db->prepare($sql);
        $req->bindParam(':entity_id', $this->Entity->id, PDO::PARAM_INT);
        return $this->Db->execute($req);
    }
}
