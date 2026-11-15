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

use Elabftw\Elabftw\EntitySqlBuilder;
use Elabftw\Enums\EntityType;
use Elabftw\Enums\State;
use Elabftw\Interfaces\QueryParamsInterface;
use Elabftw\Models\Users\Users;
use Override;
use PDO;

use function array_map;
use function json_decode;
use function sprintf;
use function usort;

use const JSON_THROW_ON_ERROR;

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
        // JSON_ARRAYAGG has no length limit, unlike GROUP_CONCAT (group_concat_max_len), and bodies can contain any character
        $sql = 'SELECT entity.id, entity.title, stepst.steps
            FROM ' . $model->value . " as entity
            CROSS JOIN (
                SELECT item_id,
                JSON_ARRAYAGG(JSON_OBJECT('id', entity_steps.id, 'body', entity_steps.body, 'ordering', entity_steps.ordering)) AS steps
                FROM " . $model->value . '_steps as entity_steps
                WHERE finished = 0 GROUP BY item_id
            ) AS stepst ON (stepst.item_id = entity.id)';

        $sql .= ' JOIN users2teams ON (users2teams.users_id = entity.userid AND users2teams.teams_id = :teamid)';
        $sql .= ' WHERE ' . ($this->teamScoped ? '1 = 1' : 'entity.userid = :userid');
        $sql .= new EntitySqlBuilder($model->toInstance($this->Users))->getCanFilter('canread');
        if ($this->teamScoped) {
            $sql .= ' AND entity.team = :teamid';
        }

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
            $steps = json_decode($entity['steps'], true, 512, JSON_THROW_ON_ERROR);
            // JSON_ARRAYAGG doesn't take an ORDER BY
            usort($steps, static fn(array $a, array $b): int => array($a['ordering'], $a['id']) <=> array($b['ordering'], $b['id']));
            // keep the previous output format: list of [id (as string), body]
            $entity['steps'] = array_map(static fn(array $step): array => array((string) $step['id'], $step['body'] ?? ''), $steps);
        }

        return $res;
    }
}
