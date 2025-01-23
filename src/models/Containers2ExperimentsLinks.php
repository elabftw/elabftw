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
 * Container <-> experiment link
 */
class Containers2ExperimentsLinks extends AbstractContainersLinks
{
    protected function getTable(): string
    {
        return 'containers2experiments';
    }

    protected function getTargetType(): EntityType
    {
        return EntityType::Experiments;
    }

    protected function getCatTable(): string
    {
        return 'experiments_categories';
    }

    protected function getStatusTable(): string
    {
        return 'experiments_status';
    }

    protected function getImportTargetTable(): string
    {
        return 'containers2experiments';
    }
}
