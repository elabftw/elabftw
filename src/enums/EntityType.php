<?php declare(strict_types=1);
/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2023 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

namespace Elabftw\Enums;

enum EntityType: string
{
    case Experiments = 'experiments';
    case Templates = 'experiments_templates';
    case Items = 'items';
    case ItemsTypes = 'items_types';
}
