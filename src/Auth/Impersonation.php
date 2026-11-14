<?php

/**
 * @package   Elabftw\Elabftw
 * @author    Nicolas CARPi / Deltablot
 * @copyright 2026 Nicolas CARPi
 * @license   https://www.gnu.org/licenses/agpl-3.0.html AGPL-3.0
 * @see       https://www.elabftw.net Official website
 */

declare(strict_types=1);

namespace Elabftw\Auth;

use Elabftw\AuditEvent\ImpersonationStarted;
use Elabftw\AuditEvent\ImpersonationStopped;
use Elabftw\Enums\Messages;
use Elabftw\Exceptions\ForbiddenException;
use Elabftw\Exceptions\ImproperActionException;
use Elabftw\Models\AuditLogs;
use Elabftw\Models\Users\AuthenticatedUser;
use Elabftw\Models\Users\Users;
use Elabftw\Services\TeamsHelper;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

use function _;

/**
 * Allow a Sysadmin (any user) or an Admin (users of their team, if allowed by instance config)
 * to browse the app as another user, and come back to their own account afterwards.
 * The state lives in the session only: an api key can never impersonate.
 */
final class Impersonation
{
    public const string CONFIG_KEY = 'admins_impersonate_users';

    private const string IMPERSONATOR_USERID = 'impersonator_userid';

    private const string IMPERSONATOR_TEAM = 'impersonator_team';

    // privileges that an Admin cannot gain by impersonating someone who has them
    private const array PRIVILEGE_COLUMNS = array(
        'can_manage_compounds',
        'can_manage_inventory_locations',
    );

    public function __construct(private SessionInterface $Session, private bool $adminsCanImpersonate) {}

    public function isActive(): bool
    {
        return $this->Session->has(self::IMPERSONATOR_USERID);
    }

    public function getImpersonatorId(): ?int
    {
        return $this->isActive() ? (int) $this->Session->get(self::IMPERSONATOR_USERID) : null;
    }

    public function start(AuthenticatedUser $requester, int $targetUserid): void
    {
        // no nesting: we must always be able to go back to the real user
        if ($this->isActive() || $requester->impersonatorId !== null) {
            throw new ImproperActionException(_('You are already impersonating a user. Stop impersonating first.'));
        }
        // only the user logged in with this very session can start an impersonation
        if (
            $this->Session->get('is_auth') !== 1
            || $this->Session->has('is_anon')
            || (int) $this->Session->get('userid') !== $requester->userid
            || (int) $this->Session->get('team') !== $requester->team
        ) {
            throw new ForbiddenException();
        }

        $team = $this->resolveTeam($requester, $targetUserid);
        $this->assertAllowed($requester, $targetUserid, $team);

        // identity changes: get a new session id
        $this->Session->migrate(true);
        $this->Session->set(self::IMPERSONATOR_USERID, $requester->userid);
        $this->Session->set(self::IMPERSONATOR_TEAM, $requester->team);
        $this->Session->set('userid', $targetUserid);
        $this->Session->set('team', $team);
        // don't carry over a team selection state from the real user
        $this->Session->remove('team_selection_required');
        $this->Session->remove('team_selection');

        AuditLogs::create(new ImpersonationStarted((int) $requester->userid, $targetUserid));
    }

    /**
     * Go back to the impersonator account
     */
    public function stop(): void
    {
        $impersonatorId = $this->getImpersonatorId() ?? throw new ImproperActionException(_('You are not impersonating anyone.'));
        $impersonatedUserid = (int) $this->Session->get('userid');
        $impersonatorTeam = (int) $this->Session->get(self::IMPERSONATOR_TEAM);

        $this->Session->migrate(true);
        $this->Session->set('userid', $impersonatorId);
        $this->Session->set('team', $impersonatorTeam);
        $this->clear();

        AuditLogs::create(new ImpersonationStopped($impersonatorId, $impersonatedUserid));
    }

    /**
     * Remove impersonation information from the session, without restoring the impersonator
     */
    public function clear(): void
    {
        $this->Session->remove(self::IMPERSONATOR_USERID);
        $this->Session->remove(self::IMPERSONATOR_TEAM);
    }

    /**
     * Called on every request while impersonating: the impersonator could have lost their privileges
     * or the instance setting could have been disabled since the impersonation started
     */
    public function validate(): void
    {
        $impersonatorId = $this->getImpersonatorId();
        if ($impersonatorId === null) {
            return;
        }
        $impersonatorTeam = (int) $this->Session->get(self::IMPERSONATOR_TEAM);
        if (!new TeamsHelper($impersonatorTeam)->isActiveUserInTeam($impersonatorId)) {
            throw new ForbiddenException();
        }
        $this->assertAllowed(
            new Users($impersonatorId, $impersonatorTeam),
            (int) $this->Session->get('userid'),
            (int) $this->Session->get('team'),
        );
    }

    /**
     * Find in which team we will be logged in as the target user
     */
    private function resolveTeam(Users $requester, int $targetUserid): int
    {
        $currentTeam = (int) $requester->team;
        // an Admin can never leave the team they are currently logged in
        if (!$requester->isSysadmin() || new TeamsHelper($currentTeam)->isActiveUserInTeam($targetUserid)) {
            return $currentTeam;
        }
        // for a Sysadmin, use the first team where the target can login
        $target = new Users($targetUserid);
        foreach ($target->getActiveTeamIds() as $teamId) {
            if (new TeamsHelper($teamId)->isActiveUserInTeam($targetUserid)) {
                return $teamId;
            }
        }
        throw new ImproperActionException(_('This user cannot be impersonated because their account is not active in any team.'));
    }

    private function assertAllowed(Users $requester, int $targetUserid, int $team): void
    {
        if ($requester->userid === $targetUserid) {
            throw new ImproperActionException(_('You cannot impersonate yourself.'));
        }
        // the target must be a user that could login in that team: validated, not archived, not expired
        if (!new TeamsHelper($team)->isActiveUserInTeam($targetUserid)) {
            throw new ImproperActionException(_('This user cannot be impersonated because their account is not active in this team.'));
        }
        if ($requester->isSysadmin()) {
            return;
        }
        if (!$this->adminsCanImpersonate) {
            throw new ForbiddenException(_('Impersonating users is not allowed for Admins on this instance.'));
        }
        // an Admin must be Admin of the team they are logged in, and stays in that team
        if ($team !== $requester->team || !new TeamsHelper($team)->isAdmin((int) $requester->userid)) {
            throw new ForbiddenException(Messages::InsufficientPermissions->toHuman());
        }
        // impersonating someone must never grant more privileges than what the Admin already has
        $target = new Users($targetUserid, $team);
        if (
            $target->isSysadmin()
            || $target->isAdminSomewhere()
            || (int) $target->userData['can_manage_users2teams'] === 1
        ) {
            throw new ForbiddenException(_('Admins can only impersonate users without elevated privileges.'));
        }
        foreach (self::PRIVILEGE_COLUMNS as $column) {
            if ((int) $target->userData[$column] === 1 && (int) $requester->userData[$column] !== 1) {
                throw new ForbiddenException(_('Admins can only impersonate users without elevated privileges.'));
            }
        }
        // a user can read entries from all their teams: impersonating them must not reveal data of a team the Admin doesn't administer
        foreach ($target->getActiveTeamIds() as $teamId) {
            if (!new TeamsHelper($teamId)->isAdmin((int) $requester->userid)) {
                throw new ForbiddenException(_('This user is also in a team where you are not Admin: you cannot impersonate them.'));
            }
        }
    }
}
