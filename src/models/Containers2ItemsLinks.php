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
use Override;

/**
 * Container <-> items link
 */
final class Containers2ItemsLinks extends AbstractContainersLinks
{
    #[Override]
    protected function getTable(): string
    {
        return 'containers2items';
    }

    #[Override]
    protected function getTargetType(): EntityType
    {
        return EntityType::Items;
    }

    #[Override]
    protected function getCatTable(): string
    {
        return 'items_types';
    }

    #[Override]
    protected function getStatusTable(): string
    {
        return 'items_status';
    }

    #[Override]
    protected function getImportTargetTable(): string
    {
        return 'containers2items';
    }
}
