<?php

/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2022 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

declare(strict_types=1);

namespace Elabftw\Elabftw;

use Elabftw\Enums\BasePermissions;
use Elabftw\Exceptions\UnprocessableContentException;
use Elabftw\Models\Config;
use Elabftw\Models\TeamGroups;
use Elabftw\Models\Teams;
use Elabftw\Models\Users\Users;

use function array_flip;
use function array_map;
use function array_key_exists;
use function json_decode;

/**
 * Help with translation of permission json into meaningful data
 */
final class PermissionsHelper
{
    /**
     * Make the permissions json string an array with human readable content, translate the ids
     * The json can contain arbitrary ids, so what gets resolved (and how) depends on the requester:
     * hidden teams and their groups are skipped, and emails are masked, see the readNamesFromIds() functions
     */
    public function translate(Users $requester, string $json): array
    {
        $permArr = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
        $result = array();

        $result['teams'] = new Teams($requester)->readNamesFromIds($permArr['teams'] ?? array());
        $result['teamgroups'] = new TeamGroups($requester)->readNamesFromIds($permArr['teamgroups'] ?? array());
        $result['users'] = $requester->readNamesFromIds($permArr['users'] ?? array());

        return $result;
    }

    /**
     * When we need to build a select menu with the base entries
     */
    public function getAssociativeArray(): array
    {
        $Config = Config::getConfig();
        $base = BasePermissions::getActiveBase($Config->configArr);

        if (empty($base)) {
            throw new UnprocessableContentException('At least one permission needs to be enabled in the config.');
        }

        return $base;
    }

    /**
     * Builds an array used by extended search
     */
    public function getExtendedSearchAssociativeArray(): array
    {
        $Config = Config::getConfig();
        $active = BasePermissions::getActiveBase($Config->configArr);

        // build the associative array with only active permissions
        $englishBase = array();
        foreach (BasePermissions::cases() as $permission) {
            if (array_key_exists($permission->value, $active)) {
                $englishBase[$permission->slug()] = $permission->value;
            }
        }

        $flipped = array_flip(array_map('strtolower', $this->getAssociativeArray()));
        return $flipped + $englishBase;
    }
}
