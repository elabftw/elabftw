<?php declare(strict_types=1);
/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @author Marcel Bolten <github@marcelbolten.de>
 * @copyright 2022 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

namespace Elabftw\Services\AdvancedSearchQuery\Enums;

enum Fields: string
{
    case Attachment = 'attachment';
    case Author = 'author';
    case Body = 'body';
    case Category = 'category';
    case Elabid = 'elabid';
    case Group = 'group';
    case Locked = 'locked';
    case Rating = 'rating';
    case Status = 'status';
    case Timestamped = 'timestamped';
    case Title = 'title';
    case Visibility = 'visibility';
}
