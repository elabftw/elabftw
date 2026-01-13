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
use Elabftw\Interfaces\SqlBuilderInterface;
use Elabftw\Models\AbstractEntity;
use Elabftw\Models\AbstractTemplateEntity;
use Elabftw\Models\Experiments;
use Elabftw\Models\Items;
use Elabftw\Models\Templates;
use Override;

use function array_column;
use function array_unique;
use function implode;
use function sprintf;

final class EntitySqlBuilder implements SqlBuilderInterface
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
    #[Override]
    public function getReadSqlBeforeWhere(
        bool $getTags = true,
        bool $fullSelect = false,
        ?EntityType $relatedOrigin = null,
    ): string {
        $this->entitySelect($fullSelect);
        $this->compounds();
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

        $sql = array(
            'SELECT',
            implode(', ', $this->selectSql),
            'FROM %1$s AS entity',
            implode(' ', $this->joinsSql),
        );

        // replace all %1$s by 'experiments' or 'items', there are many more than the one in FROM
        return sprintf(implode(' ', $sql), $this->entity->entityType->value);
    }

    #[Override]
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
                entity.status,
                entity.team,
                entity.rating,
                entity.userid,
                entity.locked,
                entity.state,
                entity.canread,
                entity.canwrite,
                entity.canread_is_immutable,
                entity.canwrite_is_immutable,
                entity.created_at,
                entity.modified_at,
                entity.timestamped';
            // only include columns (created_at, locked_at, timestamped_at, entity.metadata) if actually searching for it
            if (!empty(array_column($this->entity->extendedValues, 'additional_columns'))) {
                $this->selectSql[] = implode(', ', array_unique(array_column($this->entity->extendedValues, 'additional_columns')));
            }
        }
        $this->selectSql[] = sprintf('(pin_%s2users.entity_id IS NOT NULL) AS is_pinned', $this->entity->entityType->value);
        $this->joinsSql[] = sprintf('LEFT JOIN pin_%1$s2users
            ON (entity.id = pin_%1$s2users.entity_id
                AND pin_%1$s2users.users_id = %2$d)', $this->entity->entityType->value, $this->entity->Users->userData['userid']);
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
                : 'items_categories',
        );
    }

    protected function compounds(): void
    {
        $this->joinsSql[] = 'LEFT JOIN compounds2%1$s AS compoundslinks ON compoundslinks.entity_id = entity.id
            LEFT JOIN compounds ON compoundslinks.compound_id = compounds.id';
    }

    protected function status(): void
    {
        $this->selectSql[] = 'statust.title AS status_title,
            statust.color AS status_color';
        $this->joinsSql[] = 'LEFT JOIN ' . $this->getStatusTable() . ' AS statust
            ON (statust.id = entity.status)';
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
        // any_value is necessary to silence the nonaggregated column error
        $this->selectSql[] = 'ANY_VALUE(st.body) AS next_step';
        $this->joinsSql[] = 'LEFT JOIN %1$s_steps AS st
            ON st.item_id = entity.id
            AND st.finished = 0
            AND st.ordering = (
                SELECT MIN(ordering)
                FROM %1$s_steps
                WHERE item_id = entity.id
                AND finished = 0
            )';
    }

    protected function comments(): void
    {
        $this->selectSql[] = 'ANY_VALUE(cmt.created_at) AS recent_comment';
        $this->joinsSql[] = 'LEFT JOIN %1$s_comments AS cmt
            ON cmt.item_id = entity.id
            AND cmt.created_at = (
                SELECT MAX(created_at)
                FROM %1$s_comments
                WHERE item_id = entity.id
            )';
    }

    /**
     * anon filter
     */
    protected function canAnon(string $can): string
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
    protected function canBasePub(string $can): string
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
    protected function canBaseOrg(string $can): string
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
    protected function canBaseTeam(string $can): string
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
    protected function canBaseUser(string $can): string
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
    protected function canBaseUserOnly(string $can): string
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
    protected function canTeams(string $can): string
    {
        // ultra admin has userid=null during cli eln export so we use the team id
        $teamsOfUser = array($this->entity->Users->userData['team']);

        if (!empty($this->entity->Users->userData['teams'])) {
            $teamsOfUser = array_column($this->entity->Users->userData['teams'], 'id');
        }

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
    protected function canTeamGroups(string $can): string
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
    protected function canUsers(string $can): string
    {
        return ":userid MEMBER OF (entity.$can->>'$.users')";
    }

    private function getStatusTable(): string
    {
        if ($this->entity instanceof Experiments || $this->entity instanceof Templates) {
            return 'experiments_status';
        }
        return 'items_status';
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
        } elseif ($this->entity instanceof AbstractTemplateEntity) {
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
                ON (users2teams.users_id = entity.userid and users2teams.teams_id = %d)
            LEFT JOIN teams ON (entity.team = teams.id)',
            $this->entity->Users->team ?? 0,
        );
    }
}
