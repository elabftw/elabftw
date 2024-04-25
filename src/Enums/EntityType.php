<?php

/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2023 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

declare(strict_types=1);

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
            $this::Experiments => new Experiments($users, $entityId),
            $this::Items => new Items($users, $entityId),
            $this::Templates => new Templates($users, $entityId),
            $this::ItemsTypes => new ItemsTypes($users, $entityId),
        };
    }
}
