<?php

/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2024 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

declare(strict_types=1);

namespace Elabftw\Models\Links;

use Elabftw\Enums\Action;
use Elabftw\Enums\State;
use Elabftw\Exceptions\ImproperActionException;
use Elabftw\Interfaces\QueryParamsInterface;
use Elabftw\Models\AbstractEntity;
use Elabftw\Models\AbstractRest;
use Elabftw\Models\Changelog;
use Elabftw\Models\Items;
use Elabftw\Models\ItemsTypes;
use Elabftw\Params\ContentParams;
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
        $this->Entity->canOrExplode('write');
        return match ($action) {
            Action::Create => $this->create(),
            default => throw new ImproperActionException('Invalid action for links create.'),
        };
    }

    // Copy Compounds from one entity to another
    public function duplicate(int $id, int $newId, bool $fromTemplate = false): int
    {
        $table = $fromTemplate ? $this->getTemplateTable() : $this->getTable();
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
        $res = $this->Db->execute($req);
        if ($res && $req->rowCount() > 0) {
            $this->Entity->touch();
            $this->createChangelog(Action::Destroy);
        }
        return $res;
    }

    // Add a compound to an entity
    public function create(): int
    {
        $this->Entity->canOrExplode('write');
        // use IGNORE to avoid failure due to a key constraint violations
        $sql = 'INSERT IGNORE INTO ' . $this->getTable() . ' (compound_id, entity_id) VALUES(:link_id, :item_id)';
        $req = $this->Db->prepare($sql);
        $req->bindParam(':item_id', $this->Entity->id, PDO::PARAM_INT);
        $req->bindParam(':link_id', $this->id, PDO::PARAM_INT);

        $res = $this->Db->execute($req);
        if ($res && $req->rowCount() > 0) {
            $this->Entity->touch();
            $this->createChangelog();
        }
        return $this->id;
    }

    abstract protected function getTable(): string;

    protected function getTemplateTable(): string
    {
        if ($this->Entity instanceof Items || $this->Entity instanceof ItemsTypes) {
            return 'compounds2items_types';
        }
        return 'compounds2experiments_templates';
    }

    private function createChangelog(Action $action = Action::Add): void
    {
        $info = $this->getCompoundInfo();
        $verb = $action === Action::Destroy ? _('Removed') : _('Added');
        $Changelog = new Changelog($this->Entity);
        $Changelog->create(new ContentParams(
            'compounds',
            sprintf(_('%s link to compound: %s (CAS Number: %s) with id: %d'), $verb, $info['name'], $info['cas_number'], $this->id ?? 0),
        ));
    }

    private function getCompoundInfo(): array
    {
        $sql = 'SELECT name, cas_number FROM compounds WHERE id = :id LIMIT 1';
        $req = $this->Db->prepare($sql);
        $req->bindParam(':id', $this->id, PDO::PARAM_INT);
        $this->Db->execute($req);
        return $this->Db->fetch($req);
    }
}
