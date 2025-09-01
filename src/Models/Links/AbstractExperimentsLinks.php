<?php

/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2022 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

declare(strict_types=1);

namespace Elabftw\Models\Links;

use Elabftw\Enums\EntityType;
use Elabftw\Models\Experiments;
use Elabftw\Models\Items;
use Elabftw\Models\ItemsTypes;
use Override;

/**
 * For links pointing to experiments
 */
abstract class AbstractExperimentsLinks extends AbstractLinks
{
    #[Override]
    protected function getTargetType(): EntityType
    {
        return EntityType::Experiments;
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
        return 'experiments2experiments';
    }

    #[Override]
    protected function getOtherImportTargetTable(): string
    {
        return 'experiments2items';
    }

    #[Override]
    protected function getTemplateTable(): string
    {
        if ($this->Entity instanceof Items || $this->Entity instanceof ItemsTypes) {
            return 'items_types2experiments';
        }
        return 'experiments_templates2experiments';
    }

    #[Override]
    protected function getRelatedTable(): string
    {
        if ($this->Entity instanceof Experiments) {
            return 'experiments2experiments';
        }
        return 'experiments2items';
    }
}
