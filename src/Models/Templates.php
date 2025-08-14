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
        $title = Filter::title($title ?? _('Untitled'));

        // CANREAD/CANWRITE
        if (isset($this->Users->userData['default_read']) && $canread === null) {
            $canread = $this->Users->userData['default_read'];
        }
        if (isset($this->Users->userData['default_write']) && $canwrite === null) {
            $canwrite = $this->Users->userData['default_write'];
        }
        $canread ??= BasePermissions::Team->toJson();
        $canwrite ??= BasePermissions::User->toJson();

        $sql = 'INSERT INTO experiments_templates(team, title, body, userid, category, status, metadata, canread, canwrite, canread_target, canwrite_target, content_type, rating, canread_is_immutable, canwrite_is_immutable)
            VALUES(:team, :title, :body, :userid, :category, :status, :metadata, :canread, :canwrite, :canread_target, :canwrite_target, :content_type, :rating, :canread_is_immutable, :canwrite_is_immutable)';
        $req = $this->Db->prepare($sql);
        $req->bindParam(':team', $this->Users->team, PDO::PARAM_INT);
        $req->bindParam(':title', $title);
        $req->bindParam(':body', $body);
        $req->bindParam(':userid', $this->Users->userid, PDO::PARAM_INT);
        $req->bindParam(':category', $category, PDO::PARAM_INT);
        $req->bindParam(':status', $status, PDO::PARAM_INT);
        $req->bindParam(':metadata', $metadata);
        $req->bindParam(':canread', $canread);
        $req->bindParam(':canwrite', $canwrite);
        $req->bindParam(':canread_is_immutable', $canreadIsImmutable, PDO::PARAM_INT);
        $req->bindParam(':canwrite_is_immutable', $canwriteIsImmutable, PDO::PARAM_INT);
        $req->bindParam(':canread_target', $canread);
        $req->bindParam(':canwrite_target', $canwrite);
        $req->bindValue(':content_type', $contentType->value, PDO::PARAM_INT);
        $req->bindParam(':rating', $rating, PDO::PARAM_INT);
        $req->execute();
        $id = $this->Db->lastInsertId();
        $this->insertTags($tags, $id);

        return $id;
    }
}
