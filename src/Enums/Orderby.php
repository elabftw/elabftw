<?php

/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2022 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

declare(strict_types=1);

namespace Elabftw\Enums;

enum Orderby: string
{
    case Category = 'cat';
    case Comment = 'comment';
    case CreatedAt = 'created_at';
    case Customid = 'customid';
    case Date = 'date';
    case Filesize = 'filesize';
    case Id = 'id';
    case Lastchange = 'lastchange';
    case Ordering = 'ordering';
    case Rating = 'rating';
    case Status = 'status';
    case Title = 'title';
    case User = 'user';

    public function toSql(): string
    {
        return match ($this) {
            self::Category => 'categoryt.title',
            self::Comment => 'recent_comment',
            self::CreatedAt => 'created_at',
            self::Customid => 'entity.custom_id',
            self::Date => 'date',
            self::Filesize => 'filesize',
            self::Id => 'entity.id',
            self::Lastchange => 'entity.modified_at',
            self::Ordering => 'ordering',
            self::Rating => 'entity.rating',
            self::Status => 'statust.title',
            self::Title => 'entity.title',
            self::User => 'CONCAT(users.firstname, " ", users.lastname)',
        };
    }
}
