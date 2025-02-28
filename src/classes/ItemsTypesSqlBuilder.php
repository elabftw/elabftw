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

use Elabftw\Enums\EntityType;
use Override;

final class ItemsTypesSqlBuilder extends EntitySqlBuilder
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
            entity.color,
            entity.title,
            entity.status,
            entity.body,
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
            entity.state';
    }

    #[Override]
    protected function status(): void
    {
        $this->selectSql[] = 'statust.title AS status_title,
            statust.color AS status_color';
        // use items_status as there are no items_types_status table
        $this->joinsSql[] = 'LEFT JOIN items_status AS statust
            ON (statust.id = entity.status)';
    }

    #[Override]
    protected function comments(): void
    {
        return;
    }

    #[Override]
    protected function category(): void
    {
        return;
    }

    #[Override]
    protected function steps(): void
    {
        return;
    }

    #[Override]
    protected function links(EntityType $relatedOrigin): void
    {
        return;
    }

    #[Override]
    protected function uploads(): void
    {
        return;
    }
}
