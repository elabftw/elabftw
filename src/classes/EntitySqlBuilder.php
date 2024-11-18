<?php

/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2022 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

declare(strict_types=1);

namespace Elabftw\Elabftw;

use Elabftw\Enums\BasePermissions;
use Elabftw\Enums\EntityType;
use Elabftw\Exceptions\IllegalActionException;
use Elabftw\Models\AbstractEntity;
use Elabftw\Models\Experiments;
use Elabftw\Models\Items;
use Elabftw\Models\Templates;
use Elabftw\Services\UsersHelper;

use function array_column;
use function array_unique;
use function implode;

class EntitySqlBuilder
{
    protected array $selectSql = array();

    protected array $joinsSql = array();

    public function __construct(private AbstractEntity $entity) {}

    /**
     * Get the SQL string for read before the WHERE
     *
     * @param bool $getTags do we get the tags too?
     * @param bool $fullSelect select all the columns of entity
     * @param null|EntityType $relatedOrigin Are we looking for related entries, what is the origin, experiments or items?
     */
    public function getReadSqlBeforeWhere(
        bool $getTags = true,
        bool $fullSelect = false,
        ?EntityType $relatedOrigin = null,
    ): string {
        $this->entitySelect($fullSelect);
        $this->status();
        $this->category();
        $this->comments();
        if ($getTags) {
            $this->tags();
        }
        if ($fullSelect) {
            $this->teamEvents();
        }
        $this->steps();
        // The links tables are only joined if we want to show related entities
        if ($relatedOrigin !== null) {
            $this->links($relatedOrigin);
        }
        $this->usersTeams();
        $this->uploads();

        $sql = array(
            'SELECT DISTINCT',
            implode(', ', $this->selectSql),
            'FROM %1$s AS entity',
            implode(' ', $this->joinsSql),
        );

        // replace all %1$s by 'experiments' or 'items', there are many more than the one in FROM
        return sprintf(implode(' ', $sql), $this->entity->entityType->value);
    }

    public function getCanFilter(string $can): string
    {
        $sql = '';
        if ($this->entity->isAnon) {
            $sql .= ' AND ' . $this->canAnon($can);
        }
        $sql .= sprintf(
            ' AND (%s %s)',
            implode(' OR ', array(
                $this->canBasePub($can),
                $this->canBaseOrg($can),
                $this->canBaseTeam($can),
                $this->canBaseUser($can),
                $this->canBaseUserOnly($can),
                $this->canTeams($can),
                $this->canTeamGroups($can),
                $this->canUsers($can),
            )),
            $this->entity->alwaysShowOwned ? 'OR entity.userid = :userid' : '',
        );

        return $sql;
    }

    protected function entitySelect(bool $fullSelect): void
    {
        if ($fullSelect) {
            // get all the columns of entity table
            $this->selectSql[] = 'entity.*';
            // add a literal string for the page that can be used by the mention tinymce plugin code
            $this->selectSql[] = sprintf(
                "'%s' AS page, '%s' AS type",
                $this->entity->entityType->toPage(),
                $this->entity->entityType->value,
            );
        } else {
            // only get the columns interesting for show mode
            $this->selectSql[] = 'entity.id,
                entity.title,
                entity.custom_id,
                entity.date,
                entity.category,
                entity.team,
                entity.status,
                entity.rating,
                entity.userid,
                entity.locked,
                entity.state,
                entity.canread,
                entity.canwrite,
                entity.modified_at,
                entity.timestamped';
            // only include columns (created_at, locked_at, timestamped_at, entity.metadata) if actually searching for it
            if (!empty(array_column($this->entity->extendedValues, 'additional_columns'))) {
                $this->selectSql[] = implode(', ', array_unique(array_column($this->entity->extendedValues, 'additional_columns')));
            }
        }
    }

    protected function category(): void
    {
        $this->selectSql[] = 'categoryt.title AS category_title,
            categoryt.color AS category_color';

        $this->joinsSql[] = sprintf(
            'LEFT JOIN %s AS categoryt
                ON (categoryt.id = entity.category)',
            $this->entity->entityType === EntityType::Experiments || $this->entity->entityType === EntityType::Templates
                ? 'experiments_categories'
                : 'items_types',
        );
    }

