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
use Elabftw\Traits\SortableTrait;
use Override;
use PDO;

/**
 * All about the templates
 */
final class Templates extends AbstractTemplateEntity
{
    use SortableTrait;
    use InsertTagsTrait;

    public EntityType $entityType = EntityType::Templates;

    // color is here just to be on par with itemstypes
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
    ): int {
        $title = Filter::title($title ?? _('Untitled'));
        $body = Filter::body($body);
        if (empty($body)) {
            $body = null;
        }

        $sql = 'INSERT INTO experiments_templates(team, title, body, userid, category, status, metadata, canread_base, canwrite_base, canread, canwrite, canread_target, canwrite_target, content_type, rating, canread_is_immutable, canwrite_is_immutable, hide_main_text)
            VALUES(:team, :title, :body, :userid, :category, :status, :metadata, :canread_base, :canwrite_base, :canread, :canwrite, :canread_target, :canwrite_target, :content_type, :rating, :canread_is_immutable, :canwrite_is_immutable, :hide_main_text)';
        $req = $this->Db->prepare($sql);
        $req->bindParam(':team', $this->Users->team, PDO::PARAM_INT);
        $req->bindParam(':title', $title);
        $req->bindParam(':body', $body);
        $req->bindParam(':userid', $this->Users->userid, PDO::PARAM_INT);
        $req->bindParam(':category', $category, PDO::PARAM_INT);
        $req->bindParam(':status', $status, PDO::PARAM_INT);
        $req->bindParam(':metadata', $metadata);
        $req->bindValue(':canread_base', $canreadBase->value, PDO::PARAM_INT);
        $req->bindValue(':canwrite_base', $canwriteBase->value, PDO::PARAM_INT);
        $req->bindParam(':canread', $canread);
        $req->bindParam(':canwrite', $canwrite);
        $req->bindParam(':canread_is_immutable', $canreadIsImmutable, PDO::PARAM_INT);
        $req->bindParam(':canwrite_is_immutable', $canwriteIsImmutable, PDO::PARAM_INT);
        $req->bindParam(':canread_target', $canread);
        $req->bindParam(':canwrite_target', $canwrite);
        $req->bindValue(':content_type', $contentType->value, PDO::PARAM_INT);
        $req->bindParam(':rating', $rating, PDO::PARAM_INT);
        $req->bindValue(':hide_main_text', $hideMainText->value, PDO::PARAM_INT);
        $req->execute();
        $id = $this->Db->lastInsertId();
        $this->insertTags($tags, $id);

        return $id;
    }

    #[Override]
    protected function getCreatePermissionKey(): string
    {
        return 'users_canwrite_experiments_templates';
    }
}
