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

use Elabftw\Enums\Action;
use Elabftw\Enums\State;
use Elabftw\Exceptions\ImproperActionException;
use Elabftw\Interfaces\QueryParamsInterface;
use Elabftw\Traits\SetIdTrait;
use Override;
use PDO;

/**
 * Compounds linking to entities
 */
abstract class AbstractCompoundsLinks extends AbstractRest
{
    use SetIdTrait;

    public function __construct(private AbstractEntity $Entity, ?int $id = null)
    {
        parent::__construct();
        // this field corresponds to the target id (link_id)
        $this->setId($id);
    }

    #[Override]
    public function getApiPath(): string
    {
        return sprintf('%s%d/%s/', $this->Entity->getApiPath(), $this->Entity->id ?? '', $this->getTable());
    }

    /**
     * Get links for an entity
     */
    #[Override]
    public function readAll(?QueryParamsInterface $queryParams = null): array
    {
        $sql = 'SELECT compound_id AS id, compounds.*
            FROM ' . $this->getTable() . ' AS main
            LEFT JOIN compounds ON compounds.id = main.compound_id
            WHERE main.entity_id = :entity_id AND compounds.state IN (:state_normal, :state_archived)';
        $req = $this->Db->prepare($sql);
        $req->bindParam(':entity_id', $this->Entity->id, PDO::PARAM_INT);
        $req->bindValue(':state_normal', State::Normal->value, PDO::PARAM_INT);
        $req->bindValue(':state_archived', State::Archived->value, PDO::PARAM_INT);
        $this->Db->execute($req);

        return $req->fetchAll();
    }

    #[Override]
    public function postAction(Action $action, array $reqBody): int
    {
        return match ($action) {
            Action::Create => $this->create(),
            //Action::Duplicate => $this->import(),
            default => throw new ImproperActionException('Invalid action for links create.'),
        };
    }

    /**
     * Copy the links from one entity to an other
     *
     * @param int $id The id of the original entity
     * @param int $newId The id of the new entity that will receive the links
     * @param bool $fromTpl do we duplicate from template?
     */
    public function duplicate(int $id, int $newId, $fromTpl = false): int
    {
        $table = $this->getTable();
        if ($fromTpl) {
            $table = 'compounds2experiments_templates';
            // TODO implement properly, right now items_types have no ui for compounds
            //$table = $this->getTemplateTable();
        }
        $sql = 'INSERT IGNORE INTO ' . $this->getTable() . ' (entity_id, compound_id)
            SELECT :new_id, compound_id
            FROM ' . $table . '
            WHERE entity_id = :old_id';
        $req = $this->Db->prepare($sql);
        $req->bindParam(':new_id', $newId, PDO::PARAM_INT);
        $req->bindParam(':old_id', $id, PDO::PARAM_INT);

        return (int) $this->Db->execute($req);
    }

    #[Override]
    public function destroy(): bool
    {
        $this->Entity->canOrExplode('write');
        $sql = 'DELETE FROM ' . $this->getTable() . ' WHERE compound_id = :compound_id AND entity_id = :entity_id';
        $req = $this->Db->prepare($sql);
        $req->bindParam(':compound_id', $this->id, PDO::PARAM_INT);
        $req->bindParam(':entity_id', $this->Entity->id, PDO::PARAM_INT);
        return $this->Db->execute($req);
    }

    public function destroyAll(): bool
    {
        $sql = 'DELETE FROM ' . $this->getTable() . ' WHERE entity_id = :entity_id';
        $req = $this->Db->prepare($sql);
        $req->bindParam(':entity_id', $this->Entity->id, PDO::PARAM_INT);
        return $this->Db->execute($req);
    }

    /**
     * Add a link to an entity
     * Links to Items are possible from all entities
     * Links to Experiments are only allowed from other Experiments and Items
     */
    public function create(): int
    {
        // use IGNORE to avoid failure due to a key constraint violations
        $sql = 'INSERT IGNORE INTO ' . $this->getTable() . ' (compound_id, entity_id) VALUES(:link_id, :item_id)';
        $req = $this->Db->prepare($sql);
        $req->bindParam(':item_id', $this->Entity->id, PDO::PARAM_INT);
        $req->bindParam(':link_id', $this->id, PDO::PARAM_INT);

        $this->Db->execute($req);

        return $this->id;
    }

    abstract protected function getTable(): string;
}
