<?php declare(strict_types=1);
/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2024 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

namespace Elabftw\Models;

use Elabftw\Elabftw\Db;
use Elabftw\Enums\Action;
use Elabftw\Enums\RequestableAction;
use Elabftw\Enums\State;
use Elabftw\Exceptions\ImproperActionException;
use Elabftw\Interfaces\RestInterface;
use PDO;

/**
 * Request action for users
 */
class UserRequestActions implements RestInterface
{
    protected Db $Db;

    public function __construct(protected Users $requester)
    {
        $this->Db = Db::getConnection();
    }

    public function readAll(): array
    {
        $sql = sprintf('SELECT "experiments" AS entity_page, entity.title AS entity_title, experiments_request_actions.id, experiments_request_actions.created_at, requester_userid, target_userid, entity_id, action, experiments_request_actions.state
            FROM experiments_request_actions LEFT JOIN experiments AS entity ON (entity.id = experiments_request_actions.entity_id) WHERE target_userid = :userid AND experiments_request_actions.state = %d ORDER BY created_at DESC LIMIT 100', State::Normal->value);
        $req = $this->Db->prepare($sql);
        $req->bindParam(':userid', $this->requester->userData['userid'], PDO::PARAM_INT);
        $this->Db->execute($req);

        $experiments = $req->fetchAll();
        $sql = sprintf('SELECT "items" AS entity_page, entity.title AS entity_title, items_request_actions.id, items_request_actions.created_at, requester_userid, target_userid, entity_id, action, items_request_actions.state
            FROM items_request_actions LEFT JOIN items AS entity ON (entity.id = items_request_actions.entity_id) WHERE target_userid = :userid AND items_request_actions.state = %d ORDER BY created_at DESC LIMIT 100', State::Normal->value);
        $req = $this->Db->prepare($sql);
        $req->bindParam(':userid', $this->requester->userData['userid'], PDO::PARAM_INT);
        $this->Db->execute($req);

        return $experiments + $req->fetchAll();
    }

    public function readAllFull(): array
    {
        return array_map(function ($action) {
            $Requester = new Users($action['requester_userid']);
            $action['requester_firstname'] = $Requester->userData['firstname'];
            $action['action'] = RequestableAction::from($action['action'])->name;
            return $action;
        }, $this->readAll());
    }

    public function readOne(): array
    {
        throw new ImproperActionException('This endpoint does not allow targeting one request.');
    }

    public function postAction(Action $action, array $reqBody): int
    {
        throw new ImproperActionException('This endpoint does not allow creating a request.');
    }

    public function patch(Action $action, array $params): array
    {
        throw new ImproperActionException('No patch action for this endpoint.');
    }

    public function getPage(): string
    {
        return 'users/me/request_actions/';
    }

    public function destroy(): bool
    {
        throw new ImproperActionException('No delete action for this endpoint.');
    }
}
