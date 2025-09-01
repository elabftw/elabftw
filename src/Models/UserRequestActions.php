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

use Elabftw\Enums\EntityType;
use Elabftw\Enums\RequestableAction;
use Elabftw\Enums\State;
use Elabftw\Interfaces\QueryParamsInterface;
use Elabftw\Models\Users\Users;
use Override;
use PDO;

/**
 * Request action for users
 */
final class UserRequestActions extends AbstractRest
{
    public function __construct(protected Users $requester)
    {
        parent::__construct();
    }

    #[Override]
    public function readAll(?QueryParamsInterface $queryParams = null): array
    {
        $tables = array(
            array(
                'page' => EntityType::Experiments->toPage(),
                'entity_type' => EntityType::Experiments->value,
            ),
            array(
                'page' => EntityType::Items->toPage(),
                'entity_type' => EntityType::Items->value,
            ),
        );
        $sql = array();
        foreach ($tables as $table) {
            $sql[] = sprintf(
                '(SELECT "%1$s" AS entity_page, entity.title AS entity_title, %2$s_request_actions.id,
                        %2$s_request_actions.created_at, requester_userid, target_userid, entity_id, action,
                        %2$s_request_actions.state
                    FROM %2$s_request_actions
                    LEFT JOIN %2$s AS entity
                        ON entity.id = %2$s_request_actions.entity_id
                    WHERE target_userid = :userid
                        AND %2$s_request_actions.state = :state)',
                $table['page'],
                $table['entity_type'],
            );
        }
        $sql = implode(' UNION ', $sql) . ' ORDER BY created_at DESC LIMIT 100';
        $req = $this->Db->prepare($sql);
        $req->bindParam(':userid', $this->requester->userData['userid'], PDO::PARAM_INT);
        $req->bindValue(':state', State::Normal->value, PDO::PARAM_INT);
        $req->execute();
        return $req->fetchAll();
    }

    public function readAllFull(): array
    {
        return array_map(function (array $action): array {
            $Requester = new Users($action['requester_userid']);
            $action['requester_firstname'] = $Requester->userData['firstname'];
            $action['action'] = RequestableAction::from($action['action'])->toHuman();
            return $action;
        }, $this->readAll());
    }

    #[Override]
    public function getApiPath(): string
    {
        return 'api/v2/users/me/request_actions/';
    }
}
