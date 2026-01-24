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
use Elabftw\AuditEvent\SignatureCreated;
use Elabftw\Elabftw\App;
use Elabftw\Elabftw\CreateUploadFromLocalFile;
use Elabftw\Elabftw\CanSqlBuilder;
use Elabftw\Elabftw\Db;
use Elabftw\Elabftw\EntitySqlBuilder;
use Elabftw\Elabftw\Env;
use Elabftw\Elabftw\FsTools;
use Elabftw\Elabftw\Permissions;
use Elabftw\Elabftw\TimestampResponse;
use Elabftw\Elabftw\Tools;
use Elabftw\Enums\AccessType;
use Elabftw\Enums\Action;
use Elabftw\Enums\BinaryValue;
use Elabftw\Enums\BodyContentType;
use Elabftw\Enums\EntityType;
use Elabftw\Enums\ExportFormat;
use Elabftw\Enums\Meaning;
use Elabftw\Enums\Messages;
use Elabftw\Enums\Metadata as MetadataEnum;
use Elabftw\Enums\RequestableAction;
use Elabftw\Enums\State;
use Elabftw\Exceptions\AppException;
use Elabftw\Exceptions\DatabaseErrorException;
use Elabftw\Exceptions\IllegalActionException;
use Elabftw\Exceptions\ImproperActionException;
use Elabftw\Exceptions\ResourceNotFoundException;
use Elabftw\Exceptions\UnauthorizedException;
use Elabftw\Exceptions\UnprocessableContentException;
use Elabftw\Factories\LinksFactory;
use Elabftw\Interfaces\ContentParamsInterface;
use Elabftw\Interfaces\SqlBuilderInterface;
use Elabftw\Interfaces\MakeTrustedTimestampInterface;
use Elabftw\Interfaces\QueryParamsInterface;
use Elabftw\Make\MakeBloxberg;
use Elabftw\Make\MakeCustomTimestamp;
use Elabftw\Make\MakeDfnTimestamp;
use Elabftw\Make\MakeDgnTimestamp;
use Elabftw\Make\MakeDigicertTimestamp;
use Elabftw\Make\MakeFullJson;
use Elabftw\Make\MakeGlobalSignTimestamp;
use Elabftw\Make\MakeSectigoTimestamp;
use Elabftw\Make\MakeUniversignTimestamp;
use Elabftw\Make\MakeUniversignTimestampDev;
use Elabftw\Models\Links\AbstractExperimentsLinks;
use Elabftw\Models\Links\AbstractItemsLinks;
use Elabftw\Models\Users\Users;
use Elabftw\Params\ContentParams;
use Elabftw\Params\DisplayParams;
use Elabftw\Params\EntityParams;
use Elabftw\Params\ExtraFieldsOrderingParams;
use Elabftw\Services\AccessKeyHelper;
use Elabftw\Services\AdvancedSearchQuery;
use Elabftw\Services\AdvancedSearchQuery\Visitors\VisitorParameters;
use Elabftw\Services\Email;
use Elabftw\Services\Filter;
use Elabftw\Services\HttpGetter;
use Elabftw\Services\SignatureHelper;
use Elabftw\Services\TeamsHelper;
use Elabftw\Services\TimestampUtils;
use Elabftw\Traits\EntityTrait;
use GuzzleHttp\Client;
use PDO;
use PDOStatement;
use Override;
use Symfony\Component\HttpFoundation\InputBag;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Mailer\Mailer;
use Symfony\Component\Mailer\Transport;
use Symfony\Component\Mime\Address;
use ZipArchive;

use function array_column;
use function array_merge;
use function implode;
use function in_array;
use function is_bool;
use function json_encode;
use function ksort;
use function mb_substr;
use function sprintf;

use const JSON_HEX_APOS;
use const JSON_THROW_ON_ERROR;

/**
 * The mother class of Experiments, Items, Templates and ItemsTypes
 */
abstract class AbstractEntity extends AbstractRest
{
    use EntityTrait;

    public Comments $Comments;

    public AbstractExperimentsLinks $ExperimentsLinks;

    public AbstractItemsLinks $ItemsLinks;

    public Steps $Steps;

    public Tags $Tags;

    public Uploads $Uploads;

    public Pins $Pins;

    public ExclusiveEditMode $ExclusiveEditMode;

    public EntityType $entityType;

    public bool $alwaysShowOwned = false;

    // sql of ids to include
    public string $idFilter = '';

    public bool $isReadOnly = false;

    public bool $isAnon = false;

    // inserted in sql
    public array $extendedValues = array();

    public TeamGroups $TeamGroups;

    // inserted in sql
    private string $extendedFilter = '';

    public function __construct(public Users $Users, public ?int $id = null, public ?bool $bypassReadPermission = false, public ?bool $bypassWritePermission = false)
    {
        parent::__construct();

        $this->ExperimentsLinks = LinksFactory::getExperimentsLinks($this);
        $this->ItemsLinks = LinksFactory::getItemsLinks($this);
        $this->Steps = new Steps($this);
        $this->Tags = new Tags($this);
        $this->Uploads = new Uploads($this);
        $this->Comments = new Comments($this);
        $this->TeamGroups = new TeamGroups($this->Users);
        $this->Pins = new Pins($this);
        $this->ExclusiveEditMode = new ExclusiveEditMode($this);
        // perform check here once instead of in canreadorexplode to avoid making the same query over and over by child entities
        $this->isReadOnly = $this->ExclusiveEditMode->isActive();
        $this->setId($id);
    }

    abstract public function create(
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
    ): int;

    abstract public function duplicate(bool $copyFiles = false, bool $linkToOriginal = false): int;

