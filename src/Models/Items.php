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
use Elabftw\AuditEvent\ResourceDeleted;
use Elabftw\Elabftw\Permissions;
use Elabftw\Elabftw\Tools;
use Elabftw\Enums\Action;
use Elabftw\Enums\BasePermissions;
use Elabftw\Enums\BinaryValue;
use Elabftw\Enums\BodyContentType;
use Elabftw\Enums\EntityType;
use Elabftw\Enums\FilterableColumn;
use Elabftw\Enums\AccessType;
use Elabftw\Exceptions\ImproperActionException;
use Elabftw\Models\Links\Items2ItemsLinks;
use Elabftw\Params\ContentParams;
use Elabftw\Params\DisplayParams;
use Elabftw\Services\Filter;
use Elabftw\Traits\InsertTagsTrait;
use PDO;
use Symfony\Component\HttpFoundation\Request;
use Override;

use function _;
use function array_filter;
use function array_intersect;
use function array_map;
use function explode;
use function in_array;
use function json_decode;
use function ksort;
use function trim;

/**
 * All about the database items
 */
final class Items extends AbstractConcreteEntity
{
    use InsertTagsTrait;

    protected const string FORCE_TEMPLATE_KEY = 'force_res_tpl';

    public EntityType $entityType = EntityType::Items;

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
        // specific to Items
        string $canbook = self::EMPTY_CAN_JSON,
        BasePermissions $canbookBase = BasePermissions::Team,
        BinaryValue $isBookable = BinaryValue::False,
    ): int {
        $title = Filter::title($title ?? _('Untitled'));
        $date ??= new DateTimeImmutable();
        $body = Filter::body($body);
        if (empty($body)) {
            $body = null;
        }
        // figure out the custom id
        $customId ??= $this->getNextCustomId($category);

        $sql = 'INSERT INTO items(team, title, date, status, body, userid, category, elabid, canread_base, canwrite_base, canbook_base, canread, canwrite, canread_is_immutable, canwrite_is_immutable, canbook, metadata, custom_id, content_type, rating, hide_main_text, created_from_type, created_from_id, is_bookable)
            VALUES(:team, :title, :date, :status, :body, :userid, :category, :elabid, :canread_base, :canwrite_base, :canbook_base, :canread, :canwrite, :canread_is_immutable, :canwrite_is_immutable, :canbook, :metadata, :custom_id, :content_type, :rating, :hide_main_text, :created_from_type, :created_from_id, :is_bookable)';
        $req = $this->Db->prepare($sql);
        $req->bindParam(':team', $this->Users->team, PDO::PARAM_INT);
        $req->bindParam(':title', $title);
        $req->bindValue(':date', $date->format('Y-m-d'));
        $req->bindParam(':status', $status);
        $req->bindParam(':body', $body);
        $req->bindParam(':userid', $this->Users->userid, PDO::PARAM_INT);
        $req->bindParam(':category', $category, PDO::PARAM_INT);
        $req->bindValue(':elabid', Tools::generateElabid());
        $req->bindValue(':canread_base', $canreadBase->value, PDO::PARAM_INT);
        $req->bindValue(':canwrite_base', $canwriteBase->value, PDO::PARAM_INT);
        $req->bindValue(':canbook_base', $canbookBase->value, PDO::PARAM_INT);
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
        $req->bindValue(':is_bookable', $isBookable->value, PDO::PARAM_INT);
        $this->Db->bindNullableInt($req, ':created_from_type', $createdFromType?->toInt());
        $this->Db->bindNullableInt($req, ':created_from_id', $createdFromId);
        $this->Db->execute($req);
        $newId = $this->Db->lastInsertId();

        $this->insertTags($tags, $newId);
        $this->addCreationToChangelog($newId, $createdFromType, $createdFromId);

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
        return $Permissions->forEntity()->book;
    }

    public function canBookInPast(): bool
    {
        return $this->Users->isAdmin || (bool) $this->entityData['book_users_can_in_past'];
    }

    #[Override]
    public function duplicate(bool $copyFiles = false, bool $linkToOriginal = false): int
    {
        $this->canOrExplode(AccessType::Read);

        $newId = $this->copyEntityFrom(
            sourceEntity: $this,
            title: $this->entityData['title'] . ' I',
            copyFiles: $copyFiles,
            overrideCreateParams: array(
                'canbook' => $this->entityData['canbook'],
                'canbookBase' => BasePermissions::from($this->entityData['canbook_base']),
                'isBookable' => BinaryValue::from($this->entityData['is_bookable']),
            ),
        );

        if ($linkToOriginal) {
            $fresh = new self($this->Users, $newId);
            $ItemsLinks = new Items2ItemsLinks($fresh);
            $ItemsLinks->setId($this->id);
            $ItemsLinks->postAction(Action::Create, array());
        }

        return $newId;
    }

    #[Override]
    public function readOne(): array
    {
        parent::readOne();
        // resolve the settings from the team that owns the item, not the requester's current team
        $Teams = new Teams($this->Users, (int) ($this->entityData['team'] ?? $this->Users->team));
        $this->entityData['deletion_reason_required'] = !empty($Teams->teamArr['deletion_reason_enabled'])
            && $this->deletionReasonMatches($Teams);
        $this->entityData['deletion_reason_options'] = json_decode((string) ($Teams->teamArr['deletion_reason_options'] ?? '[]'), true) ?: array();
        ksort($this->entityData);
        return $this->entityData;
    }

    #[Override]
    public function destroy(): bool
    {
        if (empty($this->entityData)) {
            $this->readOne();
        }
        if (!empty($this->entityData['deletion_reason_required'])) {
            // read the reason from the request body so it stays out of the server access logs
            $request = Request::createFromGlobals();
            $payload = json_decode($request->getContent(), true) ?: array();
            $deletionReason = trim((string) ($payload['deletion_reason'] ?? $request->request->get('deletion_reason', '')));
            if ($deletionReason === '') {
                throw new ImproperActionException(_('A reason must be provided to delete this resource.'));
            }
            new Changelog($this)->create(new ContentParams('deletion_reason', $deletionReason));
            // also record it in the audit log, which survives even if the item is later purged
            AuditLogs::create(new ResourceDeleted($this->Users->userData['userid'], $this->id ?? 0, $this->entityType, $deletionReason));
        }
        return parent::destroy();
    }

    #[Override]
    // get users who booked current item in the 4 surrounding months
    protected function getSurroundingBookers(): array
    {
        // save a sql query if the resource is not bookable
        if (!$this->entityData['is_bookable']) {
            return array();
        }
        // Note: here we select past and future bookers but skip the ones that are archived in all teams
        $sql = 'SELECT DISTINCT
                u.email,
                CONCAT(u.firstname, " ", u.lastname) AS fullname
            FROM team_events tev
            JOIN users u
              ON u.userid = tev.userid
            LEFT JOIN (
                SELECT users_id, MIN(is_archived) AS all_archived
                FROM users2teams
                GROUP BY users_id
            ) ut
              ON ut.users_id = u.userid
            WHERE tev.item = :itemid
              AND tev.start BETWEEN DATE_SUB(NOW(), INTERVAL 4 MONTH)
                              AND DATE_ADD(NOW(), INTERVAL 4 MONTH)
              AND u.validated = 1
              AND COALESCE(ut.all_archived, 0) = 0';
        $req = $this->Db->prepare($sql);
        $req->bindValue(':itemid', $this->id, PDO::PARAM_INT);
        $this->Db->execute($req);
        return $req->fetchAll();
    }

    #[Override]
    protected function getCreatePermissionKey(): string
    {
        return 'users_canwrite_resources';
    }

    // an item needs a deletion reason if its category or one of its tags is watched by the team
    private function deletionReasonMatches(Teams $Teams): bool
    {
        $categories = json_decode((string) ($Teams->teamArr['deletion_reason_categories'] ?? '[]'), true) ?: array();
        if (in_array((int) ($this->entityData['category'] ?? 0), array_map('intval', $categories), true)) {
            return true;
        }
        $watchedTags = json_decode((string) ($Teams->teamArr['deletion_reason_tags'] ?? '[]'), true) ?: array();
        $entityTags = array_filter(explode('|', (string) ($this->entityData['tags'] ?? '')));
        return !empty(array_intersect($watchedTags, $entityTags));
    }
}
