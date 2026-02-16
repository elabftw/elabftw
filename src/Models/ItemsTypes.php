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
use Elabftw\Enums\BinaryValue;
use Elabftw\Enums\BodyContentType;
use Elabftw\Enums\EntityType;
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
        BasePermissions $canreadBase = BasePermissions::Team,
        BasePermissions $canwriteBase = BasePermissions::User,
        string $canread = self::EMPTY_CAN_JSON,
        string $canwrite = self::EMPTY_CAN_JSON,
        bool $canreadIsImmutable = false,
        bool $canwriteIsImmutable = false,
        array $tags = array(),
        ?int $category = null,
        ?int $status = null,
        ?int $customId = null,
        ?string $metadata = null,
        BinaryValue $hideMainText = BinaryValue::False,
        int $rating = 0,
        BodyContentType $contentType = BodyContentType::Html,
        string $canbook = self::EMPTY_CAN_JSON,
        BasePermissions $canbookBase = BasePermissions::Team,
    ): int {
        $title = Filter::title($title ?? _('Default'));
        $body = Filter::body($body);
        if (empty($body)) {
            $body = null;
        }

        $sql = 'INSERT INTO items_types(userid, title, body, team, canread_base, canwrite_base, canbook_base, canread, canwrite, canbook, canread_is_immutable, canwrite_is_immutable, canread_target, canwrite_target, category, content_type, status, rating, metadata, hide_main_text)
            VALUES(:userid, :title, :body, :team, :canread_base, :canwrite_base, :canbook_base, :canread, :canwrite, :canbook, :canread_is_immutable, :canwrite_is_immutable, :canread_target, :canwrite_target, :category, :content_type, :status, :rating, :metadata, :hide_main_text)';
        $req = $this->Db->prepare($sql);
        $req->bindValue(':userid', $this->Users->userid, PDO::PARAM_INT);
        $req->bindValue(':title', $title);
        $req->bindValue(':body', $body);
        $req->bindParam(':team', $this->Users->team, PDO::PARAM_INT);
        $req->bindValue(':canread_base', $canreadBase->value, PDO::PARAM_INT);
        $req->bindValue(':canwrite_base', $canwriteBase->value, PDO::PARAM_INT);
        $req->bindParam(':canread', $canread);
        $req->bindParam(':canwrite', $canwrite);
        $req->bindParam(':canbook', $canbook);
        $req->bindValue(':canbook_base', $canbookBase->value, PDO::PARAM_INT);
        $req->bindParam(':canread_is_immutable', $canreadIsImmutable, PDO::PARAM_INT);
        $req->bindParam(':canwrite_is_immutable', $canwriteIsImmutable, PDO::PARAM_INT);
        $req->bindParam(':canread_target', $canread);
        $req->bindParam(':canwrite_target', $canwrite);
        $req->bindParam(':category', $category);
        $req->bindValue(':content_type', $contentType->value, PDO::PARAM_INT);
        $req->bindParam(':status', $status);
        $req->bindParam(':rating', $rating, PDO::PARAM_INT);
        $req->bindParam(':metadata', $metadata);
        $req->bindValue(':hide_main_text', $hideMainText->value, PDO::PARAM_INT);
        $this->Db->execute($req);
        $id = $this->Db->lastInsertId();

        $this->insertTags($tags, $id);

        return $id;
    }

    #[Override]
    protected function getCreatePermissionKey(): string
    {
        return 'users_canwrite_resources_templates';
    }
}