    public function createFromTemplate(int $templateId, ?string $title = null): int
    {
        $TemplateType = $this->entityType->toTemplateType($this->Users, $templateId);
        $template = $TemplateType->readOne();
        $id = $this->create(
            title: $title ?? $template['title'],
            body: $template['body'],
            canread: $template['canread_target'],
            canwrite: $template['canwrite_target'],
            canreadIsImmutable: (bool) $template['canread_is_immutable'],
            canwriteIsImmutable: (bool) $template['canwrite_is_immutable'],
            category: $template['category'],
            status: $template['status'],
            metadata: $template['metadata'],
            hideMainText: BinaryValue::from($template['hide_main_text']),
            rating: $template['rating'],
            contentType: BodyContentType::from($template['content_type']),
        );
        $tags = array_column($TemplateType->Tags->readAll(), 'tag');
        $this->ItemsLinks->duplicate($templateId, $id, fromTemplate: true);
        $this->ExperimentsLinks->duplicate($templateId, $id, fromTemplate: true);
        $CompoundsLinks = LinksFactory::getCompoundsLinks($this);
        $CompoundsLinks->duplicate($templateId, $id, fromTemplate: true);
        $this->Steps->duplicate($templateId, $id, fromTemplate: true);
        $freshSelf = new $this($this->Users, $id);
        $TemplateType->Uploads->duplicate($freshSelf);
        foreach ($tags as $tag) {
            $freshSelf->Tags->postAction(Action::Create, array('tag' => $tag));
        }
        return $id;
    }

    #[Override]
    public function postAction(Action $action, array $reqBody): int
    {
        return match ($action) {
            Action::Create => (
                function () use ($reqBody) {
                    if (isset($reqBody['template']) && ((int) $reqBody['template']) !== -1) {
                        return $this->createFromTemplate((int) $reqBody['template'], $reqBody['title'] ?? null);
                    }
                    // check if use of template is enforced at team level for experiments
                    $teamConfigArr = new Teams($this->Users, $this->Users->team)->readOne();
                    if ($teamConfigArr['force_exp_tpl'] === 1 && $this instanceof Experiments) {
                        throw new ImproperActionException(_('Experiments must use a template!'));
                    }
                    if (!isset($reqBody['category']) || $reqBody['category'] === -1) {
                        $reqBody['category'] = null;
                    }
                    // convert to int only if not empty, otherwise send null: we don't want to convert a null to int, as it would send 0
                    $category = !empty($reqBody['category']) ? (int) $reqBody['category'] : null;
                    $status = !empty($reqBody['status']) ? (int) $reqBody['status'] : null;
                    // force metadata to be a string
                    $metadata = null;
                    if (!empty($reqBody['metadata'])) {
                        $metadata = json_encode($reqBody['metadata'], JSON_THROW_ON_ERROR);
                    }
                    // force tags to be an array
                    $tags = $reqBody['tags'] ?? null;
                    if (is_string($tags)) {
                        $tags = array($tags);
                    }
                    return $this->create(
                        body: $reqBody['body'] ?? null,
                        title: $reqBody['title'] ?? null,
                        canread: $reqBody['canread'] ?? null,
                        canwrite: $reqBody['canwrite'] ?? null,
                        canreadIsImmutable: (bool) ($reqBody['canread_is_immutable'] ?? false),
                        canwriteIsImmutable: (bool) ($reqBody['canwrite_is_immutable'] ?? false),
                        tags: $tags ?? array(),
                        category: $category,
                        status: $status,
                        metadata: $metadata,
                        contentType: $this->Users->userData['use_markdown'] === 1 ? BodyContentType::Markdown : BodyContentType::Html,
                    );
                }
            )(),
            Action::Duplicate => $this->duplicate((bool) ($reqBody['copyFiles'] ?? false), (bool) ($reqBody['linkToOriginal'] ?? false)),
            Action::Notif => $this->notifyBookers($reqBody),
            default => throw new ImproperActionException('Invalid action parameter.'),
        };
    }

    #[Override]
    public function getApiPath(): string
    {
        return sprintf('api/v2/%s/', $this->entityType->value);
    }

    /**
     * Signal that a submodel has been modified (such as steps or links).
     * The modified_at column is automatically updated when the entity row is modified, but not if a child model is modified,
     * so this need to be called after a post/patch/delete action on the submodel.
     */
    public function touch(): bool
    {
        $sql = sprintf('UPDATE %s SET modified_at = NOW(), lastchangeby = :userid WHERE id = :id', $this->entityType->value);
        $req = $this->Db->prepare($sql);
        $req->bindParam(':id', $this->id, PDO::PARAM_INT);
        $req->bindParam(':userid', $this->Users->requester->userid, PDO::PARAM_INT);
        return $this->Db->execute($req);
    }

    public function getSurroundingBookers(): array
    {
        return array();
    }

    public function lock(): array
    {
        $this->checkToggleLockPermissions();
        return $this->toggleLock(1);
    }

    public function unlock(): array
    {
        $this->checkToggleLockPermissions();
        return $this->toggleLock(0);
    }

    public function toggleLock(?int $targetLockState = null): array
    {
        $this->checkToggleLockPermissions();
        $currentLockState = $this->entityData['locked'];
        if ($targetLockState !== null) {
            $currentLockState = $targetLockState === 1 ? 0 : 1;
        } else {
            $targetLockState = $currentLockState === 1 ? 0 : 1;
        }

        // if we try to unlock something we didn't lock
        if ($currentLockState === 1) {
            $this->checkUnlockPermissions();
        }

        $targetLockedBy = $targetLockState === 1 ? $this->Users->userData['userid'] : null;
        $sql = 'UPDATE ' . $this->entityType->value . ' SET locked = :locked, lockedby = :lockedby, locked_at = CURRENT_TIMESTAMP WHERE id = :id';
        $req = $this->Db->prepare($sql);
        $req->bindParam(':locked', $targetLockState, PDO::PARAM_INT);
        $req->bindParam(':lockedby', $targetLockedBy, PDO::PARAM_INT);
        $req->bindParam(':id', $this->id, PDO::PARAM_INT);
        $this->Db->execute($req);

        // record this action in the changelog
        $Changelog = new Changelog($this);
        $Changelog->create(new ContentParams('locked', $currentLockState === 1 ? 'Unlocked' : 'Locked'));

        // clear any request action - skip for templates
        if ($this instanceof AbstractConcreteEntity) {
            $RequestActions = new RequestActions($this->Users, $this);
            $RequestActions->remove(RequestableAction::Lock);
        }

        return $this->readOne();
    }

