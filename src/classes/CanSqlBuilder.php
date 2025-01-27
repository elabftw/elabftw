<?php

/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2024 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

declare(strict_types=1);

namespace Elabftw\Elabftw;

use Elabftw\Enums\AccessType;
use Elabftw\Enums\BasePermissions;
use Elabftw\Models\TeamGroups;
use Elabftw\Models\Users;
use Elabftw\Services\UsersHelper;

use function array_column;
use function implode;
use function sprintf;

class CanSqlBuilder
{
    public function __construct(
        private readonly Users $requester,
        private readonly AccessType $accessType,
    ) {}

    public function getCanFilter(): string
    {
        $sql = '';
        $sql .= sprintf(
            ' AND (%s)',
            implode(' OR ', array(
                $this->canBasePub(),
                $this->canBaseOrg(),
                $this->canBaseTeam(),
                $this->canBaseUser(),
                $this->canBaseUserOnly(),
                $this->canTeams(),
                $this->canTeamGroups(),
                $this->canUsers(),
            )),
        );

        return $sql;
    }

    /**
     * base pub filter
     */
    protected function canBasePub(): string
    {
        return sprintf(
            "entity.%s->'$.base' = %d",
            $this->accessType->value,
            BasePermissions::Full->value,
        );
    }

    /**
     * base org filter
     */
    protected function canBaseOrg(): string
    {
        return sprintf(
            "entity.%s->'$.base' = %d",
            $this->accessType->value,
            BasePermissions::Organization->value,
        );
    }

    /**
     * base team filter
     */
    protected function canBaseTeam(): string
    {
        return sprintf(
            "(entity.%s->'$.base' = %d
                AND users2teams.teams_id = %d)",
            $this->accessType->value,
            BasePermissions::Team->value,
            $this->requester->team ?? 0,
        );
    }

    /**
     * base user filter
     * entities are accessible for admins too
     */
    protected function canBaseUser(): string
    {
        return sprintf(
            "(entity.%s->'$.base' = %d
                AND entity.userid = %d
                AND users2teams.teams_id = entity.team)",
            $this->accessType->value,
            BasePermissions::User->value,
            $this->requester->isAdmin
                ? 'users2teams.users_id'
                : $this->requester->userid ?? 0,
        );
    }

    /**
     * base user only filter
     * entities are listed only if we own them
     */
    protected function canBaseUserOnly(): string
    {
        return sprintf(
            "(entity.%s->'$.base' = %d
                AND entity.userid = %d
                AND users2teams.teams_id = entity.team)",
            $this->accessType->value,
            BasePermissions::UserOnly->value,
            $this->requester->userid ?? 0,
        );
    }

    /**
     * teams filter
     */
    protected function canTeams(): string
    {
        // FIXME why is userid a string here sometimes??
        $UsersHelper = new UsersHelper((int) $this->requester->userid);
        $teamsOfUser = $UsersHelper->getTeamsIdFromUserid();
        if (!empty($teamsOfUser)) {
            // JSON_OVERLAPS checks for the intersection of two arrays
            // for instance [4,5,6] vs [2,6] has 6 in common -> 1 (true)
            return sprintf(
                "JSON_OVERLAPS(entity.%s->'$.teams', CAST('[%s]' AS JSON))",
                $this->accessType->value,
                implode(', ', $teamsOfUser),
            );
        }
        return '1=2';
    }

    /**
     * teamgroups filter
     */
    protected function canTeamGroups(): string
    {
        $TeamGroups = new TeamGroups($this->requester);
        $teamgroupsOfUser = array_column($TeamGroups->readGroupsFromUser(), 'id');
        if (!empty($teamgroupsOfUser)) {
            // JSON_OVERLAPS checks for the intersection of two arrays
            // for instance [4,5,6] vs [2,6] has 6 in common -> 1 (true)
            return sprintf(
                "JSON_OVERLAPS(entity.%s->'$.teamgroups', CAST('[%s]' AS JSON))",
                $this->accessType->value,
                implode(', ', $teamgroupsOfUser),
            );
        }
        return '1=2';
    }

    /**
     * users filter
     */
    protected function canUsers(): string
    {
        return sprintf("%d MEMBER OF (entity.%s->>'$.users')", $this->requester->userid ?? 0, $this->accessType->value);
    }
}
