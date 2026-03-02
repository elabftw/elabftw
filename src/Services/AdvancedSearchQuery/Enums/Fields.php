<?php

/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @author Marcel Bolten <github@marcelbolten.de>
 * @copyright 2022 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

declare(strict_types=1);

namespace Elabftw\Services\AdvancedSearchQuery\Enums;

enum Fields: string
{
    // @deprecated use Owner
    case Author = 'author';
    case Body = 'body';
    case Category = 'category';
    case CustomId = 'custom_id';
    case Elabid = 'elabid';
    case Group = 'group';
    case Id = 'id';
    case Locked = 'locked';
    case Owner = 'owner';
    case Rating = 'rating';
    case State  = 'state';
    case Status = 'status';
    case Timestamped = 'timestamped';
    case Title = 'title';
    case Visibility = 'visibility';
}
