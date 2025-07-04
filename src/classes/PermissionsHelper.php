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
use Elabftw\Exceptions\IllegalActionException;
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
        $base = array();

        // add settings based on the main config
        $baseAllowed = array(
            'allow_team' => BasePermissions::Team,
            'allow_user' => BasePermissions::User,
            'allow_full' => BasePermissions::Full,
            'allow_organization' => BasePermissions::Organization,
            'allow_useronly' => BasePermissions::UserOnly,
        );

        foreach ($baseAllowed as $configKey => $permissionEnum) {
            if (isset($Config->configArr[$configKey]) && $Config->configArr[$configKey] !== '0') {
                $base[$permissionEnum->value] = $permissionEnum->toHuman();
            }
        }

        if (empty($base)) {
            throw new IllegalActionException('At least one permission needs to be enabled in the config.');
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
