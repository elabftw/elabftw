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
use Elabftw\Elabftw\Db;
use Elabftw\Elabftw\EntitySqlBuilder;
use Elabftw\Elabftw\Permissions;
use Elabftw\Elabftw\TemplatesSqlBuilder;
use Elabftw\Elabftw\Tools;
use Elabftw\Enums\Action;
use Elabftw\Enums\EntityType;
use Elabftw\Enums\Metadata as MetadataEnum;
use Elabftw\Enums\RequestableAction;
use Elabftw\Enums\State;
use Elabftw\Exceptions\DatabaseErrorException;
use Elabftw\Exceptions\IllegalActionException;
use Elabftw\Exceptions\ImproperActionException;
use Elabftw\Exceptions\ResourceNotFoundException;
use Elabftw\Factories\LinksFactory;
use Elabftw\Interfaces\ContentParamsInterface;
use Elabftw\Params\ContentParams;
use Elabftw\Params\DisplayParams;
use Elabftw\Params\EntityParams;
use Elabftw\Params\ExtraFieldsOrderingParams;
use Elabftw\Services\AccessKeyHelper;
use Elabftw\Services\AdvancedSearchQuery;
use Elabftw\Services\AdvancedSearchQuery\Visitors\VisitorParameters;
use Elabftw\Services\Filter;
use Elabftw\Traits\EntityTrait;
use PDO;
use PDOStatement;
use Override;

use function array_column;
use function array_merge;
use function implode;
use function is_bool;
use function json_encode;
use function ksort;
use function sprintf;

use const JSON_HEX_APOS;
use const JSON_THROW_ON_ERROR;

/**
 * The mother class of Experiments, Items, Templates and ItemsTypes
 */
abstract class AbstractEntity extends AbstractRest
{
    use EntityTrait;

    public const int CONTENT_HTML = 1;

    public const int CONTENT_MD = 2;

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
    ): int;

    abstract public function duplicate(bool $copyFiles = false, bool $linkToOriginal = false): int;

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
     * @param DisplayParams $displayParams display parameters like sort/limit/order by
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
    public function readShow(DisplayParams $displayParams, bool $extended = false, string $can = 'canread'): array
    {
        // (extended) search (block must be before the call to getReadSqlBeforeWhere so extendedValues is filled)
        if (!empty($displayParams->queryString) || !empty($displayParams->extendedQuery)) {
            $this->processExtendedQuery(trim($displayParams->queryString . ' ' . $displayParams->extendedQuery));
        }

        // TODO inject
        if ($this instanceof Templates) {
            $EntitySqlBuilder = new TemplatesSqlBuilder($this);
        } else {
            $EntitySqlBuilder = new EntitySqlBuilder($this);
        }
        $sql = $EntitySqlBuilder->getReadSqlBeforeWhere(
            $extended,
            $extended,
            $displayParams->relatedOrigin,
        );

        $sql .= ' WHERE 1=1 ';

        // add externally added filters
        $sql .= $this->filterSql;

        // add filters like related, owner or category
        $sql .= $displayParams->filterSql;

        // add the json permissions
        $sql .= $EntitySqlBuilder->getCanFilter($can);

        $sqlArr = array(
            $this->extendedFilter,
            $this->idFilter,
            $displayParams->getStatesSql('entity'),
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
        // a Review action doesn't do anything
        if ($action === Action::Review) {
            // clear any request action - skip for templates
            if ($this instanceof AbstractConcreteEntity) {
                $RequestActions = new RequestActions($this->Users, $this);
                $RequestActions->remove(RequestableAction::Review);
            }
            return $this->readOne();
        }
        // the toggle pin action doesn't require write access to the entity
        if ($action !== Action::Pin) {
            $this->canOrExplode('write');
        }
        // if there is an active exclusive edit mode, entity cannot be modified
        // only user who locked can do everything
        // (sys)admin can remove locks
        // everyone can Pin, AccessKey, Bloxberg, Sign, Timestamp
        $this->ExclusiveEditMode->canPatchOrExplode($action);
        match ($action) {
            Action::AccessKey => (new AccessKeyHelper($this))->toggleAccessKey(),
            Action::Archive => (
                function () {
                    $targetState = State::Normal;
                    if ($this->entityData['state'] === $targetState->value) {
                        $targetState = State::Archived;
                        if ($this->entityData['locked'] === 0) {
                            $this->toggleLock();
                        }
                    }
                    $this->update(new EntityParams('state', (string) $targetState->value));
                    // clear any request action
                    $RequestActions = new RequestActions($this->Users, $this);
                    $RequestActions->remove(RequestableAction::Archive);
                }
            )(),
            Action::Destroy => $this->destroy(),
            Action::Lock => $this->toggleLock(),
            Action::ForceLock => $this->lock(),
            Action::ForceUnlock => $this->unlock(),
            Action::Pin => $this->Pins->togglePin(),
            Action::SetCanread => $this->update(new EntityParams('canread', $params['can'])),
            Action::SetCanwrite => $this->update(new EntityParams('canwrite', $params['can'])),
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
            Action::Update => (
                function () use ($params) {
                    foreach ($params as $key => $value) {
                        $this->update(new EntityParams($key, (string) $value));
                    }
                }
            )(),
            Action::RemoveExclusiveEditMode => $this->ExclusiveEditMode->destroy(),
            default => throw new ImproperActionException('Invalid action parameter.'),
        };
        return $this->readOne();
    }

    public function readOneFull(): array
    {
        $this->Uploads->includeArchived = true;
        $base = $this->readOne();
        // items types don't have this yet
        if ($this instanceof AbstractConcreteEntity || $this instanceof Templates) {
            $base['revisions'] = (new Revisions($this))->readAll();
            $base['changelog'] = (new Changelog($this))->readAll();
        }
        ksort($base);
        return $base;
    }

    /**
     * Check if we have the permission to read/write or throw an exception
     *
     * @param string $rw read or write
     * @throws IllegalActionException
     */
    public function canOrExplode(string $rw): void
    {
        if ($this->id === null) {
            throw new ImproperActionException('Cannot check permissions without an id!');
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
            || (array_key_exists('locked', $this->entityData) && $this->entityData['locked'] === 1)
        ) {
            $this->isReadOnly = true;
        }

        if (!$permissions[$rw]) {
            throw new IllegalActionException('User tried to access entity without permission.');
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
            substr(Filter::forFilesystem($this->entityData['title']), 0, 100),
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
        // set state to deleted
        return $this->update(new EntityParams('state', (string) State::Deleted->value));
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
}
