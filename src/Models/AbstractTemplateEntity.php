<?php

/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2022 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

declare(strict_types=1);

namespace Elabftw\Models;

use Elabftw\Elabftw\Metadata;
use Elabftw\Elabftw\MetadataHelpers;
use Elabftw\Enums\BasePermissions;
use Elabftw\Enums\AccessType;
use Elabftw\Enums\Action;
use Elabftw\Enums\EntityType;
use Elabftw\Enums\State;
use Elabftw\Exceptions\ForbiddenException;
use Elabftw\Exceptions\ImproperActionException;
use Elabftw\Exceptions\ResourceNotFoundException;
use Elabftw\Exceptions\UnprocessableContentException;
use Elabftw\Factories\LinksFactory;
use Override;
use PDO;
use Throwable;

use function _;
use function array_column;
use function array_diff;
use function is_int;
use function json_decode;

/**
 * An entity like Templates or ItemsTypes. Template as opposed to Concrete: Experiments and Items
 */
abstract class AbstractTemplateEntity extends AbstractEntity
{
    #[Override]
    public function patch(Action $action, array $params): array
    {
        if ($action !== Action::UpdateEntries) {
            return parent::patch($action, $params);
        }
        $this->canOrExplode(AccessType::Write);
        $this->ExclusiveEditMode->canPatchOrExplode($action);
        if ($this->isReadOnly || $this->entityData['state'] !== State::Normal->value) {
            throw new UnprocessableContentException(_('Cannot update entries from a read-only template.'));
        }

        return $this->updateEntries();
    }

    /**
     * Get an id of an existing one or create it and get its id
     */
    public function getIdempotentIdFromTitle(string $title): int
    {
        $sql = 'SELECT id
            FROM ' . $this->entityType->value . ' WHERE title = :title AND team = :team AND state = :state';
        $req = $this->Db->prepare($sql);
        $req->bindParam(':title', $title);
        $req->bindParam(':team', $this->Users->team, PDO::PARAM_INT);
        $req->bindValue(':state', State::Normal->value, PDO::PARAM_INT);
        $this->Db->execute($req);
        $res = $req->fetch(PDO::FETCH_COLUMN);
        if (!is_int($res)) {
            return $this->create(title: $title);
        }
        return $res;
    }

    #[Override]
    public function readOne(): array
    {
        $this->entityData = parent::readOne();
        $this->entityData['canread_target_base_human'] = BasePermissions::from($this->entityData['canread_target_base'])->toHuman();
        $this->entityData['canwrite_target_base_human'] = BasePermissions::from($this->entityData['canwrite_target_base'])->toHuman();
        return $this->entityData;
    }

    #[Override]
    public function duplicate(bool $copyFiles = false, bool $linkToOriginal = false): int
    {
        $this->canOrExplode(AccessType::Read);

        return $this->copyEntityFrom(
            sourceEntity: $this,
            title: $this->entityData['title'] . ' I',
            copyFiles: $copyFiles,
        );
    }

    private function updateEntries(): array
    {
        $template = $this->readOne();
        $tags = array_column(new Tags($this)->readAll(), 'tag');
        $hasFields = new Metadata($template['metadata'])->getExtraFields() !== array();
        $result = array('updated' => 0, 'skipped' => 0);

        foreach (array(EntityType::Experiments, EntityType::Items) as $type) {
            // Archived, deleted and locked entries must remain untouched.
            $sql = 'SELECT id FROM ' . $type->value . '
                WHERE created_from_id = :id AND created_from_type = :type
                    AND state = :state AND locked = 0';
            $req = $this->Db->prepare($sql);
            $req->bindValue(':id', $this->id, PDO::PARAM_INT);
            $req->bindValue(':type', $this->entityType->toInt(), PDO::PARAM_INT);
            $req->bindValue(':state', State::Normal->value, PDO::PARAM_INT);
            $this->Db->execute($req);

            foreach ($req->fetchAll(PDO::FETCH_COLUMN) as $id) {
                try {
                    $entry = $type->toInstance($this->Users, $id);
                    $entry->canOrExplode(AccessType::Write);
                } catch (ForbiddenException | ResourceNotFoundException) {
                    ++$result['skipped'];
                    continue;
                }
                // This also covers another user's exclusive edit mode.
                if ($entry->isReadOnly) {
                    ++$result['skipped'];
                    continue;
                }

                $this->Db->beginTransaction();
                try {
                    if ($hasFields) {
                        $metadata = MetadataHelpers::mergeMetadataAdditive($entry->entityData['metadata'], $template['metadata']);
                        if (json_decode($metadata, true) !== json_decode($entry->entityData['metadata'] ?? '{}', true)) {
                            $entry->patch(Action::Update, array('metadata' => $metadata));
                        }
                    }
                    $entryTags = new Tags($entry);
                    $missingTags = array_diff($tags, array_column($entryTags->readAll(), 'tag'));
                    foreach ($missingTags as $tag) {
                        try {
                            $entryTags->postAction(Action::Create, array('tag' => $tag));
                        } catch (ImproperActionException) {
                            // A template tag may not exist in the requester's team, where tag creation can be disabled.
                            continue;
                        }
                    }
                    foreach (array('experiments_links', 'items_links') as $key) {
                        $missingLinks = array_diff(
                            array_column($template[$key], 'entityid'),
                            array_column($entry->entityData[$key], 'entityid'),
                        );
                        foreach ($missingLinks as $linkId) {
                            $links = $key === 'experiments_links'
                                ? LinksFactory::getExperimentsLinks($entry, $linkId)
                                : LinksFactory::getItemsLinks($entry, $linkId);
                            $links->create();
                        }
                    }
                    $this->Db->commit();
                    ++$result['updated'];
                } catch (Throwable $e) {
                    $this->Db->rollBack();
                    throw $e;
                }
            }
        }

        return $result;
    }
}
