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
use Elabftw\Enums\BinaryValue;
use Elabftw\Enums\BodyContentType;
use Elabftw\Enums\State;
use Elabftw\Factories\LinksFactory;
use Override;
use PDO;

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
    public function duplicate(bool $copyFiles = false, bool $linkToOriginal = false): int
    {
        $this->canOrExplode('read');
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
}
