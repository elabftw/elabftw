<?php declare(strict_types=1);
/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

namespace Elabftw\Models;

use function array_column;

use Elabftw\Elabftw\ContentParams;
use Elabftw\Elabftw\Db;
use Elabftw\Elabftw\DisplayParams;
use Elabftw\Elabftw\EntityParams;
use Elabftw\Elabftw\EntitySqlBuilder;
use Elabftw\Elabftw\Permissions;
use Elabftw\Elabftw\PermissionsHelper;
use Elabftw\Elabftw\Tools;
use Elabftw\Enums\Action;
use Elabftw\Enums\BasePermissions;
use Elabftw\Enums\State;
use Elabftw\Exceptions\IllegalActionException;
use Elabftw\Exceptions\ImproperActionException;
use Elabftw\Exceptions\ResourceNotFoundException;
use Elabftw\Interfaces\ContentParamsInterface;
use Elabftw\Interfaces\RestInterface;
use Elabftw\Services\AdvancedSearchQuery;
use Elabftw\Services\AdvancedSearchQuery\Visitors\VisitorParameters;
use Elabftw\Traits\EntityTrait;

use function explode;
use function implode;
use function is_bool;
use const JSON_HEX_APOS;
use const JSON_THROW_ON_ERROR;
use PDO;
use PDOStatement;
use const SITE_URL;
use Symfony\Component\HttpFoundation\Request;

/**
 * The mother class of Experiments, Items, Templates and ItemsTypes
 */
abstract class AbstractEntity implements RestInterface
{
    use EntityTrait;

    public const TYPE_EXPERIMENTS = 'experiments';

    public const TYPE_ITEMS = 'items';

    public const TYPE_ITEMS_TYPES = 'items_types';

    public const TYPE_TEMPLATES = 'experiments_templates';

    public const CONTENT_HTML = 1;

    public const CONTENT_MD = 2;

    public Comments $Comments;

    public ExperimentsLinks $ExperimentsLinks;

    public ItemsLinks $ItemsLinks;

    public Steps $Steps;

    public Tags $Tags;

    public Uploads $Uploads;

    public Pins $Pins;

    // some TYPE_ const
    public string $type = '';

    // use that to ignore the canOrExplode calls
    public bool $bypassReadPermission = false;

    // use that to ignore the canOrExplode calls
    public bool $bypassWritePermission = false;

    // will be defined in children classes
    public string $page = '';

    // sql of ids to include
    public string $idFilter = '';

    public bool $isReadOnly = false;

    // inserted in sql
    public array $extendedValues = array();

    protected TeamGroups $TeamGroups;

    // inserted in sql
    private string $extendedFilter = '';

    /**
     * Constructor
     *
     * @param int|null $id the id of the entity
     */
    public function __construct(public Users $Users, ?int $id = null)
    {
        $this->Db = Db::getConnection();

        $this->ExperimentsLinks = new ExperimentsLinks($this);
        $this->ItemsLinks = new ItemsLinks($this);
        $this->Steps = new Steps($this);
        $this->Tags = new Tags($this);
        $this->Uploads = new Uploads($this);
        $this->Comments = new Comments($this);
        $this->TeamGroups = new TeamGroups($this->Users);
        $this->Pins = new Pins($this);
        $this->setId($id);
    }

    /**
     * Duplicate an item
     *
     * @return int the new item id
     */
    abstract public function duplicate(): int;

    public function getPage(): string
    {
        return sprintf('api/v2/%s/', $this->page);
    }

    /**
     * Only Experiments will currently implement this correctly
     */
    public function updateTimestamp(string $responseTime): void
    {
    }

    /**
     * Count the number of timestamped experiments during past month (sliding window)
     */
    public function getTimestampLastMonth(): int
    {
        $sql = 'SELECT COUNT(id) FROM experiments WHERE timestamped = 1 AND timestamped_at > (NOW() - INTERVAL 1 MONTH)';
        $req = $this->Db->prepare($sql);
        $this->Db->execute($req);
        return (int) $req->fetchColumn();
    }

