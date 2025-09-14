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
use Elabftw\Models\Users\Users;

enum EntityType: string
{
    case Experiments = 'experiments';
    case Templates = 'experiments_templates';
    case Items = 'items';
    case ItemsTypes = 'items_types';

    public function toInstance(Users $users, ?int $entityId = null, ?bool $bypassReadPermission = null, ?bool $bypassWritePermission = null): AbstractEntity
    {
        return match ($this) {
            self::Experiments => new Experiments($users, $entityId, $bypassReadPermission, $bypassWritePermission),
            self::Items => new Items($users, $entityId, $bypassReadPermission, $bypassWritePermission),
            self::Templates => new Templates($users, $entityId, $bypassReadPermission, $bypassWritePermission),
            self::ItemsTypes => new ItemsTypes($users, $entityId, $bypassReadPermission, $bypassWritePermission),
        };
    }

    public function toTemplateType(Users $users, ?int $entityId = null, ?bool $bypassReadPermission = null, ?bool $bypassWritePermission = null): AbstractEntity
    {
        return match ($this) {
            self::Experiments,
            self::Templates  => new Templates($users, $entityId, $bypassReadPermission, $bypassWritePermission),
            self::Items,
            self::ItemsTypes => new ItemsTypes($users, $entityId, $bypassReadPermission, $bypassWritePermission),
        };
    }

    public function toTemplatePage(): string
    {
        return match ($this) {
            self::Items, self::ItemsTypes => 'resources-templates.php',
            default => 'templates.php',
        };
    }

    public function toCategoryPage(): string
    {
        $prefix = match ($this) {
            self::Items, self::ItemsTypes => 'resources',
            default => 'experiments',
        };
        return $prefix . '-categories.php';
    }

    public function toStatusPage(): string
    {
        $prefix = match ($this) {
            self::Items, self::ItemsTypes => 'resources',
            default => 'experiments',
        };
        return $prefix . '-status.php';
    }

    // for use in the "genre" attribute of .eln node
    public function toGenre(): string
    {
        return match ($this) {
            self::Experiments => 'experiment',
            self::Items => 'resource',
            self::Templates => 'experiment template',
            self::ItemsTypes => 'resource template',
        };
    }

    public function toPage(): string
    {
        return match ($this) {
            self::Experiments => 'experiments.php',
            self::Items => 'database.php',
            self::Templates => 'templates.php',
            self::ItemsTypes => 'resources-templates.php',
        };
    }
}
