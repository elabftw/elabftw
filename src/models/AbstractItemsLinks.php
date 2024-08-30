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
 * For links pointing to items
 */
abstract class AbstractItemsLinks extends AbstractLinks
{
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
        return 'items2items';
    }

    protected function getTemplateTable(): string
    {
        if ($this->Entity instanceof Experiments || $this->Entity instanceof Templates) {
            return 'experiments_templates2items';
        }
        return 'items_types2items';
    }

    protected function getRelatedTable(): string
    {
        if ($this->Entity instanceof Experiments) {
            return 'items2experiments';
        }
        return 'items2items';
    }
}
