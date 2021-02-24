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

use Elabftw\Elabftw\Db;
use Elabftw\Elabftw\DisplayParams;
use Elabftw\Elabftw\Permissions;
use Elabftw\Elabftw\Tools;
use Elabftw\Exceptions\DatabaseErrorException;
use Elabftw\Exceptions\IllegalActionException;
use Elabftw\Exceptions\ImproperActionException;
use Elabftw\Exceptions\ResourceNotFoundException;
use Elabftw\Interfaces\CreatableInterface;
use Elabftw\Maps\Team;
use Elabftw\Services\Check;
use Elabftw\Services\Email;
use Elabftw\Services\Filter;
use Elabftw\Traits\EntityTrait;
use function explode;
use function is_bool;
use PDO;

/**
 * The mother class of Experiments and Database
 */
abstract class AbstractEntity implements CreatableInterface
{
    use EntityTrait;

    /** @var Comments $Comments instance of Comments */
    public $Comments;

    /** @var Links $Links instance of Links */
    public $Links;

    /** @var Steps $Steps instance of Steps */
    public $Steps;

    /** @var Tags $Tags instance of Tags */
    public $Tags;

    /** @var Uploads $Uploads instance of Uploads */
    public $Uploads;

    /** @var Users $Users our user */
    public $Users;

    /** @var Pins $Pins */
    public $Pins;

    /** @var string $type experiments or items */
    public $type = '';

    /** @var bool $bypassPermissions use that to ignore the canOrExplode calls */
    public $bypassPermissions = false;

    /** @var string $page will be defined in children classes */
    public $page = '';

    /** @var array $filters an array of arrays with filters for sql query */
    public $filters = array();

    /** @var string $idFilter sql of ids to include */
    public $idFilter;

    /** @var string $titleFilter inserted in sql */
    public $titleFilter = '';

    /** @var string $dateFilter inserted in sql */
    public $dateFilter = '';

    /** @var string $bodyFilter inserted in sql */
    public $bodyFilter = '';

    /** @var bool $isReadOnly if we can read but not write to it */
    public $isReadOnly = false;

    /** @var TeamGroups $TeamGroups instance of TeamGroups */
    protected $TeamGroups;

    /**
     * Constructor
     *
     * @param Users $users
     * @param int|null $id the id of the entity
     */
    public function __construct(Users $users, ?int $id = null)
    {
        $this->Db = Db::getConnection();

        $this->Links = new Links($this);
        $this->Steps = new Steps($this);
        $this->Tags = new Tags($this);
        $this->Uploads = new Uploads($this);
        $this->Users = $users;
        $this->Comments = new Comments($this, new Email(new Config(), $this->Users));
        $this->TeamGroups = new TeamGroups($this->Users);
        $this->Pins = new Pins($this);
        $this->idFilter = '';

        if ($id !== null) {
            $this->setId($id);
        }
    }

    /**
     * Duplicate an item
     *
     * @return int the new item id
     */
    abstract public function duplicate(): int;

    /**
     * Destroy an item
     *
     * @return void
     */
    //abstract public function destroy(?int $id = null): void;

