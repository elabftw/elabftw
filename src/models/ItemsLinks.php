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
 * All about Items Links
 */
class ItemsLinks extends AbstractLinks
{
    protected function getTargetType(): string
    {
        return 'items';
    }

    protected function getTargetPage(): string
    {
        return 'database';
    }

    protected function getCatTable(): string
    {
        return 'items_types';
    }

    protected function getStatusTable(): string
    {
        return 'items_status';
    }

    protected function getTable(): string
    {
        if ($this->Entity instanceof Experiments) {
            return 'experiments_links';
        }
        if ($this->Entity instanceof Templates) {
            return 'experiments_templates_links';
        }
        if ($this->Entity instanceof ItemsTypes) {
            return 'items_types_links';
        }
        return 'items_links';
    }

    protected function getImportTargetTable(): string
    {
        return 'items_links';
    }

    protected function getTemplateTable(): string
    {
        if ($this->Entity instanceof Experiments) {
            return 'experiments_templates_links';
        }
        return 'items_types_links';
    }

    protected function getRelatedTable(): string
    {
        if ($this->Entity instanceof Experiments) {
            return 'items2experiments';
        }
        return 'items_links';
    }
}
