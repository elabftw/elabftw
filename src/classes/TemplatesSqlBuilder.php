<?php

/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2024 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

declare(strict_types=1);

namespace Elabftw\Elabftw;

use Override;

class TemplatesSqlBuilder extends EntitySqlBuilder
{
    #[Override]
    protected function entitySelect(bool $fullSelect): void
    {
        // get all the columns of entity table
        $this->selectSql[] = '
            entity.id,
            entity.userid,
            entity.created_at,
            entity.modified_at,
            entity.team,
            entity.title,
            entity.status,
            entity.body,
            entity.category,
            entity.status,
            entity.ordering,
            entity.canread,
            entity.canwrite,
            entity.canread_is_immutable,
            entity.canwrite_is_immutable,
            entity.canread_target,
            entity.canwrite_target,
            entity.content_type,
            entity.locked,
            entity.lockedby,
            entity.locked_at,
            entity.metadata,
            entity.state,
            (pin_experiments_templates2users.entity_id IS NOT NULL) AS is_pinned';
        $this->joinsSql[] = 'LEFT JOIN pin_experiments_templates2users
                ON (entity.id = pin_experiments_templates2users.entity_id
                    AND pin_experiments_templates2users.users_id = :userid)';
    }

    #[Override]
    protected function status(): void
    {
        $this->selectSql[] = 'statust.title AS status_title,
            statust.color AS status_color';
        // use experiments_status
        $this->joinsSql[] = 'LEFT JOIN experiments_status AS statust
            ON (statust.id = entity.status)';
    }

    #[Override]
    protected function comments(): void
    {
        return;
    }
}
