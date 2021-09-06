<?php declare(strict_types=1);
/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

namespace Elabftw\Models;

use Elabftw\Interfaces\ContentParamsInterface;
use PDO;

/**
 * Read the unfinished steps of items or experiments to display in todo-list
 * For items unfinished steps of the user or the team are returned
 * For experiments only unfinished steps of the user are returned
 */
class UnfinishedSteps extends Steps
{
    public function __construct(public AbstractEntity $Entity)
    {
        parent::__construct($Entity);
    }

    public function read(ContentParamsInterface $params): array
    {
        $whereClause = ' WHERE entity.userid = :userid';

        if ($params->getExtra('scope') === 'team') {
            $teamgroupsOfUser = array_column((new TeamGroups($this->Entity->Users))->readGroupsFromUser(), 'id');
            $teamgroups = '';
            foreach ($teamgroupsOfUser as $teamgroup) {
                $teamgroups .= " OR entity.canread = $teamgroup";
            }

            $whereClause = ' WHERE' . ($this->Entity->type === 'items' ? ' entity.team = :teamid AND' : '') .
                " (
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

        $sql = 'SELECT entity.id, entity.title, stepst.finished, stepst.steps_body, stepst.steps_id
            FROM ' . $this->Entity->type . " as entity
            CROSS JOIN (
                SELECT item_id, finished,
                GROUP_CONCAT(entity_steps.body ORDER BY entity_steps.ordering SEPARATOR '|') AS steps_body,
                GROUP_CONCAT(entity_steps.id ORDER BY entity_steps.ordering SEPARATOR '|') AS steps_id
                FROM " . $this->Entity->type . '_steps as entity_steps
                WHERE finished = 0 GROUP BY item_id) AS stepst ON (stepst.item_id = entity.id)';

        if ($this->Entity->type === 'experiments'
            && $params->getExtra('scope') === 'team') {
            $sql .= 'JOIN users2teams ON (users2teams.users_id = entity.userid AND users2teams.teams_id = :teamid)';
        }

        $sql .= $whereClause;
        $sql .= ' GROUP BY entity.id ORDER BY entity.id DESC';

        $req = $this->Db->prepare($sql);
        $req->bindParam(':userid', $this->Entity->Users->userData['userid'], PDO::PARAM_INT);
        if ($params->getExtra('scope') === 'team') {
            $req->bindParam(':teamid', $this->Entity->Users->team, PDO::PARAM_INT);
        }
        $this->Db->execute($req);

        $res = $this->Db->fetchAll($req);

        return $this->cleanUpResult($res);
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
}
