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
use Elabftw\Exceptions\ImproperActionException;
use Elabftw\Models\Experiments;
use Elabftw\Models\Items;
use Elabftw\Models\Templates;
use Override;

use function sprintf;

/**
 * For links pointing to items
 */
abstract class AbstractItemsLinks extends AbstractLinks
{
    #[Override]
    protected function getTargetType(): EntityType
    {
        return EntityType::Items;
    }

    #[Override]
    protected function getCatTable(): string
    {
        return 'items_categories';
    }

    #[Override]
    protected function getStatusTable(): string
    {
        return 'items_status';
    }

    #[Override]
    protected function getImportTargetTable(): string
    {
        return 'items2items';
    }

    #[Override]
    protected function getOtherImportTargetTable(): string
    {
        return 'items2experiments';
    }

    #[Override]
    protected function getTemplateTable(): string
    {
        if ($this->Entity instanceof Experiments || $this->Entity instanceof Templates) {
            return 'experiments_templates2items';
        }
        return 'items_types2items';
    }

    #[Override]
    protected function getRelatedTable(): string
    {
        // be strict here: see comment on AbstractExperimentsLinks
        return match (true) {
            $this->Entity instanceof Items => 'items2items',
            $this->Entity instanceof Experiments => 'items2experiments',
            default => throw new ImproperActionException(sprintf(
                'Entity type %s cannot have incoming resource links.',
                $this->Entity::class,
            )),
        };
    }
}
