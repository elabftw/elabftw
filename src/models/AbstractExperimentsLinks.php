<?php

/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2022 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

declare(strict_types=1);

namespace Elabftw\Models;

use Elabftw\Enums\EntityType;

/**
 * For links pointing to experiments
 */
abstract class AbstractExperimentsLinks extends AbstractLinks
{
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
        return 'experiments2experiments';
    }

    protected function getTemplateTable(): string
    {
        if ($this->Entity instanceof Items || $this->Entity instanceof ItemsTypes) {
            return 'items_types2experiments';
        }
        return 'experiments_templates2experiments';
    }

    protected function getRelatedTable(): string
    {
        if ($this->Entity instanceof Experiments) {
            return 'experiments2experiments';
        }
        return 'experiments2items';
    }
}