    /**
     * Read several entities for show mode
     * The goal here is to decrease the number of read columns to reduce memory footprint
     * The other read function is for view/edit modes where it's okay to fetch more as there is only one ID
     * Only logged in users use this function
     * @param QueryParamsInterface $displayParams display parameters like sort/limit/order by
     * @param bool $extended use it to get a full reply. used by API to get everything back
     * @psalm-suppress UnusedForeachValue
     *
     *                   \||/
     *                   |  @___oo
     *         /\  /\   / (__,,,,|
     *        ) /^\) ^\/ _)
     *        )   /^\/   _)
     *        )   _ /  / _)
     *    /\  )/\/ ||  | )_)
     *   <  >      |(,,) )__)
     *    ||      /    \)___)\
     *    | \____(      )___) )___
     *     \______(_______;;; __;;;
     *
     *          Here be dragons!
     */
    public function readShow(QueryParamsInterface $displayParams, bool $extended = false, string $can = 'canread'): array
    {
        if ($displayParams->isFast()) {
            return $this->readAllSimple($displayParams);
        }
        // (extended) search (block must be before the call to getReadSqlBeforeWhere so extendedValues is filled)
        if ($displayParams->hasUserQuery()) {
            $this->processExtendedQuery($displayParams->getUserQuery());
        }

        $EntitySqlBuilder = $this->getSqlBuilder();
        $sql = $EntitySqlBuilder->getReadSqlBeforeWhere(
            $extended,
            $extended,
            $displayParams->getRelatedOrigin(),
        );

        $sql .= ' WHERE 1=1 ';

        // add externally added filters
        $sql .= $this->filterSql;

        // add filters like related, owner or category
        $sql .= $displayParams->getFilterSql();

        // add the json permissions
        $sql .= $EntitySqlBuilder->getCanFilter($can);

        // dirty hack so we don't take the state query param into account if state is present in extended query
        $stateSql = $displayParams->getStatesSql('entity');
        if (str_contains($this->extendedFilter, 'entity.state')) {
            $stateSql = '';
        }
        $sqlArr = array(
            $this->extendedFilter,
            $this->idFilter,
            $stateSql,
            'GROUP BY id',
            $displayParams->getSql(),
        );

        $sql .= implode(' ', $sqlArr);

        $req = $this->Db->prepare($sql);
        $req->bindParam(':userid', $this->Users->userData['userid'], PDO::PARAM_INT);

        $this->bindExtendedValues($req);
        $this->Db->execute($req);

        return $req->fetchAll();
    }

    /**
     * Read the tags of the entity
     * $items the results of all items from readShow()
     */
    public function getTags(array $items): array
    {
        $sql = sprintf(
            'SELECT DISTINCT tags2entity.tag_id, tags2entity.item_id, tags.tag, (tags_id IS NOT NULL) AS is_favorite
                FROM tags2entity
                LEFT JOIN tags
                    ON (tags2entity.tag_id = tags.id)
                LEFT JOIN favtags2users
                    ON (favtags2users.users_id = :userid
                        AND favtags2users.tags_id = tags.id)
                WHERE tags2entity.item_type = :type
                    AND tags2entity.item_id IN (%s)
                ORDER by tag',
            implode(',', array_column($items, 'id'))
        );
        $req = $this->Db->prepare($sql);
        $req->bindValue(':type', $this->entityType->value);
        $req->bindParam(':userid', $this->Users->userData['userid'], PDO::PARAM_INT);
        $this->Db->execute($req);
        $allTags = array();
        foreach ($req->fetchAll() as $tags) {
            $allTags[$tags['item_id']][] = $tags;
        }
        return $allTags;
    }