    /**
     * Lock/unlock
     */
    public function toggleLock(): array
    {
        $this->getPermissions();
        if (!$this->Users->userData['is_admin'] && $this->entityData['userid'] !== $this->Users->userData['userid']) {
            throw new ImproperActionException(_("You don't have the rights to lock/unlock this."));
        }
        $locked = $this->entityData['locked'];

        // if we try to unlock something we didn't lock
        if ($locked === 1 && !$this->Users->userData['is_admin'] && ($this->entityData['lockedby'] !== $this->Users->userData['userid'])) {
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

        $sql = 'UPDATE ' . $this->type . ' SET locked = IF(locked = 1, 0, 1), lockedby = :lockedby, locked_at = CURRENT_TIMESTAMP WHERE id = :id';
        $req = $this->Db->prepare($sql);
        $req->bindParam(':lockedby', $this->Users->userData['userid'], PDO::PARAM_INT);
        $req->bindParam(':id', $this->id, PDO::PARAM_INT);
        $this->Db->execute($req);
        return $this->readOne();
    }

    public function readAll(): array
    {
        return $this->readShow(new DisplayParams($this->Users, Request::createFromGlobals()), true);
    }

    /**
     * Read several entities for show mode
     * The goal here is to decrease the number of read columns to reduce memory footprint
     * The other read function is for view/edit modes where it's okay to fetch more as there is only one ID
     * Only logged in users use this function
     * @param DisplayParams $displayParams display parameters like sort/limit/order by
     * @param bool $extended use it to get a full reply. used by API to get everything back
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
     *  @psalm-suppress UnusedForeachValue
     */
    public function readShow(DisplayParams $displayParams, bool $extended = false): array
    {
        // (extended) search (block must be before the call to getReadSqlBeforeWhere so extendedValues is filled)
        if (!empty($displayParams->query) or !empty($displayParams->extendedQuery)) {
            $this->processExtendedQuery(trim($displayParams->query . ' ' . $displayParams->extendedQuery));
        }

        $EntitySqlBuilder = new EntitySqlBuilder($this);
        $sql = $EntitySqlBuilder->getReadSqlBeforeWhere($extended, $extended, $displayParams->hasMetadataSearch);
        $teamgroupsOfUser = array_column($this->TeamGroups->readGroupsFromUser(), 'id');

        // first where is the state
        $sql .= ' WHERE entity.state = :state';

        // add externally added filters
        $sql .= $this->filterSql;

        // add filters like related, owner or category
        $sql .= $displayParams->filterSql;

        // metadata filter (this will just be empty if we're not doing anything metadata related)
        $sql .= implode(' ', $displayParams->metadataFilter);

        // teamFilter is to restrict to the team for items only
        // as they have a team column
        $teamFilter = '';
        if ($this instanceof Items) {
            $teamFilter = ' AND users2teams.teams_id = entity.team';
        }
        // add pub/org/team filter
        $sqlPublicOrg = "((JSON_EXTRACT(entity.canread, '$.base') = " . BasePermissions::Full->value . " OR JSON_EXTRACT(entity.canread, '$.base') = " . BasePermissions::Organization->value . ') AND entity.userid = users2teams.users_id) OR ';
        if ($this->Users->userData['show_public']) {
            $sqlPublicOrg = "JSON_EXTRACT(entity.canread, '$.base') = " . BasePermissions::Full->value . " OR JSON_EXTRACT(entity.canread, '$.base') = " . BasePermissions::Organization->value . ') OR ';
        }
        $sql .= ' AND ( ' . $sqlPublicOrg . " (JSON_EXTRACT(entity.canread, '$.base') = " . BasePermissions::MyTeams->value . ' AND users2teams.users_id = entity.userid' . $teamFilter . ") OR (JSON_EXTRACT(entity.canread, '$.base') = " . BasePermissions::User->value . ' ';
        // admin will see the experiments with visibility user for user of their team
        if ($this->Users->userData['is_admin']) {
            $sql .= 'AND entity.userid = users2teams.users_id)';
        } else {
            $sql .= 'AND entity.userid = :userid)';
        }
        // add entities in useronly visibility only if we own them
        $sql .= " OR (JSON_EXTRACT(entity.canread, '$.base') = " . BasePermissions::UserOnly->value . ' AND entity.userid = :userid)';
        // look for teamgroups
        if (!empty($teamgroupsOfUser)) {
            $sql .= ' OR (JSON_CONTAINS(entity.canread, ("[' . implode(',', $teamgroupsOfUser) . "]\"), '$.teamgroups'))";
        }
        // look for users, seems using the :userid placeholder does not work, or at least not in my hands
        $sql .= ' OR (JSON_CONTAINS(entity.canread, ("[ ' . $this->Users->userData['userid'] . "]\"), '$.users'))";
        $sql .= ')';

        $sqlArr = array(
            $this->extendedFilter,
            $this->idFilter,
            'GROUP BY id',
            // build the having clause for metadata
            $displayParams->getMetadataHavingSql(),
            'ORDER BY',
            $displayParams->orderby::toSql($displayParams->orderby),
            $displayParams->sort->value,
            ', entity.id',
            $displayParams->sort->value,
            // add one so we can display Next page if there are more things to display
            sprintf('LIMIT %d', $displayParams->limit + 1),
            sprintf('OFFSET %d', $displayParams->offset),
        );

        $sql .= implode(' ', $sqlArr);

        $req = $this->Db->prepare($sql);
        $req->bindParam(':userid', $this->Users->userData['userid'], PDO::PARAM_INT);
        $req->bindValue(':state', State::Normal->value, PDO::PARAM_INT);
        if ($displayParams->hasMetadataSearch) {
            foreach ($displayParams->metadataKey as $i => $v) {
                $req->bindParam(sprintf(':metadata_key_%d', $i), $displayParams->metadataKey[$i]);
                $req->bindParam(sprintf(':metadata_value_path_%d', $i), $displayParams->metadataValuePath[$i]);
                $req->bindParam(sprintf(':metadata_value_%d', $i), $displayParams->metadataValue[$i]);
            }
        }

        $this->bindExtendedValues($req);
        $this->Db->execute($req);

        return $req->fetchAll();
    }

    /**
     * Read the tags of the entity
     *
     * @param array<array-key, mixed> $items the results of all items from readShow()
     */
    public function getTags(array $items): array
    {
        $sqlid = 'tags2entity.item_id IN (' . implode(',', array_column($items, 'id')) . ')';
        $sql = 'SELECT DISTINCT tags2entity.tag_id, tags2entity.item_id, tags.tag
            FROM tags2entity
            LEFT JOIN tags ON (tags2entity.tag_id = tags.id)
            WHERE tags2entity.item_type = :type AND ' . $sqlid;
        $req = $this->Db->prepare($sql);
        $req->bindParam(':type', $this->type);
        $this->Db->execute($req);
        $allTags = array();
        foreach ($req->fetchAll() as $tags) {
            $allTags[$tags['item_id']][] = $tags;
        }
        return $allTags;
    }

    public function patch(Action $action, array $params): array
    {
        // the toggle pin action doesn't require write access to the entity
        if ($action !== Action::Pin) {
            $this->canOrExplode('write');
        }
        match ($action) {
            Action::Lock => $this->toggleLock(),
            Action::Pin => $this->Pins->togglePin(),
            Action::UpdateMetadataField => (
                function () use ($params) {
                    foreach ($params as $key => $value) {
                        // skip action key
                        if ($key !== 'action') {
                            $this->updateJsonField((string) $key, (string) $value);
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
            default => throw new ImproperActionException('Invalid action parameter.'),
        };
        return $this->readOne();
    }

    /**
     * Get a list of visibility/team groups to display
     */
    public function getCan(string $permission): array
    {
        $PermissionsHelper = new PermissionsHelper();
        return $PermissionsHelper->translate($permission);
    }

    /**
     * Check if we have the permission to read/write or throw an exception
     *
     * @param string $rw read or write
     * @throws IllegalActionException
     */
    public function canOrExplode(string $rw): void
    {
        $permissions = $this->getPermissions();

        // READ ONLY?
        if ($permissions['read'] && !$permissions['write']) {
            $this->isReadOnly = true;
        }

        if (!$permissions[$rw]) {
            throw new IllegalActionException('User tried to access entity without permission.');
        }
    }

    /**
     * Verify we can read/write an item
     * Here be dragons! Cognitive load > 9000
     *
     * @param array<string, mixed>|null $item one item array
     */
    public function getPermissions(?array $item = null): array
    {
        if ($this->bypassWritePermission) {
            return array('read' => true, 'write' => true);
        }
        if ($this->bypassReadPermission) {
            return array('read' => true, 'write' => false);
        }
        if (empty($this->entityData) && !isset($item)) {
            $this->readOne();
        }
        // don't try to read() again if we have the item (for show where there are several items to check)
        if (!isset($item)) {
            $item = $this->entityData;
        }

        // if it has the deleted state, don't show it.
        if ($item['state'] === State::Deleted->value) {
            return array('read' => false, 'write' => false);
        }

        $Permissions = new Permissions($this->Users, $item);

        if ($this instanceof Experiments || $this instanceof Items || $this instanceof Templates) {
            return $Permissions->forEntity();
        }
        if ($this instanceof ItemsTypes) {
            return $Permissions->forItemType();
        }

        return array('read' => false, 'write' => false);
    }

    /**
     * Add an arbitrary filter to the query, externally, not through DisplayParams
     */
    public function addFilter(string $column, string|int $value): void
    {
        $this->filterSql .= sprintf(" AND %s = '%s'", $column, (string) $value);
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
        $sql = 'SELECT id FROM ' . $this->type . ' WHERE userid = :userid AND modified_at BETWEEN :from AND :to';
        $req = $this->Db->prepare($sql);
        $req->bindParam(':userid', $userid, PDO::PARAM_INT);
        $req->bindParam(':from', $from);
        $req->bindParam(':to', $to);
        $this->Db->execute($req);

        return array_column($req->fetchAll(), 'id');
    }

    /**
     * Get timestamper full name for display in view mode
     */
    public function getTimestamperFullname(): string
    {
        if ($this instanceof Items || $this->entityData['timestamped'] === 0) {
            return 'Unknown';
        }
        // maybe user was deleted!
        try {
            $timestamper = new Users($this->entityData['timestampedby']);
        } catch (ResourceNotFoundException) {
            return 'User not found!';
        }
        return $timestamper->userData['fullname'];
    }

    /**
     * Check if the current entity is pin of current user
     */
    public function isPinned(): bool
    {
        $sql = 'SELECT DISTINCT id FROM pin2users WHERE entity_id = :entity_id AND type = :type AND users_id = :users_id';
        $req = $this->Db->prepare($sql);
        $req->bindParam(':users_id', $this->Users->userData['userid']);
        $req->bindParam(':entity_id', $this->id, PDO::PARAM_INT);
        $req->bindParam(':type', $this->type);

        $this->Db->execute($req);
        return $req->rowCount() > 0;
    }

    public function getIdFromCategory(int $category): array
    {
        $sql = 'SELECT id FROM ' . $this->type . ' WHERE team = :team AND category = :category';
        $req = $this->Db->prepare($sql);
        $req->bindParam(':team', $this->Users->team, PDO::PARAM_INT);
        $req->bindParam(':category', $category);
        $req->execute();

        return array_column($req->fetchAll(), 'id');
    }

    public function getIdFromUser(int $userid): array
    {
        $sql = 'SELECT id FROM ' . $this->type . ' WHERE userid = :userid';
        $req = $this->Db->prepare($sql);
        $req->bindParam(':userid', $userid);
        $req->execute();

        return array_column($req->fetchAll(), 'id');
    }

    public function destroy(): bool
    {
        if ($this instanceof AbstractConcreteEntity) {
            // mark all uploads related to that entity as deleted
            $sql = 'UPDATE uploads SET state = :state WHERE item_id = :entity_id AND type = :type';
            $req = $this->Db->prepare($sql);
            $req->bindParam(':entity_id', $this->id, PDO::PARAM_INT);
            $req->bindValue(':type', $this->type);
            $req->bindValue(':state', State::Deleted->value, PDO::PARAM_INT);
            $this->Db->execute($req);
        }
        // set state to deleted
        return $this->update(new EntityParams('state', (string) State::Deleted->value));
    }

    /**
     * Read all from one entity
     */
    public function readOne(): array
    {
        if ($this->id === null) {
            throw new IllegalActionException('No id was set!');
        }
        $EntitySqlBuilder = new EntitySqlBuilder($this);
        $sql = $EntitySqlBuilder->getReadSqlBeforeWhere(true, true, true);

        $sql .= sprintf(' WHERE entity.id = %d', $this->id);

        $req = $this->Db->prepare($sql);
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
        $this->entityData['uploads'] = $this->Uploads->readAll();
        $this->entityData['comments'] = $this->Comments->readAll();
        $this->entityData['page'] = $this->page;
        // add a share link
        $this->entityData['sharelink'] = sprintf('%s/%s.php?mode=view&id=%d&elabid=%s', SITE_URL, $this->page, $this->id, $this->entityData['elabid']);
        // add the body as html
        $this->entityData['body_html'] = $this->entityData['body'];
        // convert from markdown only if necessary
        if ($this->entityData['content_type'] === self::CONTENT_MD) {
            $this->entityData['body_html'] = Tools::md2html($this->entityData['body'] ?? '');
        }
        ksort($this->entityData);
        return $this->entityData;
    }

    /**
     * Update an entity. The revision is saved before so it can easily compare old and new body.
     */
    protected function update(ContentParamsInterface $params): bool
    {
        $content = $params->getContent();
        switch ($params->getTarget()) {
            case 'bodyappend':
                $content = $this->readOne()['body'] . $content;
                // no break
            case 'canread':
            case 'canwrite':
                if ($this->bypassWritePermission === false) {
                    $this->checkTeamPermissionsEnforced($params->getTarget());
                }
                break;
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
        $sql = 'UPDATE ' . $this->type . ' SET ' . $params->getColumn() . ' = :content, lastchangeby = :userid WHERE id = :id';
        $req = $this->Db->prepare($sql);
        $req->bindValue(':content', $content);
        $req->bindParam(':id', $this->id, PDO::PARAM_INT);
        $req->bindParam(':userid', $this->Users->userData['userid'], PDO::PARAM_INT);
        return $this->Db->execute($req);
    }

    private function addToExtendedFilter(string $extendedFilter, array $extendedValues = array()): void
    {
        $this->extendedFilter .= $extendedFilter . ' ';
        $this->extendedValues = array_merge($this->extendedValues, $extendedValues);
    }

    /**
     * Update only one field in the metadata json
     */
    private function updateJsonField(string $key, string $value): bool
    {
        $Changelog = new Changelog($this);
        $Changelog->create(new ContentParams('metadata_' . $key, $value));
        // build field
        $field = json_encode($key, JSON_HEX_APOS | JSON_THROW_ON_ERROR);
        $field = '$.extra_fields.' . $field . '.value';
        $sql = 'UPDATE ' . $this->type . ' SET metadata = JSON_SET(metadata, :field, :value) WHERE id = :id';
        $req = $this->Db->prepare($sql);
        $req->bindParam(':field', $field);
        $req->bindValue(':value', $value);
        $req->bindParam(':id', $this->id, PDO::PARAM_INT);
        return $this->Db->execute($req);
    }

    /**
     * Update read or write permissions for an entity
     *
     * @param string $rw read or write
     */
    private function checkTeamPermissionsEnforced(string $rw): void
    {
        // check if the permissions are enforced
        $Teams = new Teams($this->Users);
        $teamConfigArr = $Teams->readOne();
        if ($rw === 'canread') {
            if ($teamConfigArr['do_force_canread'] === 1 && !$this->Users->userData['is_admin']) {
                throw new ImproperActionException(_('Read permissions enforced by admin. Aborting change.'));
            }
        } else {
            if ($teamConfigArr['do_force_canwrite'] === 1 && !$this->Users->userData['is_admin']) {
                throw new ImproperActionException(_('Write permissions enforced by admin. Aborting change.'));
            }
        }
    }

    private function bindExtendedValues(PDOStatement $req): void
    {
        foreach ($this->extendedValues as $bindValue) {
            $req->bindValue($bindValue['param'], $bindValue['value'], $bindValue['type']);
        }
    }

    private function processExtendedQuery(string $extendedQuery): void
    {
        $advancedQuery = new AdvancedSearchQuery($extendedQuery, new VisitorParameters(
            $this->type,
            $this->TeamGroups->getVisibilityList(),
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
