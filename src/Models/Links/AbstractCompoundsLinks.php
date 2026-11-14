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

use Elabftw\Elabftw\CompoundPermissions;
use Elabftw\Enums\Action;
use Elabftw\Enums\AccessType;
use Elabftw\Enums\State;
use Elabftw\Exceptions\ImproperActionException;
use Elabftw\Interfaces\QueryParamsInterface;
use Elabftw\Models\AbstractEntity;
use Elabftw\Models\AbstractRest;
use Elabftw\Models\Changelog;
use Elabftw\Models\GhsStatements;
use Elabftw\Models\Items;
use Elabftw\Models\ItemsTypes;
use Elabftw\Params\ContentParams;
use Elabftw\Traits\SetIdTrait;
use Override;
use PDO;

use function _;
use function array_column;
use function array_map;
use function sprintf;

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
        // like the links to entries, a linked compound the requester cannot read is not listed
        $sql = 'SELECT compound_id AS id, compounds.*
            FROM ' . $this->getTable() . ' AS main
            LEFT JOIN compounds ON compounds.id = main.compound_id
            WHERE main.entity_id = :entity_id AND compounds.state IN (:state_normal, :state_archived)'
            . new CompoundPermissions($this->Entity->Users)->getCanFilter('compounds');
        $req = $this->Db->prepare($sql);
        $req->bindParam(':entity_id', $this->Entity->id, PDO::PARAM_INT);
        $req->bindParam(':userid', $this->Entity->Users->userid, PDO::PARAM_INT);
        $req->bindValue(':state_normal', State::Normal->value, PDO::PARAM_INT);
        $req->bindValue(':state_archived', State::Archived->value, PDO::PARAM_INT);
        $this->Db->execute($req);
        $compounds = $req->fetchAll();

        // shown with the compound on the entry and in exports
        $statements = new GhsStatements()->readForCompounds(array_column($compounds, 'id'));
        return array_map(
            static fn(array $compound): array => $compound + array('ghs_statements' => $statements[$compound['id']] ?? array()),
            $compounds,
        );
    }

    #[Override]
    public function postAction(Action $action, array $reqBody): int
    {
        $this->Entity->canOrExplode(AccessType::Write);
        return match ($action) {
            Action::Create => $this->create(),
            default => throw new ImproperActionException('Invalid action for links create.'),
        };
    }

    // Copy Compounds from one entity to another
    public function duplicate(int $id, int $newId, bool $fromTemplate = false, bool $toTemplate = false): int
    {
        $sourceTable = $fromTemplate ? $this->getTemplateTable() : $this->getTable();
        $targetTable = $toTemplate ? $this->getTemplateTable() : $this->getTable();
        $sql = 'INSERT IGNORE INTO ' . $targetTable . ' (entity_id, compound_id)
            SELECT :new_id, compound_id
            FROM ' . $sourceTable . '
            WHERE entity_id = :old_id';
        $req = $this->Db->prepare($sql);
        $req->bindParam(':new_id', $newId, PDO::PARAM_INT);
        $req->bindParam(':old_id', $id, PDO::PARAM_INT);

        return (int) $this->Db->execute($req);
    }

    #[Override]
    public function destroy(bool $recursive = false): bool
    {
        $this->Entity->canOrExplode(AccessType::Write);
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
        $this->Entity->canOrExplode(AccessType::Write);
        new CompoundPermissions($this->Entity->Users)->canOrExplodeFromId($this->id ?? 0, AccessType::Read);
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

    /**
     * The changelog is readable by anyone who can read the entry, but the compound may be restricted:
     * only its id is recorded, and its name and CAS number stay behind its own permissions
     */
    private function createChangelog(Action $action = Action::Add): void
    {
        $verb = $action === Action::Destroy ? _('Removed') : _('Added');
        $Changelog = new Changelog($this->Entity);
        $Changelog->create(new ContentParams(
            'compounds',
            sprintf(_('%s link to compound with id: %d'), $verb, $this->id ?? 0),
        ));
    }
}