    #[Override]
    public function patch(Action $action, array $params): array
    {
        // a Review action doesn't do anything: TODO leave a comment
        if ($action === Action::Review) {
            $RequestActions = new RequestActions($this->Users, $this);
            $RequestActions->remove(RequestableAction::Review);
            return $this->readOne();
        }
        // for deleted or archived entities, allow specific actions (Restore & Unarchive)
        $state = $this->entityData['state'] ?? null;
        // Allow RemoveExclusiveEditMode even on deleted entities: when navigating away from the edit page, a keepalive PATCH may be sent
        // after the entity has been deleted. This avoids a race condition where the client tries to remove exclusive edit mode on an already-deleted entity.
        $allowedActionsOnDeleted = array(Action::Restore, Action::RemoveExclusiveEditMode);
        if ($state === State::Deleted->value && !in_array($action, $allowedActionsOnDeleted, true)) {
            throw new UnprocessableContentException(_('Only the Restore action is allowed on a deleted entity.'));
        }
        if ($state === State::Archived->value && $action !== Action::Unarchive) {
            throw new UnprocessableContentException(_('Only the Unarchive action is allowed on an archived entity.'));
        }

        $requiredAccess = 'write';
        // some actions only require read access even if they are using PATCH verb
        $readAccessActions = array(Action::Pin, Action::Sign, Action::Timestamp, Action::Bloxberg);
        if (in_array($action, $readAccessActions, true)) {
            $requiredAccess = 'read';
            // allow uploading a file to that entity too
            $this->Uploads->Entity->bypassWritePermission = true;
        }
        $this->canOrExplode($requiredAccess);
        // if there is an active exclusive edit mode, entity cannot be modified
        // only user who locked can do everything
        // (sys)admin can remove locks
        // everyone can Pin, AccessKey, Bloxberg, Sign, Timestamp
        $this->ExclusiveEditMode->canPatchOrExplode($action);
        match ($action) {
            Action::AccessKey => (new AccessKeyHelper($this->entityType, $this->id))->toggleAccessKey(),
            Action::Archive => (
                function () {
                    $this->handleArchivedState(State::Normal, State::Archived, fn() => $this->lock());
                    // clear any request action
                    $RequestActions = new RequestActions($this->Users, $this);
                    $RequestActions->remove(RequestableAction::Archive);
                }
            )(),
            Action::Bloxberg => $this->bloxberg(),
            Action::Destroy => $this->destroy(),
            Action::Lock => $this->toggleLock(),
            Action::ForceLock => $this->lock(),
            Action::ForceUnlock => $this->unlock(),
            Action::Pin => $this->Pins->togglePin(),
            Action::Restore => $this->restore(),
            Action::RemoveExclusiveEditMode => $this->ExclusiveEditMode->destroy(),
            Action::SetCanread => $this->update(new EntityParams('canread', $params['can'])),
            Action::SetCanwrite => $this->update(new EntityParams('canwrite', $params['can'])),
            Action::SetNextCustomId => $this->update(new EntityParams('custom_id', $this->getNextIdempotentCustomId())),
            Action::Sign => $this->sign($params['passphrase'], Meaning::from((int) $params['meaning'])),
            Action::Timestamp => $this->timestamp(),
            Action::Unarchive => $this->handleArchivedState(from: State::Archived, to: State::Normal, toggleLock: fn() => $this->unlock()),
            Action::UpdateMetadataField => (
                function () use ($params) {
                    foreach ($params as $key => $value) {
                        // skip action key
                        if ($key !== 'action') {
                            $this->updateJsonField((string) $key, $value);
                        }
                    }
                }
            )(),
            Action::UpdateOwner => $this->updateOwnership((int) $params['userid'], (int) $params['team']),
            Action::Update => (
                function () use ($params) {
                    foreach ($params as $key => $value) {
                        $this->update(new EntityParams($key, (string) $value));
                    }
                }
            )(),
            default => throw new ImproperActionException('Invalid action parameter.'),
        };
        return $this->readOne();
    }

    #[Override]
    public function getQueryParams(?InputBag $query = null): DisplayParams
    {
        return new DisplayParams($this->Users, $this->entityType, $query);
    }

    #[Override]
    public function readAll(?QueryParamsInterface $queryParams = null): array
    {
        $queryParams ??= $this->getQueryParams();
        if ($queryParams->getFastq()) {
            return $this->readAllSimple($queryParams);
        }
        return $this->readShow($queryParams, true);
    }

    #[Override]
    public function readOne(): array
    {
        if ($this->id === null) {
            throw new IllegalActionException('No id was set!');
        }
        $queryParams = $this->getQueryParams(Request::createFromGlobals()->query);
        $sql = $this->getSqlBuilder()->getReadSqlBeforeWhere(true, true);

        $sql .= sprintf(' WHERE entity.id = %d', $this->id);

        $req = $this->Db->prepare($sql);
        if (str_contains($sql, ':userid')) {
            $req->bindParam(':userid', $this->Users->userid, PDO::PARAM_INT);
        }
        $this->Db->execute($req);
        $this->entityData = $this->Db->fetch($req);
        // Note: this is returning something with all values set to null instead of resource not found exception if the id is incorrect.
        if ($this->entityData['id'] === null) {
            throw new ResourceNotFoundException();
        }
        $this->canOrExplode('read');
        $this->entityData['steps'] = $this->Steps->readAll();
        $this->entityData['experiments_links'] = $this->ExperimentsLinks->readAll();
        $this->entityData['items_links'] = $this->ItemsLinks->readAll();
        $this->entityData['related_experiments_links'] = $this->ExperimentsLinks->readRelated();
        $this->entityData['related_items_links'] = $this->ItemsLinks->readRelated();
        $this->entityData['uploads'] = $this->Uploads->readAll($queryParams);
        $this->entityData['comments'] = $this->Comments->readAll();
        $this->entityData['page'] = mb_substr($this->entityType->toPage(), 0, -4);
        $CompoundsLinks = LinksFactory::getCompoundsLinks($this);
        $this->entityData['compounds_links'] = $CompoundsLinks->readAll();
        $ContainersLinks = LinksFactory::getContainersLinks($this);
        $this->entityData['containers'] = $ContainersLinks->readAll();
        $this->entityData['sharelink'] = sprintf(
            '%s/%s?mode=view&id=%d%s',
            Env::asUrl('SITE_URL'),
            $this->entityType->toPage(),
            $this->id,
            // add a share link
            !empty($this->entityData['access_key'])
                ? sprintf('&access_key=%s', $this->entityData['access_key'])
                : '',
        );
        // add the body as html
        $this->entityData['body_html'] = $this->entityData['body'];
        // convert from markdown only if necessary
        if ($this->entityData['content_type'] === BodyContentType::Markdown->value) {
            $this->entityData['body_html'] = Tools::md2html($this->entityData['body'] ?? '');
        }
        if (!empty($this->entityData['metadata'])) {
            $this->entityData['metadata_decoded'] = json_decode($this->entityData['metadata']);
        }
        $exclusiveEditMode = $this->ExclusiveEditMode->readOne();
        $this->entityData['exclusive_edit_mode'] = empty($exclusiveEditMode) ? null : $exclusiveEditMode;
        ksort($this->entityData);
        return $this->entityData;
    }

    public function readOneFull(): array
    {
        $base = $this->readOne();
        $base['revisions'] = (new Revisions($this))->readAll();
        $base['changelog'] = (new Changelog($this))->readAll();
        // we want to include ALL uploaded files
        $base['uploads'] = (new Uploads($this))->readAll(
            $this->getQueryParams(new InputBag(array('state' => '1,2,3')))
        );
        ksort($base);
        return $base;
    }

