<?php declare(strict_types=1);
/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2022 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

namespace Elabftw\Enums;

enum Orderby: string
{
    case Category = 'cat';
    case Comment = 'comment';
    case Customid = 'customid';
    case Date = 'date';
    case Id = 'id';
    case Lastchange = 'lastchange';
    case Rating = 'rating';
    case Status = 'status';
    case Title = 'title';
    case User = 'user';

    public static function toSql(self $value): string
    {
        return match ($value) {
            Orderby::Category => 'categoryt.title',
            Orderby::Comment => 'commentst.recent_comment',
            Orderby::Customid => 'entity.custom_id',
            Orderby::Date => 'date',
            Orderby::Id => 'entity.id',
            Orderby::Lastchange => 'entity.modified_at',
            Orderby::Rating => 'entity.rating',
            Orderby::Status => 'statust.title',
            Orderby::Title => 'entity.title',
            Orderby::User => 'CONCAT(users.firstname, " ", users.lastname)',
        };
    }
}
