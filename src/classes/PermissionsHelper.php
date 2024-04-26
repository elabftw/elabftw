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
        $base = array(
            BasePermissions::Full->value => BasePermissions::Full->toHuman(),
            BasePermissions::Organization->value => BasePermissions::Organization->toHuman(),
            BasePermissions::Team->value => BasePermissions::Team->toHuman(),
            BasePermissions::User->value => BasePermissions::User->toHuman(),
        );

        // add the only me setting only if it is allowed by main config
        $Config = Config::getConfig();
        if ($Config->configArr['allow_useronly'] === '1') {
            $base[BasePermissions::UserOnly->value] = BasePermissions::UserOnly->toHuman();
        }
        return $base;
    }

    /**
     * Builds an array used by extended search
     */
    public function getExtendedSearchAssociativeArray(): array
    {
        $flipped = array_flip(array_map('strtolower', $this->getAssociativeArray()));
        $englishBase = array(
            'public' => BasePermissions::Full->value,
            'organization' => BasePermissions::Organization->value,
            'myteam' => BasePermissions::Team->value,
            'user' => BasePermissions::User->value,
        );
        // add the only me setting only if it is allowed by main config
        $Config = Config::getConfig();
        if ($Config->configArr['allow_useronly'] === '1') {
            $englishBase['useronly'] = BasePermissions::UserOnly->value;
        }
        return $flipped + $englishBase;
    }
}