    protected function status(): void
    {
        $this->selectSql[] = 'statust.title AS status_title,
            statust.color AS status_color';
        $this->joinsSql[] = 'LEFT JOIN %1$s_status AS statust
            ON (statust.id = entity.status)';
    }

    protected function uploads(): void
    {
        $this->selectSql[] = 'uploads.up_item_id,
            uploads.has_attachment';

        // only include columns if actually searching for comments/filenames
        $searchAttachments = '';
        if (!empty(array_column($this->entity->extendedValues, 'searchAttachments'))) {
            $searchAttachments = ', GROUP_CONCAT(comment) AS comments
                , GROUP_CONCAT(real_name) AS real_names';
        }

        $this->joinsSql[] = 'LEFT JOIN (
                SELECT item_id AS up_item_id,
                    (item_id IS NOT NULL) AS has_attachment,
                    type
                    ' . $searchAttachments . '
                FROM uploads
                GROUP BY item_id, type
            ) AS uploads
                ON (uploads.up_item_id = entity.id
                    AND uploads.type = \'%1$s\')';
    }

    protected function links(EntityType $relatedOrigin): void
    {
        $table = 'items';
        if ($this->entity->entityType === EntityType::Experiments) {
            $table = 'experiments';
        }

        $related = '2items';
        if ($relatedOrigin === EntityType::Experiments) {
            $related = '2experiments';
        }

        $this->joinsSql[] = "LEFT JOIN $table$related AS linkst
            ON (linkst.item_id = entity.id)";
    }

