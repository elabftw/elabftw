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
use Elabftw\Elabftw\Permissions;
use Elabftw\Elabftw\Tools;
use Elabftw\Enums\Action;
use Elabftw\Enums\EntityType;
use Elabftw\Enums\FilterableColumn;
use Elabftw\Factories\LinksFactory;
use Elabftw\Params\ContentParams;
use Elabftw\Params\DisplayParams;
use Elabftw\Services\Filter;
use Elabftw\Traits\InsertTagsTrait;
use PDO;
use Symfony\Component\HttpFoundation\Request;
use Override;

/**
 * All about the database items
 */
final class Items extends AbstractConcreteEntity
{
    use InsertTagsTrait;

    public EntityType $entityType = EntityType::Items;

    #[Override]
    public function create(
        ?int $template = -1,
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
        ?int $contentType = null,
        bool $forceExpTpl = false,
        string $defaultTemplateHtml = '',
        string $defaultTemplateMd = '',
        // specific to Items
        ?string $canbook = '',
    ): int {
        // TODO maybe allow creating an Item without any template, like for experiments
        $ItemsTypes = new ItemsTypes($this->Users);
        if ($template < 0 || $template === null) {
            $template = $ItemsTypes->getDefault();
        }
        $ItemsTypes->setId($template);
        $itemTemplate = $ItemsTypes->readOne();
        $title = Filter::title($title ?? _('Untitled'));
        $date ??= new DateTimeImmutable();
        $body = Filter::body($body ?? $itemTemplate['body']);
        $canread ??= $itemTemplate['canread_target'];
        $canwrite ??= $itemTemplate['canwrite_target'];
        $canreadIsImmutable = $itemTemplate['canread_is_immutable'];
        $canwriteIsImmutable = $itemTemplate['canwrite_is_immutable'];
        $canbook = $canread;
        $status ??= $itemTemplate['status'];
        $metadata ??= $itemTemplate['metadata'];
        // figure out the custom id
        $customId = $this->getNextCustomId($template);
        $contentType = $itemTemplate['content_type'];

        $sql = 'INSERT INTO items(team, title, date, status, body, userid, category, elabid, canread, canwrite, canread_is_immutable, canwrite_is_immutable, canbook, metadata, custom_id, content_type, rating)
            VALUES(:team, :title, :date, :status, :body, :userid, :category, :elabid, :canread, :canwrite, :canread_is_immutable, :canwrite_is_immutable, :canbook, :metadata, :custom_id, :content_type, :rating)';
        $req = $this->Db->prepare($sql);
        $req->bindParam(':team', $this->Users->team, PDO::PARAM_INT);
        $req->bindParam(':title', $title);
        $req->bindValue(':date', $date->format('Y-m-d'));
        $req->bindParam(':status', $status);
        $req->bindParam(':body', $body);
        $req->bindParam(':userid', $this->Users->userid, PDO::PARAM_INT);
        $req->bindParam(':category', $template, PDO::PARAM_INT);
        $req->bindValue(':elabid', Tools::generateElabid());
        $req->bindParam(':canread', $canread);
        $req->bindParam(':canwrite', $canwrite);
        $req->bindParam(':canread_is_immutable', $canreadIsImmutable, PDO::PARAM_INT);
        $req->bindParam(':canwrite_is_immutable', $canwriteIsImmutable, PDO::PARAM_INT);
        $req->bindParam(':canbook', $canbook);
        $req->bindParam(':metadata', $metadata);
        $req->bindParam(':custom_id', $customId, PDO::PARAM_INT);
        $req->bindParam(':content_type', $contentType, PDO::PARAM_INT);
        $req->bindParam(':rating', $rating, PDO::PARAM_INT);
        $this->Db->execute($req);
        $newId = $this->Db->lastInsertId();

        $this->insertTags($tags, $newId);
        $this->ItemsLinks->duplicate($itemTemplate['id'], $newId, true);
        $this->ExperimentsLinks->duplicate($itemTemplate['id'], $newId, true);
        $this->Steps->duplicate($itemTemplate['id'], $newId, true);

        return $newId;
    }

    /**
     * Get all items with is_bookable that we can read
     */
    public function readBookable(): array
    {
        $Request = Request::createFromGlobals();
        $DisplayParams = new DisplayParams($this->Users, EntityType::Items, $Request->query);
        // we only want the bookable type of items
        $DisplayParams->appendFilterSql(FilterableColumn::Bookable, 1);
        // make limit very big because we want to see ALL the bookable items here
        $DisplayParams->limit = 900000;
        // filter on the canbook or canread depending on query param
        if ($Request->query->has('canbook')) {
            return $this->readShow($DisplayParams, true, 'canbook');
        }
        return $this->readShow($DisplayParams, true, 'canread');
    }

    public function canBook(): bool
    {
        $Permissions = new Permissions($this->Users, $this->entityData);
        $can = json_decode($this->entityData['canbook'], true, 512, JSON_THROW_ON_ERROR);
        return $Permissions->getCan($can);
    }

    public function canBookInPast(): bool
    {
        return $this->Users->isAdmin || (bool) $this->entityData['book_users_can_in_past'];
    }

    #[Override]
    public function duplicate(bool $copyFiles = false, bool $linkToOriginal = false): int
    {
        $this->canOrExplode('read');

        $title = $this->entityData['title'] . ' I';
        // handle the blank_value_on_duplicate attribute on extra fields
        $metadata = (new Metadata($this->entityData['metadata']))->blankExtraFieldsValueOnDuplicate();
        $newId = $this->create(
            title: $title,
            body: $this->entityData['body'],
            template: $this->entityData['category'],
            canread: $this->entityData['canread'],
            canwrite: $this->entityData['canwrite'],
            metadata: $metadata,
            contentType: $this->entityData['content_type'],
        );

        // add missing canbook
        $fresh = new self($this->Users, $newId);
        $fresh->update(new ContentParams('canbook', $this->entityData['canbook']));
        /** @psalm-suppress PossiblyNullArgument */
        $this->ItemsLinks->duplicate($this->id, $newId);
        $this->Steps->duplicate($this->id, $newId);
        $this->Tags->copyTags($newId);
        $CompoundsLinks = LinksFactory::getCompoundsLinks($this);
        $CompoundsLinks->duplicate($this->id, $newId, false);
        // also add a link to the original resource
        if ($linkToOriginal) {
            $ItemsLinks = new Items2ItemsLinks($fresh);
            $ItemsLinks->setId($this->id);
            $ItemsLinks->postAction(Action::Create, array());
        }
        if ($copyFiles) {
            $this->Uploads->duplicate($fresh);
        }

        return $newId;
    }

    #[Override]
    public function destroy(): bool
    {
        parent::destroy();

        // Todo: should this be remove from here as we do soft delete?
        // delete links of this item in experiments with this item linked
        $sql = 'DELETE FROM experiments2items WHERE link_id = :link_id';
        $req = $this->Db->prepare($sql);
        $req->bindParam(':link_id', $this->id, PDO::PARAM_INT);
        $this->Db->execute($req);
        // same for items_links
        $sql = 'DELETE FROM items2items WHERE link_id = :link_id';
        $req = $this->Db->prepare($sql);
        $req->bindParam(':link_id', $this->id, PDO::PARAM_INT);
        $this->Db->execute($req);

        // delete from pinned
        return $this->Pins->cleanup();
    }
}
