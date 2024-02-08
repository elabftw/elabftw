<?php declare(strict_types=1);
/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @author Marcel Bolten <github@marcelbolten.de>
 * @copyright 2024 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

namespace Elabftw\Enums;

enum SearchType
{
    case Category;
    case Extended;
    case Owner;
    case Query;
    case Related;
    case Status;
    case SearchPage;
    case Tags;
    case Undefined;
}
