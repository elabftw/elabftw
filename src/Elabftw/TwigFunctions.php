<?php

/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

declare(strict_types=1);

namespace Elabftw\Elabftw;

use DateTime;
use Elabftw\Enums\Orderby;
use Elabftw\Enums\Sort;
use Elabftw\Models\TeamGroups;
use Elabftw\Models\Teams;
use Elabftw\Models\Users\Users;
use Symfony\Component\HttpFoundation\Request;

use function memory_get_usage;
use function microtime;
use function round;

/**
 * Functions used by Twig in templates
 */
final class TwigFunctions
{
    /**
     * Get an array of integer with valid number of items per page based on the current limit
     *
     * @param int $input the current limit for the page
     */
    public static function getLimitOptions(int $input): array
    {
        $limits = array(10, 20, 50, 100);
        // if the current limit is already a standard one, no need to include it
        if (in_array($input, $limits, true)) {
            return $limits;
        }
        // now find the place where to include our limit
        $place = count($limits);
        foreach ($limits as $key => $limit) {
            if ($input < $limit) {
                $place = $key;
                break;
            }
        }
        array_splice($limits, $place, 0, array($input));
        return $limits;
    }

    public static function getGenerationTime(): float
    {
        $Request = Request::createFromGlobals();
        return round(microtime(true) - $Request->server->get('REQUEST_TIME_FLOAT'), 5);
    }

    public static function envAsBool(string $input): bool
    {
        return Env::asBool($input);
    }

    public static function getMemoryUsage(): int
    {
        return memory_get_usage();
    }

    public static function getExtendedSearchExample(): string
    {
        $examples = array(
            '"search term in quotes"',
            'termA AND date:>2023-01-01',
            'title:something OR body:"something else"',
            '(locked:yes OR timestamped:yes) AND author:"Firstname Lastname"',
            '"western blot" AND rating:5',
        );
        return $examples[array_rand($examples)];
    }

    public static function getNumberOfQueries(): int
    {
        $Db = Db::getConnection();
        return $Db->getNumberOfQueries();
    }

    /**
     * Get a formatted date relative to now
     * Input is fed directly to the modify function of DateTime
     * Output format works for SQL Datetime column (like step deadline)
     */
    public static function toDatetime(string $input): string
    {
        return (new DateTime())->modify($input)->format('Y-m-d H:i:s');
    }

    public static function extractJson(string $json, string $key): bool|int
    {
        $decoded = json_decode($json, true, 3, JSON_THROW_ON_ERROR);
        if (isset($decoded[$key])) {
            return (int) $decoded[$key];
        }
        return false;
    }

    public static function isInJsonArray(string $json, string $key, int $target): bool
    {
        $decoded = json_decode($json, true, 3, JSON_THROW_ON_ERROR);
        if (in_array($target, $decoded[$key], true)) {
            return true;
        }
        return false;
    }

    public static function canToHuman(string $json): array
    {
        $PermissionsHelper = new PermissionsHelper();
        $Users = new Users();
        return $PermissionsHelper->translate(new Teams($Users), new TeamGroups($Users), $json);
    }

    public static function getSortIcon(string $orderBy): string
    {
        $Request = Request::createFromGlobals();
        $sort = null;
        if (Orderby::tryFrom($orderBy) === Orderby::tryFrom($Request->query->getAlpha('order'))) {
            $sort = Sort::tryFrom($Request->query->getAlpha('sort'));
        }
        return $sort === null ? 'fa-sort' : $sort->toFa();
    }
}