    /**
     * Lock/unlock
     *
     * @return void
     */
    public function toggleLock(): void
    {
        // no locking for templates
        if ($this instanceof Templates) {
            return;
        }

        $permissions = $this->getPermissions();
        if (!$this->Users->userData['can_lock'] && !$permissions['write']) {
            throw new ImproperActionException(_("You don't have the rights to lock/unlock this."));
        }
        $locked = (int) $this->entityData['locked'];

        // if we try to unlock something we didn't lock
        if ($locked === 1 && ($this->entityData['lockedby'] != $this->Users->userData['userid'])) {
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

        // check if the experiment is timestamped. Disallow unlock in this case.
        if ($locked === 1 && $this instanceof Experiments && $this->entityData['timestamped']) {
            throw new ImproperActionException(_('You cannot unlock or edit in any way a timestamped experiment.'));
        }

        $sql = 'UPDATE ' . $this->type . ' SET locked = IF(locked = 1, 0, 1), lockedby = :lockedby, lockedwhen = CURRENT_TIMESTAMP WHERE id = :id';
        $req = $this->Db->prepare($sql);
        $req->bindParam(':lockedby', $this->Users->userData['userid'], PDO::PARAM_INT);
        $req->bindParam(':id', $this->id, PDO::PARAM_INT);
        $this->Db->execute($req);
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
     */
    public function readShow(DisplayParams $displayParams, bool $extended = false): array
    {
        $sql = $this->getReadSqlBeforeWhere($extended, $extended);
        $teamgroupsOfUser = $this->TeamGroups->getGroupsFromUser();

        // there might or might not be a condition for the WHERE, so make sure there is at least one
        $sql .= ' WHERE 1=1';

        foreach ($this->filters as $filter) {
            $sql .= sprintf(" AND %s = '%s'", $filter['column'], $filter['value']);
        }

        // experiments related to something?
        if ($displayParams->searchType === 'related') {
            $sql .= ' AND linkst.link_id = ' . $displayParams->related;
        }
        // teamFilter is to restrict to the team for items only
        // as they have a team column
        $teamFilter = '';
        if ($this instanceof Database) {
            $teamFilter = ' AND users2teams.teams_id = entity.team';
        }
        // add pub/org/team filter
        $sql .= " AND ( entity.canread = 'public' OR entity.canread = 'organization' OR (entity.canread = 'team' AND users2teams.users_id = entity.userid" . $teamFilter . ") OR (entity.canread = 'user' ";
        // admin will see the experiments with visibility user for user of their team
        if ($this->Users->userData['is_admin']) {
            $sql .= 'AND entity.userid = users2teams.users_id)';
        } else {
            // normal user will so only their own experiments
            $sql .= 'AND entity.userid = :userid)';
        }
        // add all the teamgroups in which the user is
        if (!empty($teamgroupsOfUser)) {
            foreach ($teamgroupsOfUser as $teamgroup) {
                $sql .= " OR (entity.canread = $teamgroup)";
            }
        }
        $sql .= ')';

        $sqlArr = array(
            $this->titleFilter,
            $this->dateFilter,
            $this->bodyFilter,
            Tools::getSearchSql($displayParams->query, 'and', '', $this->type),
            $this->idFilter,
            'GROUP BY id ORDER BY',
            $displayParams->getOrderSql(),
            $displayParams->sort,
            ', entity.id',
            $displayParams->sort,
            // add one so we can display Next page if there are more things to display
            'LIMIT ' . (string) ($displayParams->limit + 1),
            'OFFSET ' . (string) $displayParams->offset,
        );

        $sql .= implode(' ', $sqlArr);

        $req = $this->Db->prepare($sql);
        $req->bindParam(':userid', $this->Users->userData['userid'], PDO::PARAM_INT);
        $this->Db->execute($req);

        $itemsArr = $req->fetchAll();
        if ($itemsArr === false) {
            $itemsArr = array();
        }

        return $itemsArr;
    }

    /**
     * Read all from one entity
     * Here be dragons!
     *
     * @param bool $getTags if true, might take a long time
     * @return array
     */
    public function read(bool $getTags = true): array
    {
        if ($this->id === null) {
            throw new IllegalActionException('No id was set!');
        }
        $sql = $this->getReadSqlBeforeWhere($getTags, true);

        $sql .= ' WHERE entity.id = ' . (string) $this->id;

        $req = $this->Db->prepare($sql);
        $this->Db->execute($req);

        $item = $req->fetch();
        if ($item === false) {
            throw new ResourceNotFoundException();
        }

        $permissions = $this->getPermissions($item);
        if ($permissions['read'] === false) {
            throw new IllegalActionException(Tools::error(true));
        }

        return $item;
    }

    /**
     * Read the tags of the entity
     *
     * @param array<array-key, mixed> $items the results of all items from readShow()
     *
     * @return array
     */
    public function getTags(array $items): array
    {
        $itemIds = '(';
        foreach ($items as $item) {
            $itemIds .= 'tags2entity.item_id = ' . $item['id'] . ' OR ';
        }
        $sqlid = rtrim($itemIds, ' OR ') . ')';
        $sql = 'SELECT DISTINCT tags2entity.tag_id, tags2entity.item_id, tags.tag FROM tags2entity
            LEFT JOIN tags ON (tags2entity.tag_id = tags.id)
            WHERE tags2entity.item_type = :type AND ' . $sqlid;
        $req = $this->Db->prepare($sql);
        $req->bindParam(':type', $this->type);
        $this->Db->execute($req);
        $res = $req->fetchAll();
        if ($res === false) {
            return array();
        }
        $allTags = array();
        foreach ($res as $tags) {
            $allTags[$tags['item_id']][] = $tags;
        }
        return $allTags;
    }

    /**
     * Update an entity. The revision is saved before so it can easily compare old and new body.
     *
     * @param string $title
     * @param string $date
     * @param string $body
     * @throws ImproperActionException
     * @throws DatabaseErrorException
     * @return void
     */
    public function update(string $title, string $date, string $body): void
    {
        $this->canOrExplode('write');

        // don't update if locked
        if ($this->entityData['locked']) {
            throw new ImproperActionException(_('Cannot update a locked entity!'));
        }

        // add a revision
        $Revisions = new Revisions($this);
        $Revisions->create($body);

        $title = Filter::title($title);
        $date = Filter::kdate($date);
        $body = Filter::body($body);

        $sql = 'UPDATE ' . $this->type . ' SET
            title = :title,
            date = :date,
            body = :body
            WHERE id = :id';

        $req = $this->Db->prepare($sql);
        $req->bindParam(':title', $title);
        $req->bindParam(':date', $date);
        $req->bindParam(':body', $body);
        $req->bindParam(':id', $this->id, PDO::PARAM_INT);

        $this->Db->execute($req);
    }

    public function updateTitle(string $title): void
    {
        $this->canOrExplode('write');
        // don't update if locked
        if ($this->entityData['locked']) {
            throw new ImproperActionException(_('Cannot update a locked entity!'));
        }

        $title = Filter::title($title);
        $sql = 'UPDATE ' . $this->type . ' SET title = :title WHERE id = :id';
        $req = $this->Db->prepare($sql);
        $req->bindParam(':title', $title);
        $req->bindParam(':id', $this->id, PDO::PARAM_INT);

        $this->Db->execute($req);
    }

    public function updateDate(string $date): void
    {
        $this->canOrExplode('write');
        // don't update if locked
        if ($this->entityData['locked']) {
            throw new ImproperActionException(_('Cannot update a locked entity!'));
        }

        $date = Filter::kdate($date);
        $sql = 'UPDATE ' . $this->type . ' SET date = :date WHERE id = :id';
        $req = $this->Db->prepare($sql);
        $req->bindParam(':date', $date);
        $req->bindParam(':id', $this->id, PDO::PARAM_INT);

        $this->Db->execute($req);
    }

    /**
     * Update read or write permissions for an entity
     *
     * @param string $rw read or write
     * @param string $value
     * @return void
     */
    public function updatePermissions(string $rw, string $value): void
    {
        $this->canOrExplode('write');
        Check::visibility($value);
        Check::rw($rw);
        // check if the permissions are enforced
        $Team = new Team((int) $this->Users->userData['team']);
        if ($rw === 'read') {
            if ($Team->getDoForceCanread() === 1) {
                throw new ImproperActionException(_('Read permissions enforced by admin. Aborting change.'));
            }
            $column = 'canread';
        } else {
            if ($Team->getDoForceCanwrite() === 1) {
                throw new ImproperActionException(_('Read permissions enforced by admin. Aborting change.'));
            }
            $column = 'canwrite';
        }

        $sql = 'UPDATE ' . $this->type . ' SET ' . $column . ' = :value WHERE id = :id';
        $req = $this->Db->prepare($sql);
        $req->bindParam(':value', $value);
        $req->bindParam(':id', $this->id, PDO::PARAM_INT);

        $this->Db->execute($req);
    }

    /**
     * Get a list of visibility/team groups to display
     *
     * @param string $rw read or write
     * @return string capitalized and translated permission level
     */
    public function getCan(string $rw): string
    {
        if (Check::id((int) $this->entityData['can' . $rw]) !== false) {
            return ucfirst($this->TeamGroups->readName((int) $this->entityData['can' . $rw]));
        }
        switch ($this->entityData['can' . $rw]) {
            case 'public':
                $res = _('Public');
                break;
            case 'organization':
                $res = _('Organization');
                break;
            case 'team':
                $res = _('Team');
                break;
            case 'user':
                $res = _('User');
                break;
            default:
                $res = Tools::error();
        }
        return ucfirst($res);
    }

    /**
     * Check if we have the permission to read/write or throw an exception
     *
     * @param string $rw read or write
     * @throws IllegalActionException
     * @return void
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
     * @return array
     */
    public function getPermissions(?array $item = null): array
    {
        if ($this->bypassPermissions) {
            return array('read' => true, 'write' => false);
        }
        if (empty($this->entityData) && !isset($item)) {
            $this->populate();
            if (!isset($this->entityData)) {
                return array('read' => false, 'write' => false);
            }
        }
        // don't try to read() again if we have the item (for show where there are several items to check)
        if (!isset($item)) {
            $item = $this->entityData;
        }

        $Permissions = new Permissions($this->Users, $item);

        if ($this instanceof Experiments || $this instanceof Database) {
            return $Permissions->forExpItem();
        }

        if ($this instanceof Templates) {
            return $Permissions->forTemplates();
        }

        return array('read' => false, 'write' => false);
    }

    /**
     * Update the category for an entity
     *
     * @param int $category id of the category (status or items types)
     * @return void
     */
    public function updateCategory(int $category): void
    {
        $this->canOrExplode('write');

        $sql = 'UPDATE ' . $this->type . ' SET category = :category WHERE id = :id';
        $req = $this->Db->prepare($sql);
        $req->bindParam(':category', $category, PDO::PARAM_INT);
        $req->bindParam(':id', $this->id, PDO::PARAM_INT);
        $this->Db->execute($req);
    }

    /**
     * Add a filter to the query
     * Second param is nullable because it can come from a request param
     *
     * @param string $column the column on which to filter
     * @param string|null $value the value to look for
     * @return void
     */
    public function addFilter(string $column, ?string $value): void
    {
        if ($value === null) {
            return;
        }
        $column = filter_var($column, FILTER_SANITIZE_STRING);
        $value = filter_var($value, FILTER_SANITIZE_STRING);
        $this->filters[] = array('column' => $column, 'value' => $value);
    }

    /**
     * Get an array of id changed since the lastchange date supplied
     *
     * @param int $userid limit to this user
     * @param string $period 20201206-20210101
     * @return array
     */
    public function getIdFromLastchange(int $userid, string $period): array
    {
        if ($period === '') {
            $period = '15000101-30000101';
        }
        list($from, $to) = explode('-', $period);
        $sql = 'SELECT id FROM ' . $this->type . ' WHERE userid = :userid AND lastchange BETWEEN :from AND :to';
        $req = $this->Db->prepare($sql);
        $req->bindParam(':userid', $userid, PDO::PARAM_INT);
        $req->bindParam(':from', $from);
        $req->bindParam(':to', $to);
        $this->Db->execute($req);

        $idArr = array();
        $res = $req->fetchAll();
        if ($res === false) {
            return array();
        }
        foreach ($res as $item) {
            $idArr[] = $item['id'];
        }
        return $idArr;
    }

    /**
     * Now that we have an id, load the data in entityData array
     *
     * @return void
     */
    public function populate(): void
    {
        if ($this->id === null) {
            throw new ImproperActionException('No id was set.');
        }

        // load the entity in entityData array
        $this->entityData = $this->read();
    }

    /**
     * Get token and pdf info for displaying in view mode
     *
     * @return array
     */
    public function getTimestampInfo(): array
    {
        if ($this instanceof Database || $this->entityData['timestamped'] === '0') {
            return array();
        }
        $timestamper = $this->Users->read((int) $this->entityData['timestampedby']);

        $Uploads = new Uploads(new Experiments($this->Users, (int) $this->entityData['id']));
        $Uploads->Entity->type = 'exp-pdf-timestamp';
        $pdf = $Uploads->readAll();

        $Uploads->Entity->type = 'timestamp-token';
        $token = $Uploads->readAll();

        return array(
            'timestamper' => $timestamper,
            'pdf' => $pdf,
            'token' => $token,
        );
    }

    /**
     * Check if the current entity is pin of current user
     *
     * @return bool
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

    /**
     * Get the SQL string for read before the WHERE
     *
     * @param bool $getTags do we get the tags too?
     * @param bool $fullSelect select all the columns of entity
     * @return string
     * @phan-suppress PhanPluginPrintfVariableFormatString
     */
    private function getReadSqlBeforeWhere(bool $getTags = true, bool $fullSelect = false): string
    {
        if ($fullSelect) {
            // get all the columns of entity table
            $select = 'SELECT DISTINCT entity.*,
                GROUP_CONCAT(DISTINCT team_events.experiment IS NOT NULL) AS is_bound,
                GROUP_CONCAT(DISTINCT team_events.item) AS events_item_id,
                GROUP_CONCAT(DISTINCT team_events.id) AS events_id,';
        } else {
            // only get the columns interesting for show mode
            $select = 'SELECT DISTINCT entity.id,
                entity.title,
                entity.date,
                entity.category,
                entity.userid,
                entity.locked,
                entity.canread,
                entity.canwrite,
                entity.lastchange,';
        }
        $select .= "uploads.up_item_id, uploads.has_attachment,
            SUBSTRING_INDEX(GROUP_CONCAT(stepst.next_step ORDER BY steps_ordering, steps_id SEPARATOR '|'), '|', 1) AS next_step,
            categoryt.id AS category_id,
            categoryt.name AS category,
            categoryt.color,
            CONCAT(users.firstname, ' ', users.lastname) AS fullname,
            commentst.recent_comment,
            (commentst.recent_comment IS NOT NULL) AS has_comment";

        $tagsSelect = '';
        $tagsJoin = '';
        if ($getTags) {
            $tagsSelect = ", GROUP_CONCAT(DISTINCT tags.tag ORDER BY tags.id SEPARATOR '|') as tags, GROUP_CONCAT(DISTINCT tags.id) as tags_id";
            $tagsJoin = 'LEFT JOIN tags2entity ON (entity.id = tags2entity.item_id AND tags2entity.item_type = \'%1$s\') LEFT JOIN tags ON (tags2entity.tag_id = tags.id)';
        }
        $uploadsJoin = 'LEFT JOIN (
            SELECT uploads.item_id AS up_item_id,
                (uploads.item_id IS NOT NULL) AS has_attachment,
                uploads.type
            FROM uploads
            GROUP BY uploads.item_id, uploads.type)
            AS uploads
            ON (uploads.up_item_id = entity.id AND uploads.type = \'%1$s\')';

        $usersJoin = 'LEFT JOIN users ON (entity.userid = users.userid)';
        $teamJoin = sprintf(
            'LEFT JOIN users2teams ON (users2teams.users_id = users.userid AND users2teams.teams_id = %s)',
            $this->Users->userData['team']
        );

        $categoryTable = $this->type === 'experiments' ? 'status' : 'items_types';
        $categoryJoin = 'LEFT JOIN ' . $categoryTable . ' AS categoryt ON (categoryt.id = entity.category)';

        $commentsJoin = 'LEFT JOIN (
            SELECT MAX(
                %1$s_comments.datetime) AS recent_comment,
                %1$s_comments.item_id
                FROM %1$s_comments GROUP BY %1$s_comments.item_id
            ) AS commentst
            ON (commentst.item_id = entity.id)';
        $stepsJoin = 'LEFT JOIN (
            SELECT %1$s_steps.item_id AS steps_item_id,
            %1$s_steps.body AS next_step,
            %1$s_steps.ordering AS steps_ordering,
            %1$s_steps.id AS steps_id,
            %1$s_steps.finished AS finished
            FROM %1$s_steps)
            AS stepst ON (
            entity.id = steps_item_id
            AND stepst.finished = 0)';
        $linksJoin = 'LEFT JOIN %1$s_links AS linkst ON (linkst.item_id = entity.id)';


        $from = 'FROM %1$s AS entity';

        if ($this instanceof Experiments) {
            $select .= ', entity.timestamped';
            $eventsColumn = 'experiment';
        } elseif ($this instanceof Database) {
            $select .= ', categoryt.bookable';
            $eventsColumn = 'item';
        } else {
            throw new IllegalActionException('Nope.');
        }
        $eventsJoin = '';
        if ($fullSelect) {
            $eventsJoin = 'LEFT JOIN team_events ON (team_events.' . $eventsColumn . ' = entity.id)';
        }

        $sqlArr = array(
            $select,
            $tagsSelect,
            $from,
            $categoryJoin,
            $commentsJoin,
            $tagsJoin,
            $eventsJoin,
            $stepsJoin,
            $linksJoin,
            $usersJoin,
            $teamJoin,
            $uploadsJoin,
        );

        // replace all %1$s by 'experiments' or 'items'
        return sprintf(implode(' ', $sqlArr), $this->type);
    }
}
