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

use Elabftw\Enums\Action;
use Elabftw\Enums\BasePermissions;
use Elabftw\Enums\BinaryValue;
use Elabftw\Enums\BodyContentType;
use Elabftw\Enums\AccessType;
use Elabftw\Enums\State;
use Elabftw\Factories\LinksFactory;
use Override;
use PDO;

use function is_int;
use function array_column;

/**
 * An entity like Templates or ItemsTypes. Template as opposed to Concrete: Experiments and Items
 */
abstract class AbstractTemplateEntity extends AbstractEntity
{
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
        $title = $this->entityData['title'] . ' I';
        $newId = $this->create(
            title: $title,
            body: $this->entityData['body'],
            canread: $this->entityData['canread'],
            canwrite: $this->entityData['canwrite'],
            category: $this->entityData['category'],
            status: $this->entityData['status'],
            metadata: $this->entityData['metadata'],
            hideMainText: BinaryValue::from($this->entityData['hide_main_text']),
            contentType: BodyContentType::from($this->entityData['content_type']),
            createdFromType: $this->entityType,
            createdFromId: $this->id,
        );
        // add missing can*_target
        $fresh = clone $this;
        $fresh->setId($newId);
        $fresh->patch(Action::Update, array(
            'canread_target' => $this->entityData['canread_target'],
            'canwrite_target' => $this->entityData['canwrite_target'],
        ));

        // copy tags
        $Tags = new Tags($this);
        $Tags->copyTags($newId);

        // copy links and steps too
        $ItemsLinks = LinksFactory::getItemsLinks($this);
        /** @psalm-suppress PossiblyNullArgument */
        $ItemsLinks->duplicate($this->id, $newId, fromTemplate: true);
        $ExperimentsLinks = LinksFactory::getExperimentsLinks($this);
        $ExperimentsLinks->duplicate($this->id, $newId, fromTemplate: true);
        $CompoundsLinks = LinksFactory::getCompoundsLinks($this);
        $CompoundsLinks->duplicate($this->id, $newId, fromTemplate: true);
        $Steps = new Steps($this);
        $Steps->duplicate($this->id, $newId, fromTemplate: true);
        if ($copyFiles) {
            $fresh->Uploads = new Uploads($fresh);
            $this->Uploads->duplicate($fresh);
        }

        return $newId;
    }

    public function createTemplateFrom(int $entityId, ?string $title = null): int
    {
        $SourceEntity = $this->entityType->toConcreteEntity($this->Users, $entityId);
        $source = $SourceEntity->readOne();

        $id = $this->create(
            title: $title ?? $source['title'],
            body: $source['body'],
            canreadBase: BasePermissions::from($source['canread_base']),
            canwriteBase: BasePermissions::from($source['canwrite_base']),
            canread: $source['canread'],
            canwrite: $source['canwrite'],
            canreadIsImmutable: (bool) $source['canread_is_immutable'],
            canwriteIsImmutable: (bool) $source['canwrite_is_immutable'],
            category: $source['category'],
            status: $source['status'],
            metadata: $source['metadata'],
            hideMainText: BinaryValue::from($source['hide_main_text']),
            rating: $source['rating'],
            contentType: BodyContentType::from($source['content_type']),
            createdFromType: $SourceEntity->entityType,
            createdFromId: $entityId,
        );

        // copy links, compounds & tags
        $freshSelf = new $this($this->Users, $id);
        $ItemsLinks = LinksFactory::getItemsLinks($SourceEntity);
        $ItemsLinks->duplicate($entityId, $id, toTemplate: true);

        $ExperimentsLinks = LinksFactory::getExperimentsLinks($SourceEntity);
        $ExperimentsLinks->duplicate($entityId, $id, toTemplate: true);

        $CompoundsLinks = LinksFactory::getCompoundsLinks($SourceEntity);
        $CompoundsLinks->duplicate($entityId, $id, toTemplate: true);

        $SourceEntity->Uploads->duplicate($freshSelf);

        $tags = array_column($SourceEntity->Tags->readAll(), 'tag');
        foreach ($tags as $tag) {
            $freshSelf->Tags->postAction(Action::Create, array('tag' => $tag));
        }
        return $id;
    }
}
