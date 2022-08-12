<?php declare(strict_types=1);
/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

namespace Elabftw\Models;

use PDO;

/**
 * Read the unfinished steps of items or experiments to display in to-do list.
 * By default the unfinished steps of a user are returned.
 * $teamScoped provides a switch to return unfinished steps of the entire team.
 */
class UnfinishedSteps extends Steps
{
    public function __construct(AbstractEntity $Entity, private bool $teamScoped = false)
    {
        parent::__construct($Entity);
    }

    public function readOne(): array
    {
        // not used
        return array();
    }

    public function readAll(): array
    {
        $sql = 'SELECT entity.id, entity.title, stepst.finished, stepst.steps_body, stepst.steps_id
            FROM ' . $this->Entity->type . " as entity
            CROSS JOIN (
                SELECT item_id, finished,
                GROUP_CONCAT(entity_steps.body ORDER BY entity_steps.ordering SEPARATOR '|') AS steps_body,
                GROUP_CONCAT(entity_steps.id ORDER BY entity_steps.ordering SEPARATOR '|') AS steps_id
                FROM " . $this->Entity->type . '_steps as entity_steps
                WHERE finished = 0 GROUP BY item_id
            ) AS stepst ON (stepst.item_id = entity.id)';

        if ($this->Entity instanceof Experiments && $this->teamScoped) {
            $sql .= ' JOIN users2teams ON (users2teams.users_id = entity.userid AND users2teams.teams_id = :teamid)';
        }

        $sql .= $this->teamScoped ? $this->getTeamWhereClause() : ' WHERE entity.userid = :userid';

        $sql .= ' AND entity.state = ' . $this->Entity::STATE_NORMAL . ' GROUP BY entity.id ORDER BY entity.id DESC';

        $req = $this->Db->prepare($sql);
        $req->bindParam(':userid', $this->Entity->Users->userData['userid'], PDO::PARAM_INT);
        if ($this->teamScoped) {
            $req->bindParam(':teamid', $this->Entity->Users->team, PDO::PARAM_INT);
        }
        $this->Db->execute($req);

        return $this->cleanUpResult($req->fetchAll());
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

    private function getTeamWhereClause(): string
    {
        $teamgroupsOfUser = array_column((new TeamGroups($this->Entity->Users))->readGroupsFromUser(), 'id');
        $teamgroups = '';
        foreach ($teamgroupsOfUser as $teamgroup) {
            $teamgroups .= " OR entity.canread = $teamgroup";
        }

        return ' WHERE' . ($this->Entity instanceof Items ? ' entity.team = :teamid AND' : '') . " (
            entity.canread = 'public'
            OR entity.canread = 'organization'
            OR entity.canread = 'team'
            $teamgroups
            OR (entity.userid = :userid
                AND (
                    entity.canread = 'user'
                    OR entity.canread = 'useronly'
                )
            )
        )";
    }
}