    protected function steps(): void
    {
        $this->selectSql[] = "SUBSTRING_INDEX(GROUP_CONCAT(
                stepst.next_step
                ORDER BY steps_ordering, steps_id
                SEPARATOR '|'
            ), '|', 1) AS next_step";
        $this->joinsSql[] = 'LEFT JOIN (
                SELECT item_id AS steps_item_id,
                    body AS next_step,
                    ordering AS steps_ordering,
                    id AS steps_id,
                    finished AS finished
                FROM %1$s_steps
                WHERE finished = 0
            ) AS stepst
                ON (stepst.steps_item_id = entity.id)';
    }

    protected function comments(): void
    {
        $this->selectSql[] = 'commentst.recent_comment,
            (commentst.recent_comment IS NOT NULL) AS has_comment';
        $this->joinsSql[] = 'LEFT JOIN (
                SELECT MAX(created_at) AS recent_comment,
                    item_id
                FROM %1$s_comments
                GROUP BY item_id
            ) AS commentst
                ON (commentst.item_id = entity.id)';
    }

    private function tags(): void
    {
        $this->selectSql[] = "GROUP_CONCAT(
                DISTINCT tags.tag
                ORDER BY tags.id SEPARATOR '|'
            ) as tags,
            GROUP_CONCAT(DISTINCT tags.id) as tags_id";
        $this->joinsSql[] = 'LEFT JOIN tags2entity
                ON (tags2entity.item_id = entity.id
                    AND tags2entity.item_type = \'%1$s\')
            LEFT JOIN tags
                ON (tags.id = tags2entity.tag_id)';
    }

    private function teamEvents(): void
    {
        if ($this->entity instanceof Experiments) {
            $eventsColumn = 'experiment';
        } elseif ($this->entity instanceof Items) {
            $this->selectSql[] = 'entity.is_bookable';
            $eventsColumn = 'item_link = entity.id OR team_events.item';
        } elseif ($this->entity instanceof Templates) {
            return;
        } else {
            throw new IllegalActionException('Nope.');
        }
        $this->selectSql[] = "GROUP_CONCAT(
                DISTINCT team_events.start
                ORDER BY team_events.start
                SEPARATOR '|'
            ) AS events_start,
            GROUP_CONCAT(
                DISTINCT team_events.item
                ORDER BY team_events.start
                SEPARATOR '|'
            ) AS events_start_itemid";


        // only select events from the future
        $this->joinsSql[] = "LEFT JOIN team_events
            ON ((team_events.$eventsColumn = entity.id)
                AND team_events.start > NOW())";
    }

    private function usersTeams(): void
    {
        $this->selectSql[] = "users.firstname,
            users.lastname,
            users.orcid,
            CONCAT(users.firstname, ' ', users.lastname) AS fullname,
            teams.name AS team_name";

        $this->joinsSql[] = 'LEFT JOIN users
            ON (users.userid = entity.userid)';
        $this->joinsSql[] = sprintf(
            'LEFT JOIN users2teams
                ON (users2teams.users_id = users.userid
                    AND users2teams.teams_id = %d)
            LEFT JOIN teams ON (entity.team = teams.id)',
            $this->entity->Users->team ?? 0,
        );
    }

    /**
     * anon filter
     */
    private function canAnon(string $can): string
    {
        return sprintf(
            "entity.%s->'$.base' = %s",
            $can,
            BasePermissions::Full->value,
        );
    }

    /**
     * base pub filter
     */
    private function canBasePub(string $can): string
    {
        return sprintf(
            "entity.%s->'$.base' = %d",
            $can,
            BasePermissions::Full->value,
        );
    }

    /**
     * base org filter
     */
    private function canBaseOrg(string $can): string
    {
        return sprintf(
            "entity.%s->'$.base' = %d",
            $can,
            BasePermissions::Organization->value,
        );
    }

    /**
     * base team filter
     */
    private function canBaseTeam(string $can): string
    {
        return sprintf(
            "(entity.%s->'$.base' = %d
                AND users2teams.teams_id = entity.team)",
            $can,
            BasePermissions::Team->value,
        );
    }

    /**
     * base user filter
     * entities are accessible for admins too
     */
    private function canBaseUser(string $can): string
    {
        return sprintf(
            "(entity.%s->'$.base' = %d
                AND entity.userid = %s
                AND users2teams.teams_id = entity.team)",
            $can,
            BasePermissions::User->value,
            $this->entity->Users->isAdmin
                ? 'users2teams.users_id'
                : ':userid',
        );
    }

    /**
     * base user only filter
     * entities are listed only if we own them
     */
    private function canBaseUserOnly(string $can): string
    {
        return sprintf(
            "(entity.%s->'$.base' = %d
                AND entity.userid = :userid
                AND users2teams.teams_id = entity.team)",
            $can,
            BasePermissions::UserOnly->value,
        );
    }

    /**
     * teams filter
     */
    private function canTeams(string $can): string
    {
        $UsersHelper = new UsersHelper($this->entity->Users->userData['userid']);
        $teamsOfUser = $UsersHelper->getTeamsIdFromUserid();
        if (!empty($teamsOfUser)) {
            // JSON_OVERLAPS checks for the intersection of two arrays
            // for instance [4,5,6] vs [2,6] has 6 in common -> 1 (true)
            return sprintf(
                "JSON_OVERLAPS(entity.%s->'$.teams', CAST('[%s]' AS JSON))",
                $can,
                implode(', ', $teamsOfUser),
            );
        }
        return '0';
    }

    /**
     * teamgroups filter
     */
    private function canTeamGroups(string $can): string
    {
        $teamgroupsOfUser = array_column($this->entity->TeamGroups->readGroupsFromUser(), 'id');
        if (!empty($teamgroupsOfUser)) {
            // JSON_OVERLAPS checks for the intersection of two arrays
            // for instance [4,5,6] vs [2,6] has 6 in common -> 1 (true)
            return sprintf(
                "JSON_OVERLAPS(entity.%s->'$.teamgroups', CAST('[%s]' AS JSON))",
                $can,
                implode(', ', $teamgroupsOfUser),
            );
        }
        return '0';
    }

    /**
     * users filter
     */
    private function canUsers(string $can): string
    {
        return ":userid MEMBER OF (entity.$can->>'$.users')";
    }
}