    public function readAllSimple(QueryParamsInterface $displayParams): array
    {
        $categoryTable = in_array($this->entityType->value, array('items', 'items_types'), true)
            ? 'items_categories'
            : 'experiments_categories';
        $CanSqlBuilder = new CanSqlBuilder($this->Users->requester, AccessType::Read);
        $canFilter = $CanSqlBuilder->getCanFilter();
        $displayParams->setSkipOrderPinned(true);
        $intQuery = intval($displayParams->getFastq());
        // if the query has a numeric part, we also try and match the custom_id or id exactly
        $idSql = '';
        if ($intQuery > 0) {
            $idSql = 'OR entity.id = :intQuery OR entity.custom_id = :intQuery';
        }

        $sql = 'SELECT entity.id, entity.title, entity.custom_id, entity.state, entity.category,
            categoryt.color AS category_color,
            categoryt.title AS category_title,
            statust.color AS status_color,
            statust.title AS status_title,
            CONCAT(users.firstname, " ", users.lastname) AS fullname,
            "' . $this->entityType->value . '" AS type,
            "' . $this->entityType->toPage() . '" AS page
            FROM ' . $this->entityType->value . ' AS entity
            LEFT JOIN ' . $categoryTable . ' AS categoryt ON entity.category = categoryt.id
            LEFT JOIN ' . $this->entityType->value . '_status AS statust ON entity.status = statust.id
            LEFT JOIN users ON entity.userid = users.userid
            LEFT JOIN
                users2teams ON (users2teams.users_id = :userid AND users2teams.teams_id = :teamid)
            WHERE
                entity.title LIKE :query ' . $idSql . '
            ' . $canFilter . '
            ' . $displayParams->getFilterSql() . '
            ' . $displayParams->getStatesSql('entity') . '
            ' . $displayParams->getSql();
        $req = $this->Db->prepare($sql);
        $req->bindParam(':userid', $this->Users->requester->userid, PDO::PARAM_INT);
        $req->bindParam(':teamid', $this->Users->requester->team, PDO::PARAM_INT);
        $req->bindValue(':query', '%' . $displayParams->getFastq() . '%');
        if ($intQuery > 0) {
            $req->bindValue(':intQuery', $intQuery, PDO::PARAM_INT);
        }
        $this->Db->execute($req);
        return $req->fetchAll();
    }

    // Check if we have the permission to read/write or throw an exception
    public function canOrExplode(string $rw): void
    {
        if ($this->id === null) {
            throw new IllegalActionException('Cannot check permissions without an id!');
        }
        if ($this->bypassWritePermission && $rw === 'write') {
            return;
        }
        if ($this->bypassReadPermission && $rw === 'read') {
            return;
        }
        $permissions = $this->getPermissions();

        // READ ONLY?
        if (
            ($permissions['read'] && !$permissions['write'])
            || (array_key_exists('locked', $this->entityData) && $this->entityData['locked'] === 1
            || $this->entityData['state'] === State::Deleted->value)
        ) {
            $this->isReadOnly = true;
        }

        if (!$permissions[$rw]) {
            throw new UnauthorizedException(Messages::InsufficientPermissions->toHuman());
        }
    }

    // Get timestamper full name for display in view mode
    public function getTimestamperFullname(): string
    {
        if ($this->entityData['timestamped'] === 0) {
            return 'Unknown';
        }
        return $this->getFullnameFromUserid($this->entityData['timestampedby']);
    }

    // generate a title useful for zip folder name for instance: shortened, with category and short elabid
    public function toFsTitle(): string
    {
        $prefix = '';
        if ($this->entityData['category_title']) {
            $prefix = Filter::forFilesystem($this->entityData['category_title']) . ' - ';
        }

        return sprintf(
            '%s%s - %s',
            $prefix,
            // prevent a zip name with too much characters from the title, see #3966
            mb_substr(Filter::forFilesystem($this->entityData['title']), 0, 100),
            Tools::getShortElabid($this->entityData['elabid'] ?? ''),
        );
    }

    /**
     * Get an array of id changed since the lastchange date supplied
     *
     * @param int $userid limit to this user
     * @param string $period 20201206-20210101
     */
    public function getIdFromLastchange(int $userid, string $period): array
    {
        if ($period === '') {
            $period = '15000101-30000101';
        }
        [$from, $to] = explode('-', $period);
        $sql = 'SELECT id FROM ' . $this->entityType->value . ' WHERE userid = :userid AND modified_at BETWEEN :from AND :to';
        $req = $this->Db->prepare($sql);
        $req->bindParam(':userid', $userid, PDO::PARAM_INT);
        $req->bindParam(':from', $from);
        $req->bindParam(':to', $to);
        $this->Db->execute($req);

        return array_column($req->fetchAll(), 'id');
    }

    // Get locker full name for display in view mode
    public function getLockerFullname(): string
    {
        if ($this->entityData['locked'] === 0) {
            return 'Unknown';
        }
        return $this->getFullnameFromUserid($this->entityData['lockedby']);
    }

    public function getIdFromCategory(int $category): array
    {
        $sql = 'SELECT id FROM ' . $this->entityType->value . ' WHERE team = :team AND category = :category AND (state = :statenormal OR state = :statearchived)';
        $req = $this->Db->prepare($sql);
        $req->bindParam(':team', $this->Users->team, PDO::PARAM_INT);
        $req->bindValue(':statenormal', State::Normal->value, PDO::PARAM_INT);
        $req->bindValue(':statearchived', State::Archived->value, PDO::PARAM_INT);
        $req->bindParam(':category', $category);
        $req->execute();

        return array_column($req->fetchAll(), 'id');
    }

