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
use Elabftw\Models\Users\AnonymousUser;
use Elabftw\Models\Users\Users;

use function array_column;
use function implode;
use function sprintf;

final class CanSqlBuilder
{
    /**
     * @param string $alias the alias of the table with the permission columns in the query, also used for compounds
     */
    public function __construct(
        private readonly Users $requester,
        private readonly AccessType $accessType,
        private readonly string $alias = 'entity',
    ) {}

    public function getCanFilter(): string
    {
        return ' AND ' . $this->getCanCondition();
    }

    /**
     * The condition alone, for a JOIN or a CASE: the query must bind :userid
     */
    public function getCanCondition(): string
    {
        $sql = sprintf(
            '(%s)',
            implode(' OR ', array(
                $this->canOwner(),
                $this->canBasePubOrg(),
                $this->canBaseTeam(),
                $this->canBaseUser(),
                $this->canBaseUserOnly(),
                $this->canTeams(),
                $this->canTeamGroups(),
                $this->canUsers(),
            )),
        );
        if ($this->requester instanceof AnonymousUser) {
            return sprintf('(%s AND %s)', $this->canAnon(), $sql);
        }
        return $sql;
    }

    /**
     * anon filter
     */
    protected function canAnon(): string
    {
        return sprintf(
            '%s.canread_base = %d',
            $this->alias,
            BasePermissions::Full->value,
        );
    }

    /**
     * The owner can always, whatever the base and the team: like Permissions::getCan(), so what can be opened is also listed
     */
    protected function canOwner(): string
    {
        return sprintf('%s.userid = :userid', $this->alias);
    }

    /**
     * base pub / org filter
     */
    protected function canBasePubOrg(): string
    {
        // a restricted user only gets access through ownership or explicit sharing with them or one of their teamgroups
        if ($this->requester->isRestricted()) {
            return '1=2';
        }
        return sprintf(
            '%s.%s_base IN (%d, %d)',
            $this->alias,
            $this->accessType->value,
            BasePermissions::Full->value,
            BasePermissions::Organization->value,
        );
    }

    /**
     * base team filter
     */
    protected function canBaseTeam(): string
    {
        // a restricted user only gets access through ownership or explicit sharing with them or one of their teamgroups
        if ($this->requester->isRestricted()) {
            return '1=2';
        }
        return sprintf(
            '(%1$s.%2$s_base = %3$d
                AND %1$s.team = %4$d)',
            $this->alias,
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
        // a restricted user only gets access through ownership or explicit sharing with them or one of their teamgroups
        if ($this->requester->isRestricted()) {
            return '1=2';
        }
        return sprintf(
            '(%1$s.%2$s_base = %3$d
                AND (%1$s.userid = :userid
                    OR EXISTS (
                        SELECT 1
                        FROM users2teams u2t
                        WHERE u2t.users_id = :userid
                          AND u2t.teams_id = %1$s.team
                          AND u2t.is_admin = 1
                          AND u2t.is_archived = 0
                      )
                )
            )',
            $this->alias,
            $this->accessType->value,
            BasePermissions::User->value,
        );
    }

    /**
     * base user only filter
     * entities are listed only if we own them
     */
    protected function canBaseUserOnly(): string
    {
        // a restricted user only gets access through ownership or explicit sharing with them or one of their teamgroups
        if ($this->requester->isRestricted()) {
            return '1=2';
        }
        return sprintf(
            '(%1$s.%2$s_base = %3$d
                AND %1$s.userid = %4$d)',
            $this->alias,
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
        // a restricted user only gets access through ownership or explicit sharing with them or one of their teamgroups
        if ($this->requester->isRestricted()) {
            return '1=2';
        }
        $teamsOfUser = $this->requester->getTeamIdsForSharing();
        if (!empty($teamsOfUser)) {
            // grant access when the entity is explicitly shared with at least
            // one team in which the requester still has an active membership
            return sprintf(
                "JSON_OVERLAPS(%s.%s->'$.teams', CAST('[%s]' AS JSON))",
                $this->alias,
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
        // TODO include the teamgroups in Users->readOneFull
        $TeamGroups = new TeamGroups($this->requester);
        $teamgroupsOfUser = array_column($TeamGroups->readGroupsFromUser(), 'id');
        if (!empty($teamgroupsOfUser)) {
            // JSON_OVERLAPS checks for the intersection of two arrays
            // for instance [4,5,6] vs [2,6] has 6 in common -> 1 (true)
            return sprintf(
                "JSON_OVERLAPS(%s.%s->'$.teamgroups', CAST('[%s]' AS JSON))",
                $this->alias,
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
        return sprintf("%d MEMBER OF (%s.%s->>'$.users')", $this->requester->userid ?? 0, $this->alias, $this->accessType->value);
    }
}
