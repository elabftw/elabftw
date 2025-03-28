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
use Elabftw\Enums\EntityType;
use Elabftw\Enums\RequestableAction;
use Elabftw\Exceptions\ImproperActionException;
use PDO;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;

use function sprintf;

/**
 * For dealing with exclusive edit mode aka write lock
 */
final class ExclusiveEditMode
{
    public array $dataArr = array();

    public bool $isActive = false;

    private Db $Db;

    public function __construct(private AbstractEntity $Entity)
    {
        $this->Db = Db::getConnection();
    }

    public function readOne(): array
    {
        $sql = sprintf(
            'SELECT locked_by,
                CONCAT(users.firstname, " ", users.lastname) AS fullname,
                locked_at
                FROM %1$s_edit_mode as entity
                LEFT JOIN users ON (entity.locked_by = users.userid)
                WHERE %1$s_id = :id',
            $this->Entity->entityType->value,
        );
        $req = $this->Db->prepare($sql);
        $req->bindParam(':id', $this->Entity->id, PDO::PARAM_INT);
        $this->Db->execute($req);
        $this->dataArr = $req->fetch() ?: array();
        if (!empty($this->dataArr)) {
            $this->isActive = true;
        }
        return $this->dataArr;
    }

    public function gatekeeper(): ?RedirectResponse
    {
        if ($this->isActive
            && $this->Entity->Users->userid !== $this->dataArr['locked_by']
        ) {
            /** @psalm-suppress PossiblyNullArgument */
            return new RedirectResponse(sprintf(
                '%s%sid=%d',
                $this->Entity->entityType->toPage(),
                $this->Entity->entityType === EntityType::Templates
                    ? '&mode=view&template'
                    : '?mode=view&',
                $this->Entity->id,
            ), Response::HTTP_SEE_OTHER);
        }
        return null;
    }

    public function setExclusiveMode(): void
    {
        $this->create();
        $this->Entity->entityData['exclusive_edit_mode'] = $this->dataArr;
    }

    public function toggle(): bool
    {
        if ($this->isActive) {
            return $this->destroy();
        }
        return $this->create();
    }

    public function canPatchOrExplode(Action $action): void
    {
        if ($this->isActive) {
            // only user who locked can do everything
            if ($this->Entity->Users->userid === $this->dataArr['locked_by']) {
                return;
            }
            // everyone can ...
            if ($action === Action::Pin
                || $action === Action::AccessKey
            ) {
                return;
            }
            if ($action === Action::ExclusiveEditMode
                && $this->Entity->Users->isAdminOf($this->dataArr['locked_by'])
            ) {
                return;
            }
            throw new ImproperActionException(sprintf(
                _('This entry is being edited by %s.'),
                $this->dataArr['fullname'],
            ));
        }
    }

    private function create(): bool
    {
        $this->Entity->canOrExplode('write');
        $sql = sprintf(
            'INSERT INTO %1$s_edit_mode (locked_by, %1$s_id, locked_at) VALUES (:userid, :entityId, NOW())',
            $this->Entity->entityType->value,
        );
        $req = $this->Db->prepare($sql);
        $req->bindParam(':userid', $this->Entity->Users->userData['userid'], PDO::PARAM_INT);
        $req->bindParam(':entityId', $this->Entity->id, PDO::PARAM_INT);
        $this->Db->execute($req);
        $res = $req->rowCount() === 1;
        if ($res) {
            $this->readOne();
        }
        return $res;
    }

    private function destroy(): bool
    {
        $sql = sprintf(
            'DELETE FROM %1$s_edit_mode
                WHERE %1$s_id = :entityId',
            $this->Entity->entityType->value,
        );
        $req = $this->Db->prepare($sql);
        $req->bindParam(':entityId', $this->Entity->id, PDO::PARAM_INT);
        $this->Db->execute($req);
        $res = $req->rowCount() === 1;
        if ($res) {
            $this->dataArr = array();
            $this->isActive = false;
            // remove potential requests
            (new RequestActions($this->Entity->Users, $this->Entity))
                ->remove(RequestableAction::RemoveExclusiveEditMode);
        }
        return $res;
    }
}
