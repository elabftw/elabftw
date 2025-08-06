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
use Elabftw\Enums\BasePermissions;
use Elabftw\Enums\BodyContentType;
use Elabftw\Enums\EntityType;
use Elabftw\Exceptions\ImproperActionException;
use Elabftw\Params\OrderingParams;
use Elabftw\Services\Filter;
use Elabftw\Traits\InsertTagsTrait;
use Elabftw\Traits\RandomColorTrait;
use Override;
use PDO;

/**
 * The kind of items you can have in the database for a team
 * TODO rename ResourcesTemplates
 */
final class ItemsTypes extends AbstractTemplateEntity
{
    use RandomColorTrait;
    use InsertTagsTrait;

    public EntityType $entityType = EntityType::ItemsTypes;

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
        $this->canWriteOrExplode();

        $title = Filter::title($title ?? _('Default'));
        $defaultPermissions = BasePermissions::Team->toJson();

        $sql = 'INSERT INTO items_types(userid, title, body, team, canread, canwrite, canread_is_immutable, canwrite_is_immutable, canread_target, canwrite_target, category, content_type, status, rating, metadata)
            VALUES(:userid, :title, :body, :team, :canread, :canwrite, :canread_is_immutable, :canwrite_is_immutable, :canread_target, :canwrite_target, :category, :content_type, :status, :rating, :metadata)';
        $req = $this->Db->prepare($sql);
        $req->bindValue(':userid', $this->Users->userid, PDO::PARAM_INT);
        $req->bindValue(':title', $title);
        $req->bindValue(':body', $body);
        $req->bindParam(':team', $this->Users->team, PDO::PARAM_INT);
        $req->bindParam(':canread', $defaultPermissions);
        $req->bindParam(':canwrite', $defaultPermissions);
        $req->bindParam(':canread_is_immutable', $canreadIsImmutable, PDO::PARAM_INT);
        $req->bindParam(':canwrite_is_immutable', $canwriteIsImmutable, PDO::PARAM_INT);
        $req->bindParam(':canread_target', $defaultPermissions);
        $req->bindParam(':canwrite_target', $defaultPermissions);
        $req->bindParam(':category', $category);
        $req->bindValue(':content_type', $contentType->value, PDO::PARAM_INT);
        $req->bindParam(':status', $status);
        $req->bindParam(':rating', $rating, PDO::PARAM_INT);
        $req->bindParam(':metadata', $metadata);
        $this->Db->execute($req);
        $id = $this->Db->lastInsertId();

        $this->insertTags($tags, $id);

        return $id;
    }

    public function getDefault(): int
    {
        // there might be none, so create one if needed
        if (empty($this->readAll())) {
            return $this->create();
        }
        // there are no default items_types, so just pick the first one from the team
        $sql = 'SELECT id FROM items_types WHERE team = :team LIMIT 1';
        $req = $this->Db->prepare($sql);
        $req->bindParam(':team', $this->Users->team, PDO::PARAM_INT);
        $this->Db->execute($req);
        return (int) $req->fetchColumn();
    }

    /**
     * Use our own function instead of SortableTrait to add the team param and permission check
     */
    public function updateOrdering(OrderingParams $params): void
    {
        $this->canWriteOrExplode();
        // we'll probably need to extract the ordering column and place it in a team related table
        $sql = 'UPDATE items_types SET ordering = :ordering WHERE id = :id AND team = :team';
        $req = $this->Db->prepare($sql);
        $req->bindParam(':team', $this->Users->team, PDO::PARAM_INT);
        foreach ($params->ordering as $ordering => $id) {
            $req->bindParam(':ordering', $ordering, PDO::PARAM_INT);
            $req->bindParam(':id', $id, PDO::PARAM_INT);
            $this->Db->execute($req);
        }
    }

    #[Override]
    protected function canWrite(): bool
    {
        $team = new Teams($this->Users, $this->Users->team);
        return $team->teamArr['users_canwrite_resources_categories'] === 1 || $this->Users->isAdmin;
    }

    protected function canWriteOrExplode(): void
    {
        if ($this->bypassWritePermission) {
            return;
        }
        if (!$this->canWrite()) {
            throw new ImproperActionException(_('Sorry, edition of resources templates has been disabled for users by your team Admin.'));
        }
    }
}
