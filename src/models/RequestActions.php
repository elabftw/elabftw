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

use Elabftw\Elabftw\Db;
use Elabftw\Enums\Action;
use Elabftw\Enums\RequestableAction;
use Elabftw\Enums\State;
use Elabftw\Exceptions\ImproperActionException;
use Elabftw\Interfaces\RestInterface;
use Elabftw\Traits\SetIdTrait;

use PDO;

/**
 * Request action for users
 */
class RequestActions implements RestInterface
{
    use SetIdTrait;

    protected Db $Db;

    public function __construct(protected Users $requester, protected AbstractEntity $entity, ?int $id = null)
    {
        $this->setId($id);
        $this->Db = Db::getConnection();
    }

    public function readAll(): array
    {
        $sql = sprintf('SELECT "%s" AS entity_page, id, created_at, requester_userid, target_userid, entity_id, action
            FROM %s_request_actions WHERE entity_id = :entity_id AND state = %d ORDER BY created_at DESC LIMIT 100', $this->entity->page, $this->entity->entityType->value, State::Normal->value);
        $req = $this->Db->prepare($sql);
        $req->bindParam(':entity_id', $this->entity->id, PDO::PARAM_INT);
        $this->Db->execute($req);

        return $req->fetchAll();
    }

    public function readAllFull(): array
    {
        return array_map(function ($action) {
            $Requester = new Users($action['requester_userid']);
            $action['requester_fullname'] = $Requester->userData['fullname'];
            $Target = new Users($action['target_userid']);
            $action['target_fullname'] = $Target->userData['fullname'];
            $action['action'] = RequestableAction::from($action['action'])->name;
            return $action;
        }, $this->readAll());
    }

    public function readOne(): array
    {
        $sql = sprintf('SELECT id, created_at, requester_userid, target_userid, entity_id, action, state
            FROM %s_request_actions WHERE id = :id', $this->entity->entityType->value);
        $req = $this->Db->prepare($sql);
        $req->bindParam(':id', $this->id, PDO::PARAM_INT);
        $this->Db->execute($req);

        return $this->Db->fetch($req);
    }

    public function postAction(Action $action, array $reqBody): int
    {
        $sql = sprintf('INSERT INTO %s_request_actions (requester_userid, target_userid, entity_id, action)
            VALUES (:requester_userid, :target_userid, :entity_id, :action)', $this->entity->entityType->value);
        $req = $this->Db->prepare($sql);
        $req->bindParam(':requester_userid', $this->requester->userData['userid'], PDO::PARAM_INT);
        $req->bindParam(':target_userid', $reqBody['target_userid'], PDO::PARAM_INT);
        $req->bindParam(':entity_id', $this->entity->id, PDO::PARAM_INT);
        $req->bindParam(':action', $reqBody['target_action'], PDO::PARAM_INT);
        $this->Db->execute($req);

        return $this->Db->lastInsertId();
    }

    public function patch(Action $action, array $params): array
    {
        throw new ImproperActionException('No patch action for this endpoint.');
    }

    public function getPage(): string
    {
        return sprintf('%s/%d/request_actions/', $this->entity->type, $this->entity->id ?? '');
    }

    public function remove(RequestableAction $action): bool
    {
        $sql = sprintf(
            'UPDATE %s_request_actions SET state = %d WHERE action = :action AND target_userid = :userid',
            $this->entity->entityType->value,
            State::Archived->value,
        );
        $req = $this->Db->prepare($sql);
        $req->bindValue(':action', $action->value, PDO::PARAM_INT);
        $req->bindParam(':userid', $this->requester->userData['userid'], PDO::PARAM_INT);
        return $this->Db->execute($req);
    }

    public function destroy(): bool
    {
        $sql = sprintf(
            'DELETE FROM %s_request_actions WHERE id = :id
            AND (target_userid = :target_userid OR requester_userid = :requester_userid)',
            $this->entity->entityType->value
        );
        $req = $this->Db->prepare($sql);
        $req->bindParam(':id', $this->id, PDO::PARAM_INT);
        $req->bindParam(':requester_userid', $this->requester->userData['userid'], PDO::PARAM_INT);
        $req->bindParam(':target_userid', $this->requester->userData['userid'], PDO::PARAM_INT);
        return $this->Db->execute($req);
    }
}