    public function getIdFromUser(int $userid): array
    {
        $sql = 'SELECT id FROM ' . $this->entityType->value . ' WHERE userid = :userid AND (state = :statenormal OR state = :statearchived)';
        $req = $this->Db->prepare($sql);
        $req->bindValue(':statenormal', State::Normal->value, PDO::PARAM_INT);
        $req->bindValue(':statearchived', State::Archived->value, PDO::PARAM_INT);
        $req->bindParam(':userid', $userid, PDO::PARAM_INT);
        $req->execute();

        return array_column($req->fetchAll(), 'id');
    }

    #[Override]
    public function destroy(): bool
    {
        $this->canOrExplode('write');
        // remove the custom_id upon deletion
        $this->update(new EntityParams('custom_id', ''));
        // delete from pinned too
        $this->Pins->cleanup();
        $this->Uploads->destroyAll();
        return $this->update(new EntityParams('state', State::Deleted->value));
    }

    public function restore(): bool
    {
        $this->canOrExplode('write');
        $this->Uploads->restoreAll();
        return $this->update(new EntityParams('state', State::Normal->value));
    }

    public function updateExtraFieldsOrdering(ExtraFieldsOrderingParams $params): array
    {
        $this->canOrExplode('write');
        $sql = 'UPDATE ' . $this->entityType->value . ' SET metadata = JSON_SET(metadata, :field, :value) WHERE id = :id';
        $req = $this->Db->prepare($sql);
        foreach ($params->ordering as $ordering => $name) {
            // build jsonPath to field
            $field = sprintf(
                '$.%s.%s.%s',
                MetadataEnum::ExtraFields->value,
                json_encode($name, JSON_HEX_APOS | JSON_THROW_ON_ERROR),
                MetadataEnum::Position->value,
            );
            $req->bindParam(':field', $field);
            $req->bindValue(':value', $ordering, PDO::PARAM_INT);
            $req->bindParam(':id', $this->id, PDO::PARAM_INT);
            $this->Db->execute($req);
        }
        return $this->readOne();
    }

    // Update an entity. The revision is saved before so it can easily compare old and new body.
    public function update(ContentParamsInterface $params): bool
    {
        $content = $params->getContent();
        if ($params->getTarget() === 'bodyappend') {
            $content = $this->readOne()['body'] . $content;
        }
        // ensure no changes happen on entries with immutable permissions
        // admins can override the immutability of an entity's permissions. See #5800
        if ($params->getTarget() === 'canread' || $params->getTarget() === 'canwrite') {
            if (($this->entityData[$params->getTarget() . '_is_immutable'] ?? 0) === 1
                && !($this instanceof AbstractTemplateEntity)
                && !($this->Users->isAdmin)
            ) {
                throw new UnprocessableContentException(_('Cannot modify permissions on entry with immutable permissions.'));
            }
        }
        // also prevent modifying immutability of permissions on concrete entities
        if (str_ends_with($params->getTarget(), '_is_immutable') && $this instanceof AbstractConcreteEntity) {
            throw new UnprocessableContentException(_('Cannot modify permissions immutability settings.'));
        }

        // save a revision for body target
        if ($params->getTarget() === 'body' || $params->getTarget() === 'bodyappend') {
            $Config = Config::getConfig();
            $Revisions = new Revisions(
                $this,
                (int) $Config->configArr['max_revisions'],
                (int) $Config->configArr['min_delta_revisions'],
                (int) $Config->configArr['min_days_revisions'],
            );
            $Revisions->create((string) $content);
        }

        $Changelog = new Changelog($this);
        $Changelog->create($params);
        // getColumn cannot be malicious here because of the previous switch
        $sql = 'UPDATE ' . $this->entityType->value . ' SET ' . $params->getColumn() . ' = :content, lastchangeby = :userid WHERE id = :id';
        $req = $this->Db->prepare($sql);
        $req->bindValue(':content', $content);
        $req->bindParam(':id', $this->id, PDO::PARAM_INT);
        $req->bindParam(':userid', $this->Users->userData['userid'], PDO::PARAM_INT);
        // custom_id could be used twice unintentionally
        try {
            return $this->Db->execute($req);
        } catch (DatabaseErrorException $e) {
            if ($params->getColumn() === 'custom_id' && $e->getErrorCode() === Db::DUPLICATE_CONSTRAINT_ERROR) {
                throw new ImproperActionException(_('Custom ID is already used! Try another one.'));
            }
            throw $e;
        }
    }

    public function timestamp(): array
    {
        $Config = Config::getConfig();

        // the source data can be in any format, here it defaults to json but can be pdf too
        $dataFormat = ExportFormat::Json;
        // if we do keeex we want to timestamp a pdf so we can keeex it
        // there might be other options impacting this condition later
        if ($Config->configArr['keeex_enabled'] === '1') {
            $dataFormat = ExportFormat::Pdf;
        }

        // select the timestamp service and do the timestamp request to TSA
        $Maker = $this->getTimestampMaker($Config->configArr, $dataFormat);
        $TimestampUtils = new TimestampUtils(
            new Client(),
            $Maker->generateData(),
            $Maker->getTimestampParameters(),
            new TimestampResponse(),
        );

        // save the token and data in a zip archive
        $zipName = $Maker->getFileName();
        $zipPath = FsTools::getCacheFile() . '.zip';
        $comment = sprintf(_('Timestamp archive by %s'), $this->Users->userData['fullname']);
        $Maker->saveTimestamp(
            $TimestampUtils->timestamp(),
            new CreateUploadFromLocalFile($zipName, $zipPath, $comment, immutable: 1, state: State::Archived),
        );

        // decrement the balance
        $Config->decrementTsBalance();

        // clear any request action
        $RequestActions = new RequestActions($this->Users, $this);
        $RequestActions->remove(RequestableAction::Timestamp);

        // force create a revision
        $Revisions = new Revisions(
            $this,
            (int) $Config->configArr['max_revisions'],
            (int) $Config->configArr['min_delta_revisions'],
            (int) $Config->configArr['min_days_revisions'],
        );
        $Revisions->dbInsert($this->entityData['body']);

        return $this->readOne();
    }

