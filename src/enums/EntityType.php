<?php declare(strict_types=1);
/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2023 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

namespace Elabftw\Enums;

use Elabftw\Models\AbstractEntity;
use Elabftw\Models\Experiments;
use Elabftw\Models\Items;
use Elabftw\Models\ItemsTypes;
use Elabftw\Models\Templates;
use Elabftw\Models\Users;

enum EntityType: string
{
    case Experiments = 'experiments';
    case Templates = 'experiments_templates';
    case Items = 'items';
    case ItemsTypes = 'items_types';

    public function toInstance(Users $users, ?int $entityId = null): AbstractEntity
    {
        return match ($this) {
            EntityType::Experiments => new Experiments($users, $entityId),
            EntityType::Items => new Items($users, $entityId),
            EntityType::Templates => new Templates($users, $entityId),
            EntityType::ItemsTypes => new ItemsTypes($users, $entityId),
        };
    }
}
