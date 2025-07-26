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
use Elabftw\Models\Links\AbstractCompoundsLinks;
use Elabftw\Models\Links\AbstractContainersLinks;
use Elabftw\Models\AbstractEntity;
use Elabftw\Models\Links\AbstractExperimentsLinks;
use Elabftw\Models\Links\AbstractItemsLinks;
use Elabftw\Models\Links\Compounds2ExperimentsLinks;
use Elabftw\Models\Links\Compounds2ExperimentsTemplatesLinks;
use Elabftw\Models\Links\Compounds2ItemsLinks;
use Elabftw\Models\Links\Compounds2ItemsTypesLinks;
use Elabftw\Models\Links\Containers2ExperimentsLinks;
use Elabftw\Models\Links\Containers2ItemsLinks;
use Elabftw\Models\Links\Containers2ItemsTypesLinks;
use Elabftw\Models\Links\Containers2TemplatesLinks;
use Elabftw\Models\Links\Experiments2ExperimentsLinks;
use Elabftw\Models\Links\Experiments2ItemsLinks;
use Elabftw\Models\Links\ExperimentsTemplates2ExperimentsLinks;
use Elabftw\Models\Links\ExperimentsTemplates2ItemsLinks;
use Elabftw\Models\Links\Items2ExperimentsLinks;
use Elabftw\Models\Links\Items2ItemsLinks;
use Elabftw\Models\Links\ItemsTypes2ExperimentsLinks;
use Elabftw\Models\Links\ItemsTypes2ItemsLinks;

final class LinksFactory
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

    public static function getContainersLinks(AbstractEntity $entity, ?int $id = null): AbstractContainersLinks
    {
        return match ($entity->entityType) {
            EntityType::Experiments => new Containers2ExperimentsLinks($entity, $id),
            EntityType::Items => new Containers2ItemsLinks($entity, $id),
            EntityType::Templates => new Containers2TemplatesLinks($entity, $id),
            EntityType::ItemsTypes => new Containers2ItemsTypesLinks($entity, $id),
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

    public static function getCompoundsLinks(AbstractEntity $entity, ?int $id = null): AbstractCompoundsLinks
    {
        return match ($entity->entityType) {
            EntityType::Experiments => new Compounds2ExperimentsLinks($entity, $id),
            EntityType::Items => new Compounds2ItemsLinks($entity, $id),
            EntityType::Templates => new Compounds2ExperimentsTemplatesLinks($entity, $id),
            EntityType::ItemsTypes => new Compounds2ItemsTypesLinks($entity, $id),
        };
    }
}
