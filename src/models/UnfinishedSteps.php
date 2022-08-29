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
use Elabftw\Interfaces\RestInterface;
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

        if ($model === AbstractEntity::TYPE_EXPERIMENTS && $this->teamScoped) {
            $sql .= ' JOIN users2teams ON (users2teams.users_id = entity.userid AND users2teams.teams_id = :teamid)';
        }
        $sql .= $this->teamScoped ? $this->getTeamWhereClause($model) : ' WHERE entity.userid = :userid';

        $sql .= ' AND entity.state = ' . AbstractEntity::STATE_NORMAL . ' GROUP BY entity.id ORDER BY entity.id DESC';
        $req = $this->Db->prepare($sql);
        $req->bindParam(':userid', $this->Users->userData['userid'], PDO::PARAM_INT);
        if ($this->teamScoped) {
            $req->bindParam(':teamid', $this->Users->team, PDO::PARAM_INT);
        }
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
        $teamgroupsOfUser = array_column((new TeamGroups($this->Users))->readGroupsFromUser(), 'id');
        $teamgroups = '';
        foreach ($teamgroupsOfUser as $teamgroup) {
            $teamgroups .= " OR entity.canread = $teamgroup";
        }

        return ' WHERE' . ($model === AbstractEntity::TYPE_ITEMS ? ' entity.team = :teamid AND' : '') . " (
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
