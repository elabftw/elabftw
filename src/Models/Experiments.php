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
use Elabftw\Elabftw\Tools;
use Elabftw\Enums\Action;
use Elabftw\Enums\BasePermissions;
use Elabftw\Enums\BinaryValue;
use Elabftw\Enums\BodyContentType;
use Elabftw\Enums\EntityType;
use Elabftw\Enums\AccessType;
use Elabftw\Models\Links\Experiments2ExperimentsLinks;
use Elabftw\Services\Filter;
use Elabftw\Traits\InsertTagsTrait;
use PDO;
use Override;

use function _;

/**
 * All about the experiments
 */
final class Experiments extends AbstractConcreteEntity
{
    use InsertTagsTrait;

    protected const string FORCE_TEMPLATE_KEY = 'force_exp_tpl';

    public EntityType $entityType = EntityType::Experiments;

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
        ?EntityType $createdFromType = null,
        ?int $createdFromId = null,
    ): int {
        // defaults
        $title = Filter::title($title ?? _('Untitled'));
        $date ??= new DateTimeImmutable();
        $body = $contentType === BodyContentType::Markdown
            ? Filter::bodyMarkdown($body)
            : Filter::body($body);
        if (empty($body)) {
            $body = null;
        }
        // figure out the custom id
        $customId ??= $this->getNextCustomId($category);

        // SQL for create experiments
        $sql = 'INSERT INTO experiments(team, title, date, body, category, status, elabid, canread_base, canwrite_base, canread, canwrite, canread_is_immutable, canwrite_is_immutable, metadata, custom_id, userid, content_type, rating, hide_main_text, created_from_type, created_from_id)
            VALUES(:team, :title, :date, :body, :category, :status, :elabid, :canread_base, :canwrite_base, :canread, :canwrite, :canread_is_immutable, :canwrite_is_immutable, :metadata, :custom_id, :userid, :content_type, :rating, :hide_main_text, :created_from_type, :created_from_id)';
        $req = $this->Db->prepare($sql);
        $req->bindParam(':team', $this->Users->team, PDO::PARAM_INT);
        $req->bindParam(':title', $title);
        $req->bindValue(':date', $date->format('Y-m-d'));
        $req->bindParam(':body', $body);
        $req->bindValue(':category', $category);
        $req->bindValue(':status', $status);
        $req->bindValue(':elabid', Tools::generateElabid());
        $req->bindValue(':canread_base', $canreadBase->value, PDO::PARAM_INT);
        $req->bindValue(':canwrite_base', $canwriteBase->value, PDO::PARAM_INT);
        $req->bindValue(':canread', $canread);
        $req->bindValue(':canwrite', $canwrite);
        $req->bindParam(':canread_is_immutable', $canreadIsImmutable, PDO::PARAM_INT);
        $req->bindParam(':canwrite_is_immutable', $canwriteIsImmutable, PDO::PARAM_INT);
        $req->bindParam(':metadata', $metadata);
        $req->bindParam(':custom_id', $customId, PDO::PARAM_INT);
        $req->bindParam(':userid', $this->Users->userData['userid'], PDO::PARAM_INT);
        $req->bindValue(':content_type', $contentType->value, PDO::PARAM_INT);
        $req->bindParam(':rating', $rating, PDO::PARAM_INT);
        $req->bindValue(':hide_main_text', $hideMainText->value, PDO::PARAM_INT);
        $this->Db->bindNullableInt($req, ':created_from_type', $createdFromType?->toInt());
        $this->Db->bindNullableInt($req, ':created_from_id', $createdFromId);
        $this->Db->execute($req);
        $newId = $this->Db->lastInsertId();

        $this->insertTags($tags, $newId);
        $this->addCreationToChangelog($newId, $createdFromType, $createdFromId);

        return $newId;
    }

    #[Override]
    public function duplicate(bool $copyFiles = false, bool $linkToOriginal = false): int
    {
        $this->canOrExplode(AccessType::Read);

        $newId = $this->copyEntityFrom(
            sourceEntity: $this,
            title: $this->entityData['title'] . ' I',
            copyFiles: $copyFiles,
        );

        if ($linkToOriginal) {
            $fresh = new self($this->Users, $newId);
            $ExperimentsLinks = new Experiments2ExperimentsLinks($fresh);
            $ExperimentsLinks->setId($this->id);
            $ExperimentsLinks->postAction(Action::Create, array());
        }

        return $newId;
    }

    #[Override]
    protected function getCreatePermissionKey(): string
    {
        return 'users_canwrite_experiments';
    }
}
