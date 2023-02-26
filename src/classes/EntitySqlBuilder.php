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
use Elabftw\Exceptions\IllegalActionException;
use Elabftw\Models\AbstractEntity;
use Elabftw\Models\Experiments;
use Elabftw\Models\Items;

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
            $select = 'SELECT DISTINCT entity.*,
                GROUP_CONCAT(DISTINCT (team_events.experiment IS NOT NULL OR team_events.item_link IS NOT NULL)) AS is_bound,
                GROUP_CONCAT(DISTINCT team_events.item) AS events_item_id,
                GROUP_CONCAT(DISTINCT team_events.id) AS events_id,
                "' . $this->entity->page . '" AS page,
                "' . $this->entity->type. '" AS type,';
        } else {
            // only get the columns interesting for show mode
            $select = 'SELECT DISTINCT entity.id,
                entity.title,
                entity.date,
                entity.category,
                entity.rating,
                entity.userid,
                entity.locked,
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
            categoryt.id AS category_id,
            categoryt.title AS category,
            categoryt.color,
            users.firstname, users.lastname, users.orcid,
            CONCAT(users.firstname, ' ', users.lastname) AS fullname,
            commentst.recent_comment,
            (commentst.recent_comment IS NOT NULL) AS has_comment";

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

        $categoryTable = $this->entity->type === 'experiments' ? 'status' : 'items_types';
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
            $select .= ', categoryt.bookable';
            $eventsColumn = 'item_link';
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
        return sprintf(implode(' ', $sqlArr), $this->entity->type);
    }
}
