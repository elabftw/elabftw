<?php

/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2024 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

declare(strict_types=1);

namespace Elabftw\Models;

use Elabftw\Enums\EntityType;

/**
 * Container <-> items types link
 */
class Containers2ItemsTypesLinks extends AbstractContainersLinks
{
    protected function getTable(): string
    {
        return 'containers2items_types';
    }

    protected function getTargetType(): EntityType
    {
        return EntityType::ItemsTypes;
    }

    protected function getCatTable(): string
    {
        return 'items_types';
    }

    protected function getStatusTable(): string
    {
        return 'items_types_status';
    }

    protected function getImportTargetTable(): string
    {
        return 'containers2items_types';
    }
}
