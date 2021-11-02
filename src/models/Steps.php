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
use Elabftw\Elabftw\Db;
use Elabftw\Interfaces\ContentParamsInterface;
use Elabftw\Interfaces\CrudInterface;
use Elabftw\Traits\SortableTrait;
use PDO;

/**
 * All about the steps
 */
class Steps implements CrudInterface
{
    use SortableTrait;

    protected Db $Db;

    public function __construct(public AbstractEntity $Entity, private ?int $id = null)
    {
        $this->Db = Db::getConnection();
    }

    /**
     * Add a step
     *
     */
    public function create(ContentParamsInterface $params): int
    {
        $this->Entity->canOrExplode('write');
        // make sure the newly added step is at the bottom
        // count the number of steps and add 1 to be sure we're last
        $ordering = count($this->read(new ContentParams())) + 1;

        $sql = 'INSERT INTO ' . $this->Entity->type . '_steps (item_id, body, ordering) VALUES(:item_id, :content, :ordering)';
        $req = $this->Db->prepare($sql);
        $req->bindParam(':item_id', $this->Entity->id, PDO::PARAM_INT);
        $req->bindValue(':content', $params->getContent());
        $req->bindParam(':ordering', $ordering, PDO::PARAM_INT);
        $this->Db->execute($req);

        return $this->Db->lastInsertId();
    }

    /**
     * Import a step from a complete step array
     * Used when importing from zip archive (json)
     *
     * @param array<string, mixed> $step
     */
    public function import(array $step): void
    {
        $this->Entity->canOrExplode('write');

        $body = str_replace('|', ' ', $step['body']);
        $sql = 'INSERT INTO ' . $this->Entity->type . '_steps (item_id, body, ordering, finished, finished_time)
            VALUES(:item_id, :body, :ordering, :finished, :finished_time)';
        $req = $this->Db->prepare($sql);
        $req->bindParam(':item_id', $this->Entity->id, PDO::PARAM_INT);
        $req->bindParam(':body', $body);
        $req->bindParam(':ordering', $step['ordering']);
        $req->bindParam(':finished', $step['finished']);
        $req->bindParam(':finished_time', $step['finished_time']);
        $this->Db->execute($req);
    }

    public function read(ContentParamsInterface $params): array
    {
        $this->Entity->canOrExplode('read');

        $sql = 'SELECT * FROM ' . $this->Entity->type . '_steps WHERE item_id = :id ORDER BY ordering';
        $req = $this->Db->prepare($sql);
        $req->bindParam(':id', $this->Entity->id, PDO::PARAM_INT);
        $this->Db->execute($req);

        return $this->Db->fetchAll($req);
    }

    /**
     * Copy the steps from one entity to an other
     *
     * @param int $id The id of the original entity
     * @param int $newId The id of the new entity that will receive the steps
     * @param bool $fromTpl do we duplicate from template?
     */
    public function duplicate(int $id, int $newId, $fromTpl = false): void
    {
        $table = $this->Entity->type;
        if ($fromTpl) {
            $table = $this->Entity instanceof Experiments ? 'experiments_templates' : 'items_types';
        }
        $stepsql = 'SELECT body, ordering FROM ' . $table . '_steps WHERE item_id = :id';
        $stepreq = $this->Db->prepare($stepsql);
        $stepreq->bindParam(':id', $id, PDO::PARAM_INT);
        $this->Db->execute($stepreq);

        while ($steps = $stepreq->fetch()) {
            $sql = 'INSERT INTO ' . $this->Entity->type . '_steps (item_id, body, ordering) VALUES(:item_id, :body, :ordering)';
            $req = $this->Db->prepare($sql);
            $this->Db->execute($req, array(
                'item_id' => $newId,
                'body' => $steps['body'],
                'ordering' => $steps['ordering'],
            ));
        }
    }

    public function update(ContentParamsInterface $params): bool
    {
        $this->Entity->canOrExplode('write');
        if ($params->getTarget() === 'body') {
            return $this->updateBody($params->getContent());
        }
        if ($params->getTarget() === 'finished') {
            return $this->toggleFinished();
        }
        return false;
    }

    public function destroy(): bool
    {
        $this->Entity->canOrExplode('write');

        $sql = 'DELETE FROM ' . $this->Entity->type . '_steps WHERE id = :id AND item_id = :item_id';
        $req = $this->Db->prepare($sql);
        $req->bindParam(':id', $this->id, PDO::PARAM_INT);
        $req->bindParam(':item_id', $this->Entity->id, PDO::PARAM_INT);
        return $this->Db->execute($req);
    }

    private function toggleFinished(): bool
    {
        $sql = 'UPDATE ' . $this->Entity->type . '_steps SET finished = !finished,
            finished_time = NOW() WHERE id = :id AND item_id = :item_id';
        $req = $this->Db->prepare($sql);
        $req->bindParam(':id', $this->id, PDO::PARAM_INT);
        $req->bindParam(':item_id', $this->Entity->id, PDO::PARAM_INT);
        return $this->Db->execute($req);
    }

    private function updateBody(string $content): bool
    {
        $sql = 'UPDATE ' . $this->Entity->type . '_steps SET body = :content WHERE id = :id AND item_id = :item_id';
        $req = $this->Db->prepare($sql);
        $req->bindParam(':content', $content, PDO::PARAM_STR);
        $req->bindParam(':id', $this->id, PDO::PARAM_INT);
        $req->bindParam(':item_id', $this->Entity->id, PDO::PARAM_INT);
        return $this->Db->execute($req);
    }
}
