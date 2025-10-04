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

use DateTimeImmutable;
use Elabftw\Elabftw\Metadata;
use Elabftw\Elabftw\Tools;
use Elabftw\Enums\Action;
use Elabftw\Enums\BasePermissions;
use Elabftw\Enums\BodyContentType;
use Elabftw\Enums\EntityType;
use Elabftw\Factories\LinksFactory;
use Elabftw\Models\Links\Experiments2ExperimentsLinks;
use Elabftw\Services\Filter;
use Elabftw\Traits\InsertTagsTrait;
use PDO;
use Override;

/**
 * All about the experiments
 */
final class Experiments extends AbstractConcreteEntity
{
    use InsertTagsTrait;

    public EntityType $entityType = EntityType::Experiments;

    #[Override]
    public function create(
        ?string $title = null,
        ?string $body = null,
        ?DateTimeImmutable $date = null,
        ?string $canread = null,
        ?string $canwrite = null,
        ?bool $canreadIsImmutable = false,
        ?bool $canwriteIsImmutable = false,
        array $tags = array(),
        ?int $category = null,
        ?int $status = null,
        ?int $customId = null,
        ?string $metadata = null,
        int $rating = 0,
        BodyContentType $contentType = BodyContentType::Html,
    ): int {
        $canread ??= $this->Users->userData['default_read'] ?? BasePermissions::Team->toJson();
        $canwrite ??= $this->Users->userData['default_write'] ?? BasePermissions::User->toJson();

        // defaults
        $title = Filter::title($title ?? _('Untitled'));
        $date ??= new DateTimeImmutable();
        $body = Filter::body($body);
        if (empty($body)) {
            $body = null;
        }

        // figure out the custom id
        $customId ??= $this->getNextCustomId($category);

        // SQL for create experiments
        $sql = 'INSERT INTO experiments(team, title, date, body, category, status, elabid, canread, canwrite, canread_is_immutable, canwrite_is_immutable, metadata, custom_id, userid, content_type, rating)
            VALUES(:team, :title, :date, :body, :category, :status, :elabid, :canread, :canwrite, :canread_is_immutable, :canwrite_is_immutable, :metadata, :custom_id, :userid, :content_type, :rating)';
        $req = $this->Db->prepare($sql);
        $req->bindParam(':team', $this->Users->team, PDO::PARAM_INT);
        $req->bindParam(':title', $title);
        $req->bindValue(':date', $date->format('Y-m-d'));
        $req->bindParam(':body', $body);
        $req->bindValue(':category', $category);
        $req->bindValue(':status', $status);
        $req->bindValue(':elabid', Tools::generateElabid());
        $req->bindParam(':canread', $canread);
        $req->bindParam(':canwrite', $canwrite);
        $req->bindParam(':canread_is_immutable', $canreadIsImmutable, PDO::PARAM_INT);
        $req->bindParam(':canwrite_is_immutable', $canwriteIsImmutable, PDO::PARAM_INT);
        $req->bindParam(':metadata', $metadata);
        $req->bindParam(':custom_id', $customId, PDO::PARAM_INT);
        $req->bindParam(':userid', $this->Users->userData['userid'], PDO::PARAM_INT);
        $req->bindValue(':content_type', $contentType->value, PDO::PARAM_INT);
        $req->bindParam(':rating', $rating, PDO::PARAM_INT);
        $this->Db->execute($req);
        $newId = $this->Db->lastInsertId();

        $this->insertTags($tags, $newId);

        return $newId;
    }

    /**
     * Duplicate an experiment
     *
     * @return int the ID of the new item
     */
    #[Override]
    public function duplicate(bool $copyFiles = false, bool $linkToOriginal = false): int
    {
        $this->canOrExplode('read');

        $Teams = new Teams($this->Users);
        $Status = new ExperimentsStatus($Teams);

        // let's add something at the end of the title to show it's a duplicate
        // capital i looks good enough
        $title = $this->entityData['title'] . ' I';

        // handle the blank_value_on_duplicate attribute on extra fields
        $metadata = (new Metadata($this->entityData['metadata']))->blankExtraFieldsValueOnDuplicate();

        $newId = $this->create(
            title: $title,
            body: $this->entityData['body'],
            category: $this->entityData['category'],
            // use default status instead of copying the current one
            status: $Status->getDefault(),
            canread: $this->entityData['canread'],
            canwrite: $this->entityData['canwrite'],
            metadata: $metadata,
            contentType: BodyContentType::from($this->entityData['content_type']),
        );

        $fresh = new self($this->Users, $newId);
        /** @psalm-suppress PossiblyNullArgument
         * this->id cannot be null here, checked during canOrExplode */
        $this->ExperimentsLinks->duplicate($this->id, $newId);
        $this->ItemsLinks->duplicate($this->id, $newId);
        $this->Steps->duplicate($this->id, $newId);
        $this->Tags->copyTags($newId);
        $CompoundsLinks = LinksFactory::getCompoundsLinks($this);
        $CompoundsLinks->duplicate($this->id, $newId);
        // also add a link to the original experiment if requested
        if ($linkToOriginal) {
            $ExperimentsLinks = new Experiments2ExperimentsLinks($fresh);
            $ExperimentsLinks->setId($this->id);
            $ExperimentsLinks->postAction(Action::Create, array());
        }
        if ($copyFiles) {
            $this->Uploads->duplicate($fresh);
        }

        return $newId;
    }
}
