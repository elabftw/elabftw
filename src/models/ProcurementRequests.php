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
use Elabftw\Elabftw\ProcurementRequestParams;
use Elabftw\Enums\Action;
use Elabftw\Enums\Currency;
use Elabftw\Enums\ProcurementState;
use Elabftw\Exceptions\ImproperActionException;
use Elabftw\Interfaces\RestInterface;
use Elabftw\Services\TeamsHelper;
use Elabftw\Traits\SetIdTrait;
use PDO;
use RuntimeException;

/**
 * Procurement requests are purchase orders in a team
 */
class ProcurementRequests implements RestInterface
{
    use SetIdTrait;

    protected Db $Db;

    public function __construct(protected Teams $Teams, ?int $id = null)
    {
        $this->Db = Db::getConnection();
        $this->setId($id);
    }

    public function readAll(): array
    {
        $sql = "SELECT
            CONCAT(users.firstname, ' ', users.lastname) AS requester_fullname,
            pr.id, pr.created_at, pr.team, pr.requester_userid, pr.entity_id, pr.qty_ordered, pr.qty_received,
            pr.body, pr.quote, pr.email_sent, pr.state, items.title AS entity_title,
            pr.qty_ordered * items.proc_price_tax AS total,
            items.proc_currency, items.proc_pack_qty, items.proc_price_notax, items.proc_price_tax
            FROM procurement_requests AS pr
            LEFT JOIN users ON (pr.requester_userid = users.userid)
            LEFT JOIN items ON (pr.entity_id = items.id)
            WHERE pr.team = :team
            ORDER BY pr.state, pr.created_at DESC LIMIT 256";
        $req = $this->Db->prepare($sql);
        $req->bindParam(':team', $this->Teams->id, PDO::PARAM_INT);
        $this->Db->execute($req);
        return array_map(function ($request) {
            $ProcurementState = ProcurementState::from($request['state']);
            $request['state_human'] = $ProcurementState->toHuman();
            $Currency = Currency::from($request['proc_currency']);
            $request['currency_symbol'] = $Currency->toSymbol();
            $request['currency_human'] = $Currency->toHuman();
            return $request;
        }, $req->fetchAll());
    }

    public function readOne(): array
    {
        $sql = 'SELECT id, created_at, team, requester_userid, entity_id, qty_ordered, qty_received, body, quote, email_sent, state
            FROM procurement_requests WHERE id = :id';
        $req = $this->Db->prepare($sql);
        $req->bindParam(':id', $this->id, PDO::PARAM_INT);
        $this->Db->execute($req);

        return $this->Db->fetch($req);
    }

    public function readActiveForEntity(int $entityId): array
    {
        $sql = "SELECT CONCAT(users.firstname, ' ', users.lastname) AS requester_fullname, pr.id, pr.created_at, pr.team, pr.requester_userid, pr.entity_id, pr.qty_ordered, pr.body, pr.quote, pr.email_sent, pr.state
            FROM procurement_requests AS pr LEFT JOIN users ON (requester_userid = users.userid) WHERE entity_id = :entity_id AND state NOT IN (:state_received, :state_archived)";
        $req = $this->Db->prepare($sql);
        $req->bindParam(':entity_id', $entityId, PDO::PARAM_INT);
        $req->bindValue(':state_received', ProcurementState::Received->value, PDO::PARAM_INT);
        $req->bindValue(':state_archived', ProcurementState::Archived->value, PDO::PARAM_INT);
        $this->Db->execute($req);

        return $req->fetchAll();
    }

    public function postAction(Action $action, array $reqBody): int
    {
        $sql = 'INSERT INTO procurement_requests (team, requester_userid, entity_id, qty_ordered, body, quote, state)
            VALUES (:team, :requester_userid, :entity_id, :qty_ordered, :body, :quote, :state)';
        $req = $this->Db->prepare($sql);
        $req->bindParam(':team', $this->Teams->id, PDO::PARAM_INT);
        $req->bindParam(':requester_userid', $this->Teams->Users->userData['userid'], PDO::PARAM_INT);
        $req->bindParam(':entity_id', $reqBody['entity_id'], PDO::PARAM_INT);
        $req->bindParam(':qty_ordered', $reqBody['qty_ordered'], PDO::PARAM_INT);
        $req->bindParam(':body', $reqBody['body']);
        $req->bindParam(':quote', $reqBody['quote'], PDO::PARAM_INT);
        $req->bindValue(':state', ProcurementState::Pending->value, PDO::PARAM_INT);
        $this->Db->execute($req);

        return $this->Db->lastInsertId();
    }

    public function patch(Action $action, array $params): array
    {
        $this->canWriteOrExplode();
        // TODO hooks: received state set qty_recievde to qty_ordered
        unset($params['action']);
        foreach ($params as $key => $value) {
            $this->update(new ProcurementRequestParams($key, (string) $value));
        }
        return $this->readOne();
    }

    public function getApiPath(): string
    {
        return 'api/v2/teams/current/procurement_requests/';
    }

    // destroy is soft delete to prevent destructive actions on procurement requests so we can trust its log
    public function destroy(): bool
    {
        $this->canWriteOrExplode();
        return $this->update(new ProcurementRequestParams('state', (string) ProcurementState::Cancelled->value));
    }

    private function update(ProcurementRequestParams $params): bool
    {
        $sql = 'UPDATE procurement_requests SET ' . $params->getColumn() . ' = :value WHERE id = :id';
        $req = $this->Db->prepare($sql);
        $req->bindValue(':value', $params->getContent());
        $req->bindParam(':id', $this->id, PDO::PARAM_INT);
        return $this->Db->execute($req);
    }

    private function canWriteOrExplode(): void
    {
        $TeamsHelper = new TeamsHelper($this->Teams->id ?? throw new RuntimeException('Team has no id!'));
        if ($TeamsHelper->isUserInTeam($this->Teams->Users->userData['userid']) === false) {
            throw new ImproperActionException('Cannot delete from a team you do not belong in.');
        }
    }
}
