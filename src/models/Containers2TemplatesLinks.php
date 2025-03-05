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
 * Container <-> template link
 */
final class Containers2TemplatesLinks extends AbstractContainersLinks
{
    #[Override]
    protected function getTable(): string
    {
        return 'containers2experiments_templates';
    }

    #[Override]
    protected function getTargetType(): EntityType
    {
        return EntityType::Templates;
    }

    #[Override]
    protected function getCatTable(): string
    {
        return 'experiments_categories';
    }

    #[Override]
    protected function getStatusTable(): string
    {
        return 'experiments_status';
    }

    #[Override]
    protected function getImportTargetTable(): string
    {
        return 'containers2experiments_templates';
    }
}
