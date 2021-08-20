<?php declare(strict_types=1);
/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

namespace Elabftw\Models;

use Elabftw\Elabftw\ContentParams;
use Elabftw\Interfaces\ContentParamsInterface;
use PDO;

/**
 * All about unfinished steps
 */
class UnfinishedSteps
{
    public function __construct(public AbstractEntity $Entity, private ?strind $scope = null)
    {
        parent::__construct($Entity);
    }

    public function read(ContentParamsInterface $params): array
    {
        if ($params->getTarget() === 'all') {
            $whereClause = ' WHERE entity.userid = :userid';
        }
        if ($params->getTarget() === 'all_team') {
            $teamgroupsOfUser = array_column((new TeamGroups($this->Entity->Users))->readGroupsFromUser(), 'id');
            $teamgroups = '';
            foreach ($teamgroupsOfUser as $teamgroup) {
                $teamgroups .= " OR entity.canread = $teamgroup";
            }

            $whereClause = " WHERE entity.team = :teamid
                AND (
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

        $sql = $this->getReadAllSql($whereClause);

        $req = $this->Db->prepare($sql);
        $req->bindParam(':id', $this->Entity->id, PDO::PARAM_INT);
        if ($this->Entity->type === 'items') {
            $req->bindParam(':teamid', $this->Entity->Users->team, PDO::PARAM_INT);
        }
        $this->Db->execute($req);

        $res = $this->Db->fetchAll($req);

        return $this->cleanUpReadAllSQLResults($res);
    }

    /*
     * Provide SQL statement to get unfinished steps
     *
     * @param string $whereClause SQL WHERE clause
     */
    private function getReadAllSql(string $whereClause): string
    {
        $sql = 'SELECT entity.id, entity.title, stepst.finished, stepst.steps_body, stepst.steps_id
            FROM ' . $this->Entity->type . " as entity
            CROSS JOIN (
                SELECT item_id, finished,
                GROUP_CONCAT(entity_steps.body ORDER BY entity_steps.ordering SEPARATOR '|') AS steps_body,
                GROUP_CONCAT(entity_steps.id ORDER BY entity_steps.ordering SEPARATOR '|') AS steps_id
                FROM " . $this->Entity->type . '_steps as entity_steps
                WHERE finished = 0 GROUP BY item_id) AS stepst ON (stepst.item_id = entity.id)';
        $sql .= $whereClause;
        $sql .= ' GROUP BY entity.id ORDER BY entity.id DESC';
        return $sql;
    }

    /*
     * Clean up the read results so we get a nice array with entity id/title and steps with their id/body
     * use reference to edit in place
     *
     *@param array $res Unfinished steps SQL result array
     */
    private function cleanUpReadAllSQLResults(array $res): array
    {
        foreach ($res as &$entity) {
            $stepIDs = explode('|', $entity['steps_id']);
            $stepsBodies = explode('|', $entity['steps_body']);

            $entitySteps = array();
            foreach ($stepIDs as $key => $stepID) {
                $entitySteps[] = array($stepID, $stepsBodies[$key]);
            }
            $entity['steps'] = $entitySteps;s
            unset($entity['steps_body'], $entity['steps_id'], $entity['finished']);
        }

        return $res;
    }
}
