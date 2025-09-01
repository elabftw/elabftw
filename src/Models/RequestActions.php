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

use Elabftw\AuditEvent\ActionRequested as AuditEventActionRequested;
use Elabftw\Enums\Action;
use Elabftw\Enums\RequestableAction;
use Elabftw\Enums\State;
use Elabftw\Exceptions\ImproperActionException;
use Elabftw\Interfaces\QueryParamsInterface;
use Elabftw\Models\Notifications\ActionRequested;
use Elabftw\Models\Users\Users;
use Elabftw\Params\ContentParams;
use Elabftw\Traits\SetIdTrait;
use Override;
use PDO;

/**
 * Request action for users
 */
final class RequestActions extends AbstractRest
{
    use SetIdTrait;

    public function __construct(protected Users $requester, protected AbstractEntity $entity, ?int $id = null)
    {
        parent::__construct();
        $this->setId($id);
    }

    #[Override]
    public function readAll(?QueryParamsInterface $queryParams = null): array
    {
        $sql = sprintf(
            'SELECT "%s" AS entity_page, id, created_at, requester_userid, target_userid, entity_id, action
                FROM %s_request_actions
                WHERE entity_id = :entity_id
                    AND state = :state
                ORDER BY created_at DESC
                LIMIT 100',
            $this->entity->entityType->toPage(),
            $this->entity->entityType->value,
        );
        $req = $this->Db->prepare($sql);
        $req->bindParam(':entity_id', $this->entity->id, PDO::PARAM_INT);
        $req->bindValue(':state', State::Normal->value, PDO::PARAM_INT);
        $this->Db->execute($req);

        return $req->fetchAll();
    }

    public function readAllFull(): array
    {
        return array_map(function (array $action): array {
            $Requester = new Users($action['requester_userid']);
            $action['requester_fullname'] = $Requester->userData['fullname'];
            $Target = new Users($action['target_userid']);
            $action['target_fullname'] = $Target->userData['fullname'];
            $action['description'] = RequestableAction::from($action['action'])->toHuman();
            $action['target'] = strtolower(RequestableAction::from($action['action'])->name);
            /** @psalm-suppress PossiblyNullArgument */
            $action['action'] = _(preg_replace('/([a-z])([A-Z])/', '${1} ${2}', RequestableAction::from($action['action'])->name));
            return $action;
        }, $this->readAll());
    }

    #[Override]
    public function readOne(): array
    {
        $sql = sprintf(
            'SELECT id, created_at, requester_userid, target_userid, entity_id, action, state
                FROM %s_request_actions
                WHERE id = :id',
            $this->entity->entityType->value
        );
        $req = $this->Db->prepare($sql);
        $req->bindParam(':id', $this->id, PDO::PARAM_INT);
        $this->Db->execute($req);

        return $this->Db->fetch($req);
    }

    #[Override]
    public function postAction(Action $action, array $reqBody): int
    {
        $sql = sprintf(
            'SELECT CAST(count(*) AS UNSIGNED) AS `count`
                FROM  %s_request_actions
                WHERE requester_userid = :requester_userid
                    AND target_userid = :target_userid
                    AND entity_id = :entity_id
                    AND action = :action
                    AND state = :state',
            $this->entity->entityType->value,
        );
        $req = $this->Db->prepare($sql);
        $req->bindParam(':requester_userid', $this->requester->userData['userid'], PDO::PARAM_INT);
        $req->bindParam(':target_userid', $reqBody['target_userid'], PDO::PARAM_INT);
        $req->bindParam(':entity_id', $this->entity->id, PDO::PARAM_INT);
        $req->bindParam(':action', $reqBody['target_action'], PDO::PARAM_INT);
        $req->bindValue(':state', State::Normal->value, PDO::PARAM_INT);
        $this->Db->execute($req);
        if ($req->fetchColumn() !== 0) {
            throw new ImproperActionException(_('This action has been requested already.'));
        }

        $sql = sprintf(
            'INSERT INTO %s_request_actions (requester_userid, target_userid, entity_id, action)
                VALUES (:requester_userid, :target_userid, :entity_id, :action)',
            $this->entity->entityType->value,
        );
        $req = $this->Db->prepare($sql);
        $req->bindParam(':requester_userid', $this->requester->userData['userid'], PDO::PARAM_INT);
        $req->bindParam(':target_userid', $reqBody['target_userid'], PDO::PARAM_INT);
        $req->bindParam(':entity_id', $this->entity->id, PDO::PARAM_INT);
        $req->bindParam(':action', $reqBody['target_action'], PDO::PARAM_INT);
        $this->Db->execute($req);
        $actionId = $this->Db->lastInsertId();

        $action = RequestableAction::from((int) $reqBody['target_action']);

        $Notifications = new ActionRequested(
            $this->requester,
            $action,
            $this->entity,
        );
        $Notifications->create((int) $reqBody['target_userid']);
        $event = new AuditEventActionRequested($this->requester->userData['userid'], (int) $reqBody['target_userid'], $this->entity->id, $this->entity->entityType, $action);
        AuditLogs::create($event);
        $changelogValue = sprintf('%s (target userid: %d)', $event->getBody(), (int) $reqBody['target_userid']);
        new Changelog($this->entity)->create(new ContentParams('action_requested', $changelogValue));

        return $actionId;
    }

    #[Override]
    public function getApiPath(): string
    {
        return sprintf('%s%d/request_actions/', $this->entity->getApiPath(), $this->entity->id ?? '');
    }

    public function remove(RequestableAction $action): bool
    {
        $sql = sprintf(
            'UPDATE %s_request_actions
                SET state = :state
                WHERE action = :action
                    AND target_userid = :userid
                    AND entity_id = :entity_id',
            $this->entity->entityType->value,
        );
        $req = $this->Db->prepare($sql);
        $req->bindValue(':state', State::Archived->value, PDO::PARAM_INT);
        $req->bindValue(':action', $action->value, PDO::PARAM_INT);
        $req->bindParam(':userid', $this->requester->userData['userid'], PDO::PARAM_INT);
        $req->bindParam(':entity_id', $this->entity->id, PDO::PARAM_INT);
        $res = $this->Db->execute($req);

        if ($res && $req->rowCount() > 0) {
            $changelogValue = sprintf('Action done: %s by user with ID %d', $action->toHuman(), $this->requester->userData['userid']);
            new Changelog($this->entity)->create(new ContentParams('action_done', $changelogValue));
        }

        return $res;
    }

    #[Override]
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