    // TODO refactor with canOrExplode()
    // this is bad code, refactor of all this will come later
    protected function canWrite(): bool
    {
        if ($this->id === null) {
            return true;
        }
        if ($this->bypassWritePermission) {
            return true;
        }
        $permissions = $this->getPermissions();

        // READ ONLY?
        if (
            ($permissions['read'] && !$permissions['write'])
            || (array_key_exists('locked', $this->entityData) && $this->entityData['locked'] === 1
            || $this->entityData['state'] === State::Deleted->value)
        ) {
            $this->isReadOnly = true;
        }

        return $permissions['write'];
    }

    protected function getSqlBuilder(): SqlBuilderInterface
    {
        return new EntitySqlBuilder($this);
    }

    protected function checkToggleLockPermissions(): void
    {
        $this->getPermissions();
        // if the entry is locked, only an admin or the locker can unlock it
        // it is no longer necessary to be an admin or owner to lock something
        if ($this->entityData['locked'] === 1 && (!$this->Users->isAdmin && $this->entityData['lockedby'] !== $this->Users->userData['userid'])) {
            throw new ImproperActionException(_("You don't have the rights to lock/unlock this."));
        }
    }

    protected function checkUnlockPermissions(): void
    {
        if (!$this->Users->isAdmin && ($this->entityData['lockedby'] !== $this->Users->userData['userid'])) {
            // Get the first name of the locker to show in error message
            $sql = 'SELECT firstname FROM users WHERE userid = :userid';
            $req = $this->Db->prepare($sql);
            $req->bindParam(':userid', $this->entityData['lockedby'], PDO::PARAM_INT);
            $this->Db->execute($req);
            $firstname = $req->fetchColumn();
            if (is_bool($firstname) || $firstname === null) {
                throw new ImproperActionException('Could not find the firstname of the locker!');
            }
            throw new ImproperActionException(
                sprintf(_("This experiment was locked by %s. You don't have the rights to unlock this."), $firstname)
            );
        }
    }

    protected function getPermissions(): array
    {
        if ($this->bypassWritePermission) {
            return array('read' => true, 'write' => true);
        }
        if ($this->bypassReadPermission) {
            return array('read' => true, 'write' => false);
        }
        // make sure entityData is filled
        if (empty($this->entityData)) {
            $this->readOne();
        }

        return (new Permissions($this->Users, $this->entityData))->forEntity();
    }

    protected function bloxberg(): array
    {
        $configArr = Config::getConfig()->configArr;
        $HttpGetter = new HttpGetter(new Client(), $configArr['proxy'], !Env::asBool('DEV_MODE'));
        $Maker = new MakeBloxberg(
            $this->Users,
            $this,
            $configArr,
            $HttpGetter,
        );
        $Maker->timestamp();
        return $this->readOne();
    }

    protected function getTimestampMaker(array $config, ExportFormat $dataFormat): MakeTrustedTimestampInterface
    {
        return match ($config['ts_authority']) {
            'dfn' => new MakeDfnTimestamp($this->Users, $this, $config, $dataFormat),
            'dgn' => new MakeDgnTimestamp($this->Users, $this, $config, $dataFormat),
            'universign' => Env::asBool('DEV_MODE') ? new MakeUniversignTimestampDev($this->Users, $this, $config, $dataFormat) : new MakeUniversignTimestamp($this->Users, $this, $config, $dataFormat),
            'digicert' => new MakeDigicertTimestamp($this->Users, $this, $config, $dataFormat),
            'sectigo' => new MakeSectigoTimestamp($this->Users, $this, $config, $dataFormat),
            'globalsign' => new MakeGlobalSignTimestamp($this->Users, $this, $config, $dataFormat),
            'custom' => new MakeCustomTimestamp($this->Users, $this, $config, $dataFormat),
            default => throw new ImproperActionException('Incorrect timestamp authority configuration.'),
        };
    }

    protected function sign(string $passphrase, Meaning $meaning): array
    {
        $Sigkeys = new SignatureHelper($this->Users);
        $Maker = new MakeFullJson(array($this));
        $message = $Maker->getFileContent();
        $signature = $Sigkeys->serializeSignature($this->Users->userData['sig_privkey'], $passphrase, $message, $meaning);
        $SigKeys = new SigKeys($this->Users);
        $SigKeys->touch();
        // create an immutable comment
        $Comments = new ImmutableComments($this);
        $comment = sprintf(_('Signed by %s (%s)'), $this->Users->userData['fullname'], $meaning->name);
        $Comments->postAction(Action::Create, array('comment' => $comment));
        // save the signature and data in a zip archive
        $zipPath = FsTools::getCacheFile() . '.zip';
        $ZipArchive = new ZipArchive();
        $ZipArchive->open($zipPath, ZipArchive::CREATE);
        $ZipArchive->addFromString('data.json.minisig', $signature);
        $ZipArchive->addFromString('data.json', $message);
        $ZipArchive->addFromString('key.pub', $this->Users->userData['sig_pubkey']);
        $ZipArchive->addFromString('verify.sh', "#!/bin/sh\nminisign -H -V -p key.pub -m data.json\n");
        $ZipArchive->close();
        $comment = sprintf(_('Signature archive by %s (%s)'), $this->Users->userData['fullname'], $meaning->name);
        $this->Uploads->create(new CreateUploadFromLocalFile('signature archive.zip', $zipPath, $comment, immutable: 1, state: State::Archived));
        $RequestActions = new RequestActions($this->Users, $this);
        $RequestActions->remove(RequestableAction::Sign);
        AuditLogs::create(new SignatureCreated($this->Users->userData['userid'], $this->id ?? 0, $this->entityType));
        // force create a revision
        $Revisions = new Revisions($this, 9000, 0, 0);
        $Revisions->dbInsert($this->entityData['body']);
        return $this->readOne();
    }

