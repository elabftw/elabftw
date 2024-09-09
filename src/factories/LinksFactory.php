<?php

/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2024 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

declare(strict_types=1);

namespace Elabftw\Factories;

use Elabftw\Enums\EntityType;
use Elabftw\Models\AbstractEntity;
use Elabftw\Models\AbstractExperimentsLinks;
use Elabftw\Models\AbstractItemsLinks;
use Elabftw\Models\Experiments2ExperimentsLinks;
use Elabftw\Models\Experiments2ItemsLinks;
use Elabftw\Models\ExperimentsTemplates2ExperimentsLinks;
use Elabftw\Models\ExperimentsTemplates2ItemsLinks;
use Elabftw\Models\Items2ExperimentsLinks;
use Elabftw\Models\Items2ItemsLinks;
use Elabftw\Models\ItemsTypes2ExperimentsLinks;
use Elabftw\Models\ItemsTypes2ItemsLinks;

class LinksFactory
{
    public static function getExperimentsLinks(AbstractEntity $entity, ?int $id = null): AbstractExperimentsLinks
    {
        return match ($entity->entityType) {
            EntityType::Experiments => new Experiments2ExperimentsLinks($entity, $id),
            EntityType::Items => new Items2ExperimentsLinks($entity, $id),
            EntityType::Templates => new ExperimentsTemplates2ExperimentsLinks($entity, $id),
            EntityType::ItemsTypes => new ItemsTypes2ExperimentsLinks($entity, $id),
        };
    }

    public static function getItemsLinks(AbstractEntity $entity, ?int $id = null): AbstractItemsLinks
    {
        return match ($entity->entityType) {
            EntityType::Experiments => new Experiments2ItemsLinks($entity, $id),
            EntityType::Items => new Items2ItemsLinks($entity, $id),
            EntityType::Templates => new ExperimentsTemplates2ItemsLinks($entity, $id),
            EntityType::ItemsTypes => new ItemsTypes2ItemsLinks($entity, $id),
        };
    }
}
