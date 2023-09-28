<?php declare(strict_types=1);
/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2022 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

namespace Elabftw\Elabftw;

use function array_column;
use function array_unique;

use Elabftw\Enums\BasePermissions;
use Elabftw\Exceptions\IllegalActionException;
use Elabftw\Models\AbstractEntity;
use Elabftw\Models\Experiments;
use Elabftw\Models\Items;
use Elabftw\Services\UsersHelper;

class EntitySqlBuilder
{
    public function __construct(private AbstractEntity $entity)
    {
    }

    /**
     * Get the SQL string for read before the WHERE
     *
     * @param bool $getTags do we get the tags too?
     * @param bool $fullSelect select all the columns of entity
     * @phan-suppress PhanPluginPrintfVariableFormatString
     */
    public function getReadSqlBeforeWhere(bool $getTags = true, bool $fullSelect = false, bool $includeMetadata = false): string
    {
        if ($fullSelect) {
            // get all the columns of entity table, we add a literal string for the page that can be used by the mention tinymce plugin code
            $select = sprintf("SELECT DISTINCT entity.*,
                GROUP_CONCAT(team_events.start ORDER BY team_events.start SEPARATOR '|') AS events_start,
                '%s' AS page,
                '%s' AS type,", $this->entity->page, $this->entity->type);
        } else {
            // only get the columns interesting for show mode
            $select = 'SELECT DISTINCT entity.id,
                entity.title,
                entity.date,
                entity.category,
                entity.status,
                entity.rating,
                entity.userid,
                entity.locked,
                entity.state,
                entity.canread,
                entity.canwrite,
                entity.modified_at,';
            // don't include the metadata column unless we really need it
            // see https://stackoverflow.com/questions/29575835/error-1038-out-of-sort-memory-consider-increasing-sort-buffer-size
            if ($includeMetadata) {
                $select .= 'entity.metadata,';
            }
            // only include columns (created_at, locked_at, timestamped_at,) if actually searching for it
            if (!empty(array_column($this->entity->extendedValues, 'additional_columns'))) {
                $select .= implode(', ', array_unique(array_column($this->entity->extendedValues, 'additional_columns'))) . ',';
            }
        }
        $select .= "uploads.up_item_id, uploads.has_attachment,
            SUBSTRING_INDEX(GROUP_CONCAT(stepst.next_step ORDER BY steps_ordering, steps_id SEPARATOR '|'), '|', 1) AS next_step,
            statust.title AS status_title,
            statust.color AS status_color,
            categoryt.title AS category_title,
            categoryt.color AS category_color,
            users.firstname, users.lastname, users.orcid,
            CONCAT(users.firstname, ' ', users.lastname) AS fullname,
            commentst.recent_comment,
            (commentst.recent_comment IS NOT NULL) AS has_comment,";

        // add a "color" column that is status for experiments and category for items
        $select .= ($this->entity instanceof Experiments) ? 'statust.color AS color, statust.title AS mainattr_title' : 'categoryt.color AS color, categoryt.title AS mainattr_title';

        $tagsSelect = '';
        $tagsJoin = '';
        if ($getTags) {
            $tagsSelect = ", GROUP_CONCAT(DISTINCT tags.tag ORDER BY tags.id SEPARATOR '|') as tags, GROUP_CONCAT(DISTINCT tags.id) as tags_ids";
            $tagsJoin = 'LEFT JOIN tags2%1$s ON (entity.id = tags2%1$s.%1$s_id) LEFT JOIN tags ON (tags2%1$s.tags_id = tags.id)';
        }

        // only include columns if actually searching for comments/filenames
        $searchAttachments = '';
        if (!empty(array_column($this->entity->extendedValues, 'searchAttachments'))) {
            $searchAttachments = ',
                GROUP_CONCAT(uploads.comment) AS comments,
                GROUP_CONCAT(uploads.real_name) AS real_names';
        }

        $uploadsJoin = 'LEFT JOIN (
            SELECT uploads.item_id AS up_item_id,
                (uploads.item_id IS NOT NULL) AS has_attachment,
                uploads.type' . $searchAttachments . '
            FROM uploads
            GROUP BY uploads.item_id, uploads.type)
            AS uploads
            ON (uploads.up_item_id = entity.id AND uploads.type = \'%1$s\')';

        $usersJoin = 'LEFT JOIN users ON (entity.userid = users.userid)';
        $teamJoin = sprintf(
            'LEFT JOIN users2teams ON (users2teams.users_id = users.userid AND users2teams.teams_id = %s)',
            $this->entity->Users->userData['team']
        );

        $categoryTable = $this->entity->type === 'experiments' ? 'experiments_categories' : 'items_types';
        $categoryJoin = 'LEFT JOIN ' . $categoryTable . ' AS categoryt ON (categoryt.id = entity.category)';

        $commentsJoin = 'LEFT JOIN (
            SELECT MAX(
                %1$s_comments.created_at) AS recent_comment,
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

        if ($this->entity instanceof Experiments) {
            $select .= ', entity.timestamped';
            $eventsColumn = 'experiment';
        } elseif ($this->entity instanceof Items) {
            $select .= ', entity.is_bookable';
            $eventsColumn = 'item_link = entity.id OR team_events.item';
        } else {
            throw new IllegalActionException('Nope.');
        }
        $eventsJoin = '';
        if ($fullSelect) {
            // only select events from the future
            $eventsJoin = 'LEFT JOIN team_events ON (team_events.' . $eventsColumn . ' = entity.id AND team_events.start > NOW())';
        }

        $sqlArr = array(
            $select,
            $tagsSelect,
            $from,
            sprintf('LEFT JOIN %s_status AS statust ON (statust.id = entity.status)', $this->entity->type),
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
        return sprintf(implode(' ', $sqlArr), $this->entity->type);
    }

    public function getCanFilter(string $can): string
    {
        $sql = '';
        // teamFilter is to restrict to the team for items only
        // as they have a team column
        $teamFilter = '';
        if ($this->entity instanceof Items) {
            $teamFilter = ' AND users2teams.teams_id = entity.team';
        }
        // for anon add an AND base = full (public)
        if ($this->entity->isAnon) {
            $sql .= sprintf(" AND JSON_EXTRACT(entity.%s, '$.base') = %s ", $can, BasePermissions::Full->value);
        }
        // add pub/org/team filter
        $sqlPublicOrg = sprintf("((JSON_EXTRACT(entity.%s, '$.base') = %d OR JSON_EXTRACT(entity.%s, '$.base') = %d) AND entity.userid = users2teams.users_id) OR ", $can, BasePermissions::Full->value, $can, BasePermissions::Organization->value);
        if ($this->entity->Users->userData['show_public']) {
            $sqlPublicOrg = sprintf("(JSON_EXTRACT(entity.%s, '$.base') = %d OR JSON_EXTRACT(entity.%s, '$.base') = %d) OR ", $can, BasePermissions::Full->value, $can, BasePermissions::Organization->value);
        }
        $sql .= sprintf(" AND ( %s (JSON_EXTRACT(entity.%s, '$.base') = %d AND users2teams.users_id = entity.userid %s) OR (JSON_EXTRACT(entity.%s, '$.base') = %d ", $sqlPublicOrg, $can, BasePermissions::MyTeams->value, $teamFilter, $can, BasePermissions::User->value);
        // admin will see the experiments with visibility user for user of their team
        if ($this->entity->Users->isAdmin) {
            $sql .= 'AND entity.userid = users2teams.users_id)';
        } else {
            $sql .= 'AND entity.userid = :userid)';
        }
        // add entities in useronly visibility only if we own them
        $sql .= sprintf(" OR (JSON_EXTRACT(entity.%s, '$.base') = %d AND entity.userid = :userid)", $can, BasePermissions::UserOnly->value);
        // look for teams
        $UsersHelper = new UsersHelper((int) $this->entity->Users->userData['userid']);
        $teamsOfUser = $UsersHelper->getTeamsIdFromUserid();
        if (!empty($teamsOfUser)) {
            foreach ($teamsOfUser as $team) {
                $sql .= sprintf(" OR (%d MEMBER OF (entity.%s->>'$.teams'))", $team, $can);
            }
        }
        // look for teamgroups
        // Note: could not find a way to only have one bit of sql to search: [4,5,6] member of [2,6] for instance, and the 6 would match
        // Only when the search is an AND between searched values we can have it (also with json_contains), so it is necessary to build a query with multiple OR ()
        $teamgroupsOfUser = array_column($this->entity->TeamGroups->readGroupsFromUser(), 'id');
        if (!empty($teamgroupsOfUser)) {
            foreach ($teamgroupsOfUser as $teamgroup) {
                $sql .= sprintf(" OR (%d MEMBER OF (entity.%s->>'$.teamgroups'))", $teamgroup, $can);
            }
        }
        // look for our userid in users part of the json
        $sql .= sprintf(" OR (:userid MEMBER OF (entity.%s->>'$.users'))", $can);
        $sql .= ')';

        return $sql;
    }
}