    protected function getFullnameFromUserid(int $userid): string
    {
        // maybe user was deleted!
        try {
            $user = new Users($userid);
        } catch (ResourceNotFoundException) {
            return 'User not found!';
        }
        return $user->userData['fullname'];
    }

    protected function getCurrentHighestCustomId(int $category): int
    {
        $sql = 'SELECT custom_id FROM ' . $this->entityType->value . ' WHERE category = :category AND custom_id IS NOT NULL ORDER BY custom_id DESC LIMIT 1';
        $req = $this->Db->prepare($sql);
        $req->bindParam(':category', $category, PDO::PARAM_INT);
        $this->Db->execute($req);
        return (int) $req->fetchColumn();
    }

    // figure out the next custom id for our entity
    protected function getNextIdempotentCustomId(): int
    {
        if ($this->entityData['category'] === null) {
            throw new ImproperActionException(_('A category is required to fetch the next Custom ID'));
        }
        // start by setting our current custom_id null to get idempotency
        $this->update(new EntityParams('custom_id', null));
        return $this->getCurrentHighestCustomId($this->entityData['category']) + 1;
    }

    // Archive a normal entity, Unarchive an archived entity.
    private function handleArchivedState(State $from, State $to, callable $toggleLock): void
    {
        $targetState = $from;
        if ($this->entityData['state'] === $from->value) {
            $targetState = $to;
            $toggleLock();
        }
        $this->update(new EntityParams('state', (string) $targetState->value));
    }

    private function updateOwnership(int $userid, int $team): void
    {
        // if there's no team provided, assign the current user's team
        if ($team === 0) {
            $team = $this->Users->team ?? throw new AppException(Messages::GenericError->toHuman());
        }
        $TeamsHelper = new TeamsHelper($team);
        if (!$TeamsHelper->isUserInTeam($userid)) {
            throw new UnauthorizedException(_('The selected user cannot be assigned ownership in the current team context.'));
        }
        $this->update(new EntityParams('userid', $userid));
        $this->update(new EntityParams('team', $team));
        // transfer entity's uploads as well
        $this->bypassWritePermission = true;
        $this->Uploads->transferOwnership($userid);
    }

    private function addToExtendedFilter(string $extendedFilter, array $extendedValues = array()): void
    {
        $this->extendedFilter .= $extendedFilter . ' ';
        $this->extendedValues = array_merge($this->extendedValues, $extendedValues);
    }

    // Update only one field in the metadata json
    private function updateJsonField(string $key, string|array|int $value): bool
    {
        $Changelog = new Changelog($this);
        $valueAsString = is_array($value) ? implode(', ', $value) : (string) $value;

        // Either ExperimentsLinks or ItemsLinks could be used here
        if ($this->ExperimentsLinks->isSelfLinkViaMetadata($key, $valueAsString)) {
            throw new ImproperActionException(_('Linking an item to itself is not allowed. Please select a different target.'));
        }

        $Changelog->create(new ContentParams('metadata_' . $key, $valueAsString));
        $value = json_encode($value, JSON_HEX_APOS | JSON_THROW_ON_ERROR);

        // build jsonPath to field
        $field = sprintf(
            '$.%s.%s.value',
            MetadataEnum::ExtraFields->value,
            json_encode($key, JSON_HEX_APOS | JSON_THROW_ON_ERROR)
        );

        // the CAST as json is necessary to avoid double encoding
        $sql = 'UPDATE ' . $this->entityType->value . ' SET metadata = JSON_SET(metadata, :field, CAST(:value AS JSON)) WHERE id = :id';
        $req = $this->Db->prepare($sql);
        $req->bindParam(':field', $field);
        $req->bindValue(':value', $value);
        $req->bindParam(':id', $this->id, PDO::PARAM_INT);
        return $this->Db->execute($req);
    }

    private function bindExtendedValues(PDOStatement $req): void
    {
        foreach ($this->extendedValues as $bindValue) {
            $req->bindValue($bindValue['param'], $bindValue['value'], $bindValue['type'] ?? PDO::PARAM_STR);
        }
    }

    private function processExtendedQuery(string $extendedQuery): void
    {
        $advancedQuery = new AdvancedSearchQuery($extendedQuery, new VisitorParameters(
            $this->entityType->value,
            $this->TeamGroups->readGroupsWithUsersFromUser(),
        ));
        $whereClause = $advancedQuery->getWhereClause();
        if ($whereClause) {
            $this->addToExtendedFilter($whereClause['where'], $whereClause['bindValues']);
        }
        $searchError = $advancedQuery->getException();
        if (!empty($searchError)) {
            throw new ImproperActionException('Error with extended search: ' . $searchError);
        }
    }

    private function notifyBookers(array $params): int
    {
        $bookers = $this->getSurroundingBookers();
        $replyTo = new Address($this->Users->userData['email'], $this->Users->userData['fullname']);
        $addresses = array_map(fn($row) => new Address($row['email'], $row['fullname']), $bookers);
        if (!$addresses) {
            return 0;
        }
        $Email = new Email(
            new Mailer(Transport::fromDsn(Config::getConfig()->getDsn())),
            App::getDefaultLogger(),
            Config::getConfig()->configArr['mail_from'],
            Env::asBool('DEMO_MODE'),
        );
        $subject = Filter::toPureString($params['subject']);
        $body = Filter::toPureString($params['body']);
        $sent = 0;
        foreach ($addresses as $address) {
            try {
                $Email->sendEmail($address, $subject, $body, replyTo: $replyTo);
                $sent++;
            } catch (ImproperActionException) {
                continue;
            }
        }
        return $sent;
    }
}
