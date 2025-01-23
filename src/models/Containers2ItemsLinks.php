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
 * Container <-> items link
 */
class Containers2ItemsLinks extends AbstractContainersLinks
{
    protected function getTable(): string
    {
        return 'containers2items';
    }

    protected function getTargetType(): EntityType
    {
        return EntityType::Items;
    }

    protected function getCatTable(): string
    {
        return 'items_types';
    }

    protected function getStatusTable(): string
    {
        return 'items_status';
    }

    protected function getImportTargetTable(): string
    {
        return 'containers2items';
    }
}
