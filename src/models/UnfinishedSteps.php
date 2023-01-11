<?php declare(strict_types=1);
/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

namespace Elabftw\Models;

use Elabftw\Elabftw\Db;
use Elabftw\Enums\Action;
use Elabftw\Enums\BasePermissions;
use Elabftw\Enums\State;
use Elabftw\Interfaces\RestInterface;
use Elabftw\Services\UsersHelper;
use PDO;

/**
 * Read the unfinished steps of items or experiments to display in to-do list.
 * By default the unfinished steps of a user are returned.
 * $teamScoped provides a switch to return unfinished steps of the entire team.
 */
class UnfinishedSteps implements RestInterface
{
    private Db $Db;

    public function __construct(private Users $Users, private bool $teamScoped = false)
    {
        $this->Db = Db::getConnection();
    }

    public function getPage(): string
    {
        return 'unfinished_steps';
    }

    public function postAction(Action $action, array $reqBody): int
    {
        return 0;
    }

    public function patch(Action $action, array $params): array
    {
        return $this->readAll();
    }

    public function readOne(): array
    {
        // not used
        return array();
    }

    public function readAll(): array
    {
        $experimentsSteps = $this->cleanUpResult($this->getSteps(AbstractEntity::TYPE_EXPERIMENTS));
        $itemsSteps = $this->cleanUpResult($this->getSteps(AbstractEntity::TYPE_ITEMS));
        return array('experiments' => $experimentsSteps, 'items' => $itemsSteps);
    }

    public function destroy(): bool
    {
        return false;
    }

    private function getSteps(string $model): array
    {
        $sql = 'SELECT entity.id, entity.title, stepst.finished, stepst.steps_body, stepst.steps_id
            FROM ' . $model . " as entity
            CROSS JOIN (
                SELECT item_id, finished,
                GROUP_CONCAT(entity_steps.body ORDER BY entity_steps.ordering SEPARATOR '|') AS steps_body,
                GROUP_CONCAT(entity_steps.id ORDER BY entity_steps.ordering SEPARATOR '|') AS steps_id
                FROM " . $model . '_steps as entity_steps
                WHERE finished = 0 GROUP BY item_id
            ) AS stepst ON (stepst.item_id = entity.id)';

        $sql .= ' JOIN users2teams ON (users2teams.users_id = entity.userid AND users2teams.teams_id = :teamid)';
        $sql .= $this->teamScoped ? $this->getTeamWhereClause($model) : ' WHERE entity.userid = :userid';

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

    private function getTeamWhereClause(string $model): string
    {
        $sql =  'WHERE ' . ($model === AbstractEntity::TYPE_ITEMS ? 'entity.team = :teamid ' : '1 = 1 ');
        // add pub/org/team filter
        $sqlPublicOrg = sprintf("( (JSON_EXTRACT(entity.canread, '$.base') = %d OR JSON_EXTRACT(entity.canread, '$.base') = %d) AND entity.userid = users2teams.users_id) OR ", BasePermissions::Full->value, BasePermissions::Organization->value);
        $sql .= sprintf(" AND  %s (JSON_EXTRACT(entity.canread, '$.base') = %d AND users2teams.users_id = entity.userid) OR (JSON_EXTRACT(entity.canread, '$.base') = %d ", $sqlPublicOrg, BasePermissions::MyTeams->value, BasePermissions::User->value);
        // add entities in useronly visibility only if we own them
        $sql .= sprintf(" OR (JSON_EXTRACT(entity.canread, '$.base') = %d AND entity.userid = :userid)", BasePermissions::UserOnly->value);
        // look for teams
        $UsersHelper = new UsersHelper((int) $this->Users->userData['userid']);
        $teamsOfUser = $UsersHelper->getTeamsIdFromUserid();
        foreach ($teamsOfUser as $team) {
            $sql .= sprintf(' OR (%d MEMBER OF (entity.canread->>"$.teams"))', $team);
        }
        // look for teamgroups
        $teamgroupsOfUser = array_column((new TeamGroups($this->Users))->readGroupsFromUser(), 'id');
        if (!empty($teamgroupsOfUser)) {
            foreach ($teamgroupsOfUser as $teamgroup) {
                $sql .= sprintf(' OR (%d MEMBER OF (entity.canread->>"$.teamgroups"))', $teamgroup);
            }
        }
        // look for our userid in users part of the json
        $sql .= ' OR (:userid MEMBER OF (entity.canread->>"$.users"))';
        $sql .= ')';

        return $sql;
    }
}
