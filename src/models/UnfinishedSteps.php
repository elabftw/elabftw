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

use Elabftw\Enums\BasePermissions;
use Elabftw\Enums\EntityType;
use Elabftw\Enums\State;
use Elabftw\Interfaces\QueryParamsInterface;
use Elabftw\Services\UsersHelper;
use Override;
use PDO;

/**
 * Read the unfinished steps of items or experiments to display in to-do list.
 * By default the unfinished steps of a user are returned.
 * $teamScoped provides a switch to return unfinished steps of the entire team.
 */
final class UnfinishedSteps extends AbstractRest
{
    public function __construct(private Users $Users, private bool $teamScoped = false)
    {
        parent::__construct();
    }

    #[Override]
    public function getApiPath(): string
    {
        return 'api/v2/unfinished_steps/';
    }

    #[Override]
    public function readAll(?QueryParamsInterface $queryParams = null): array
    {
        $experimentsSteps = $this->cleanUpResult($this->getSteps(EntityType::Experiments));
        $itemsSteps = $this->cleanUpResult($this->getSteps(EntityType::Items));
        return array('experiments' => $experimentsSteps, 'items' => $itemsSteps);
    }

    private function getSteps(EntityType $model): array
    {
        $sql = 'SELECT entity.id, entity.title, stepst.finished, stepst.steps_body, stepst.steps_id
            FROM ' . $model->value . " as entity
            CROSS JOIN (
                SELECT item_id, finished,
                GROUP_CONCAT(entity_steps.body ORDER BY entity_steps.ordering SEPARATOR '|') AS steps_body,
                GROUP_CONCAT(entity_steps.id ORDER BY entity_steps.ordering SEPARATOR '|') AS steps_id
                FROM " . $model->value . '_steps as entity_steps
                WHERE finished = 0 GROUP BY item_id
            ) AS stepst ON (stepst.item_id = entity.id)';

        $sql .= ' JOIN users2teams ON (users2teams.users_id = entity.userid AND users2teams.teams_id = :teamid)';
        $sql .= ' WHERE ' . ($this->teamScoped ? $this->getTeamWhereClause($model) : 'entity.userid = :userid');

        $sql .= sprintf(' AND entity.state = %d GROUP BY entity.id ORDER BY entity.id DESC', State::Normal->value);
        $req = $this->Db->prepare($sql);
        $req->bindParam(':userid', $this->Users->userData['userid'], PDO::PARAM_INT);
        $req->bindParam(':teamid', $this->Users->team, PDO::PARAM_INT);
        $this->Db->execute($req);

        return $req->fetchAll();
    }

    /*
     * Clean up the read result so we get a nice array with entity id/title and steps with their id/body
     * use reference to edit in place
     *
     *@param array $res Unfinished steps SQL result array
     */
    private function cleanUpResult(array $res): array
    {
        foreach ($res as &$entity) {
            $stepIDs = explode('|', $entity['steps_id']);
            $stepsBodies = explode('|', $entity['steps_body']);

            $entitySteps = array();
            foreach ($stepIDs as $key => $stepID) {
                $entitySteps[] = array($stepID, $stepsBodies[$key]);
            }
            $entity['steps'] = $entitySteps;
            unset($entity['steps_body'], $entity['steps_id'], $entity['finished']);
        }

        return $res;
    }

    private function getTeamWhereClause(EntityType $model): string
    {
        // add team id filter for items + pub/org visibility filter
        $sql = sprintf(
            "%s AND (
                (JSON_EXTRACT(entity.canread, '$.base') = %d OR JSON_EXTRACT(entity.canread, '$.base') = %d)
                AND users2teams.users_id = entity.userid
            )",
            $model === EntityType::Items ? 'entity.team = :teamid' : '1 = 1',
            BasePermissions::Full->value,
            BasePermissions::Organization->value,
        );

        // add team filter
        $sql .= sprintf(
            " OR (JSON_EXTRACT(entity.canread, '$.base') = %d AND users2teams.users_id = entity.userid)",
            BasePermissions::Team->value,
        );

        // add user filter
        $sql .= sprintf(
            " OR JSON_EXTRACT(entity.canread, '$.base') = %d ",
            BasePermissions::User->value,
        );

        // add entities in useronly visibility only if we own them
        $sql .= sprintf(
            " OR (JSON_EXTRACT(entity.canread, '$.base') = %d AND entity.userid = :userid)",
            BasePermissions::UserOnly->value,
        );

        // look for teams
        $UsersHelper = new UsersHelper($this->Users->userData['userid']);
        $teamsOfUser = $UsersHelper->getTeamsIdFromUserid();
        foreach ($teamsOfUser as $team) {
            $sql .= sprintf(
                ' OR (%d MEMBER OF (entity.canread->>"$.teams"))',
                $team,
            );
        }

        // look for teamgroups
        $teamgroupsOfUser = array_column((new TeamGroups($this->Users))->readGroupsFromUser(), 'id');
        if (!empty($teamgroupsOfUser)) {
            foreach ($teamgroupsOfUser as $teamgroup) {
                $sql .= sprintf(
                    ' OR (%d MEMBER OF (entity.canread->>"$.teamgroups"))',
                    $teamgroup,
                );
            }
        }

        // look for our userid in users part of the json
        $sql .= ' OR (:userid MEMBER OF (entity.canread->>"$.users"))';

        return sprintf('(%s)', $sql);
    }
}
