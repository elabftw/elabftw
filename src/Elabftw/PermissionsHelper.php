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
use Elabftw\Exceptions\ImproperActionException;
use Elabftw\Models\Config;
use Elabftw\Models\TeamGroups;
use Elabftw\Models\Teams;

use function array_flip;
use function array_map;

/**
 * Help with translation of permission json into meaningful data
 */
final class PermissionsHelper
{
    /**
     * Make the permissions json string an array with human readable content, translate the ids
     */
    public function translate(Teams $Teams, TeamGroups $TeamGroups, string $json): array
    {
        $permArr = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
        $result = array();

        $base = BasePermissions::tryFrom($permArr['base']) ?? throw new ImproperActionException('Invalid base parameter for permissions');
        $result['base'] = $base->toHuman();
        $result['teams'] = $Teams->readNamesFromIds($permArr['teams']);
        $result['teamgroups'] = $TeamGroups->readNamesFromIds($permArr['teamgroups']);
        $result['users'] = $Teams->Users->readNamesFromIds($permArr['users']);

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
