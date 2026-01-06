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
use Elabftw\Enums\BasePermissions;
use Elabftw\Enums\BinaryValue;
use Elabftw\Enums\BodyContentType;
use Elabftw\Enums\EntityType;
use Elabftw\Enums\FilterableColumn;
use Elabftw\Factories\LinksFactory;
use Elabftw\Models\Links\Items2ItemsLinks;
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
        BinaryValue $hideMainText = BinaryValue::False,
        int $rating = 0,
        BodyContentType $contentType = BodyContentType::Html,
        // specific to Items
        ?string $canbook = '',
    ): int {
        $title = Filter::title($title ?? _('Untitled'));
        $date ??= new DateTimeImmutable();
        $body = Filter::body($body);
        $canread ??= BasePermissions::Team->toJson();
        $canwrite ??= BasePermissions::Team->toJson();
        $canbook = $canread;
        // figure out the custom id
        $customId ??= $this->getNextCustomId($category);

        $sql = 'INSERT INTO items(team, title, date, status, body, userid, category, elabid, canread, canwrite, canread_is_immutable, canwrite_is_immutable, canbook, metadata, custom_id, content_type, rating, hide_main_text)
            VALUES(:team, :title, :date, :status, :body, :userid, :category, :elabid, :canread, :canwrite, :canread_is_immutable, :canwrite_is_immutable, :canbook, :metadata, :custom_id, :content_type, :rating, :hide_main_text)';
        $req = $this->Db->prepare($sql);
        $req->bindParam(':team', $this->Users->team, PDO::PARAM_INT);
        $req->bindParam(':title', $title);
        $req->bindValue(':date', $date->format('Y-m-d'));
        $req->bindParam(':status', $status);
        $req->bindParam(':body', $body);
        $req->bindParam(':userid', $this->Users->userid, PDO::PARAM_INT);
        $req->bindParam(':category', $category, PDO::PARAM_INT);
        $req->bindValue(':elabid', Tools::generateElabid());
        $req->bindParam(':canread', $canread);
        $req->bindParam(':canwrite', $canwrite);
        $req->bindParam(':canread_is_immutable', $canreadIsImmutable, PDO::PARAM_INT);
        $req->bindParam(':canwrite_is_immutable', $canwriteIsImmutable, PDO::PARAM_INT);
        $req->bindParam(':canbook', $canbook);
        $req->bindParam(':metadata', $metadata);
        $req->bindParam(':custom_id', $customId, PDO::PARAM_INT);
        $req->bindValue(':content_type', $contentType->value, PDO::PARAM_INT);
        $req->bindParam(':rating', $rating, PDO::PARAM_INT);
        $req->bindValue(':hide_main_text', $hideMainText->value, PDO::PARAM_INT);
        $this->Db->execute($req);
        $newId = $this->Db->lastInsertId();

        $this->insertTags($tags, $newId);

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
        // filter on the canbook or canread depending on query param
        if ($Request->query->has('canbook')) {
            return $this->readShow($DisplayParams, true, 'canbook');
        }
        return $this->readShow($DisplayParams, true);
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
            canread: $this->entityData['canread'],
            canwrite: $this->entityData['canwrite'],
            category: $this->entityData['category'],
            status: $this->entityData['status'],
            metadata: $metadata,
            hideMainText: BinaryValue::from($this->entityData['hide_main_text']),
            contentType: BodyContentType::from($this->entityData['content_type']),
        );

        // add missing canbook
        $fresh = new self($this->Users, $newId);
        $fresh->update(new ContentParams('canbook', $this->entityData['canbook']));
        /** @psalm-suppress PossiblyNullArgument */
        $this->ExperimentsLinks->duplicate($this->id, $newId);
        $this->ItemsLinks->duplicate($this->id, $newId);
        $this->Steps->duplicate($this->id, $newId);
        $this->Tags->copyTags($newId);
        $CompoundsLinks = LinksFactory::getCompoundsLinks($this);
        $CompoundsLinks->duplicate($this->id, $newId);
        $ContainersLinks = LinksFactory::getContainersLinks($this);
        $ContainersLinks->duplicate($this->id, $newId);
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

    // get users who booked current item in the 4 surrounding months
    #[Override]
    public function getSurroundingBookers(): array
    {
        // save a sql query if the resource is not bookable
        if (!$this->entityData['is_bookable']) {
            return array();
        }
        // Note: this might reach users that had their account fully archived, but the problem will go away after 4 months.
        $sql = 'SELECT DISTINCT email, CONCAT(firstname, " ", lastname) AS fullname
            FROM team_events
            INNER JOIN users ON users.userid = team_events.userid
            WHERE team_events.item = :itemid
              AND team_events.start BETWEEN DATE_SUB(NOW(), INTERVAL 4 MONTH) AND DATE_ADD(NOW(), INTERVAL 4 MONTH)
              AND users.validated = 1';
        $req = $this->Db->prepare($sql);
        $req->bindValue(':itemid', $this->id, PDO::PARAM_INT);
        $this->Db->execute($req);
        return $req->fetchAll();
    }
}
