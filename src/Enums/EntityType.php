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

    public function toInstance(Users $users, ?int $entityId = null, ?bool $bypassReadPermission = null, ?bool $bypassWritePermission = null): AbstractEntity
    {
        return match ($this) {
            $this::Experiments => new Experiments($users, $entityId, $bypassReadPermission, $bypassWritePermission),
            $this::Items => new Items($users, $entityId, $bypassReadPermission, $bypassWritePermission),
            $this::Templates => new Templates($users, $entityId, $bypassReadPermission, $bypassWritePermission),
            $this::ItemsTypes => new ItemsTypes($users, $entityId, $bypassReadPermission, $bypassWritePermission),
        };
    }

    // for use in the "genre" attribute of .eln node
    public function toGenre(): string
    {
        return match ($this) {
            $this::Experiments => 'experiment',
            $this::Items => 'resource',
            $this::Templates => 'experiment template',
            $this::ItemsTypes => 'resource template',
        };
    }

    public function toPage(): string
    {
        return match ($this) {
            $this::Experiments => 'experiments.php',
            $this::Items => 'database.php',
            $this::Templates => 'templates.php',
            $this::ItemsTypes => 'admin.php?tab=4',
        };
    }
}
