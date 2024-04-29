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

/**
 * All about Experiments Links
 */
class ExperimentsLinks extends AbstractLinks
{
    protected function getTargetType(): string
    {
        return 'experiments';
    }

    protected function getTargetPage(): string
    {
        return 'experiments';
    }

    protected function getCatTable(): string
    {
        return 'experiments_categories';
    }

    protected function getStatusTable(): string
    {
        return 'experiments_status';
    }

    protected function getTable(): string
    {
        if ($this->Entity instanceof Experiments) {
            return 'experiments2experiments';
        }
        if ($this->Entity instanceof Templates) {
            return 'experiments_templates2experiments';
        }
        return 'items2experiments';
    }

    protected function getImportTargetTable(): string
    {
        return 'experiments2experiments';
    }

    protected function getTemplateTable(): string
    {
        return 'experiments_templates_links';
    }

    protected function getRelatedTable(): string
    {
        if ($this->Entity instanceof Experiments) {
            return 'experiments2experiments';
        }
        return 'experiments_links';
    }
}
