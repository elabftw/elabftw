<?php

/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2022 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

declare(strict_types=1);

namespace Elabftw\Models;

use Elabftw\AuditEvent\PermissionLevelChanged;
use Elabftw\AuditEvent\TeamAddition;
use Elabftw\AuditEvent\TeamRemoval;
use Elabftw\Elabftw\Db;
use Elabftw\Enums\Usergroup;
use Elabftw\Enums\Users2TeamsTargets;
use Elabftw\Exceptions\IllegalActionException;
use Elabftw\Exceptions\ImproperActionException;
use Elabftw\Models\Notifications\OnboardingEmail;
use Elabftw\Services\UsersHelper;
use Elabftw\Services\TeamsHelper;
use PDO;

/**
 * Manage the link between users and teams
 */
final class Users2Teams
{
    // are onboarding emails sent in general?
    // setting for each team is checked additionally
    public bool $sendOnboardingEmailOfTeams = false;

    protected Db $Db;

    public function __construct(private Users $requester)
    {
        $this->Db = Db::getConnection();
    }

    /**
     * Add one user to one team
     */
    public function create(int $userid, int $teamid, Usergroup $group = Usergroup::User): bool
    {
        $isAdmin = ($group === Usergroup::Admin || $group === Usergroup::Sysadmin) ? 1 : 0;
        // primary key will take care of ensuring there are no duplicate tuples
        $sql = 'INSERT IGNORE INTO users2teams (`users_id`, `teams_id`, `is_admin`) VALUES (:userid, :team, :is_admin);';
        $req = $this->Db->prepare($sql);
        $req->bindValue(':userid', $userid, PDO::PARAM_INT);
        $req->bindValue(':team', $teamid, PDO::PARAM_INT);
        $req->bindValue(':is_admin', $isAdmin, PDO::PARAM_INT);
        $res = $this->Db->execute($req);
        AuditLogs::create(new TeamAddition($teamid, $isAdmin, $this->requester->userid ?? 0, $userid));

        // check onboarding email setting for each team
        $Team = new Teams(new Users(), $teamid, bypassReadPermission: true);
        if ($this->sendOnboardingEmailOfTeams && $Team->readOne()['onboarding_email_active'] === 1) {
            (new OnboardingEmail($teamid))->create($userid);
        }

        return $res;
    }

    public function patchUser2Team(array $params): int
    {
        return $this->patchIsSomething(
            Users2TeamsTargets::from($params['target']),
            (int) $params['userid'],
            (int) $params['team'],
            (int) $params['content'],
        );
    }

    /**
     * Add one user to n teams
     *
     * @param array<array-key, int> $teamIdArr this is the validated array of teams that exist
     */
    public function addUserToTeams(int $userid, array $teamIdArr, Usergroup $group = Usergroup::User): void
    {
        foreach ($teamIdArr as $teamId) {
            $this->create($userid, $teamId, $group);
        }
    }

    /**
     * Remove a user from teams
     *
     * @param array<array-key, int> $teamIdArr this is the validated array of teams that exist
     */
    public function rmUserFromTeams(int $userid, array $teamIdArr): void
    {
        foreach ($teamIdArr as $teamId) {
            $this->removeUserFromTeam($userid, $teamId);
        }
    }

    /**
     * Remove one user from a team
     */
    public function destroy(int $userid, int $teamid): array
    {
        // make sure we are Admin in the team that we are removing the user from
        $TeamsHelper = new TeamsHelper($teamid);
        if (!($this->requester->userData['is_sysadmin'] || $TeamsHelper->isAdminInTeam($this->requester->userData['userid']))) {
            throw new ImproperActionException('Cannot remove user from team if not admin of said user in that team');
        }
        return $this->removeUserFromTeam($userid, $teamid);
    }

    private function removeUserFromTeam(int $userid, int $teamid): array
    {
        // make sure that the user is in more than one team before removing the team
        $UsersHelper = new UsersHelper($userid);
        if (count($UsersHelper->getTeamsFromUserid()) === 1) {
            throw new ImproperActionException('Cannot remove last team from user: users must belong to at least one team.');
        }
        $sql = 'DELETE FROM users2teams WHERE `users_id` = :userid AND `teams_id` = :team';
        $req = $this->Db->prepare($sql);
        $req->bindParam(':userid', $userid, PDO::PARAM_INT);
        $req->bindValue(':team', $teamid, PDO::PARAM_INT);
        $this->Db->execute($req);
        // also remove any api key for that user in that team
        $ApiKeys = new ApiKeys(new Users($userid, $teamid));
        $ApiKeys->destroyInTeam($teamid);

        AuditLogs::create(new TeamRemoval($teamid, 0, $userid));
        $Users = new Users($userid);
        return $Users->readOne();
    }

    private function patchIsAdmin(int $userid, int $teamid, bool $isAdmin): int
    {
        $promoteToAdmin = $isAdmin && !$this->wasAdminAlready($userid);
        // make sure requester is admin of target user
        if (!$this->requester->isAdminOf($userid) && $this->requester->userData['is_sysadmin'] !== 1) {
            throw new IllegalActionException('User tried to patch team group of another user but they are not admin');
        }

        $TeamsHelper = new TeamsHelper($teamid);
        if (!$TeamsHelper->isAdminInTeam($this->requester->userData['userid']) && $this->requester->userData['is_sysadmin'] !== 1) {
            throw new IllegalActionException('User tried to patch team group of a team where they are not admin');
        }
        $sql = 'UPDATE users2teams SET is_admin = :is_admin WHERE `users_id` = :userid AND `teams_id` = :team';
        $req = $this->Db->prepare($sql);
        $req->bindValue(':is_admin', $isAdmin, PDO::PARAM_INT);
        $req->bindValue(':userid', $userid, PDO::PARAM_INT);
        $req->bindValue(':team', $teamid, PDO::PARAM_INT);

        $this->Db->execute($req);

        // send the admin onboarding email only when user becomes admin the first time
        if ($promoteToAdmin
            && (Config::getConfig())->configArr['onboarding_email_active'] === '1'
        ) {
            (new OnboardingEmail(-1, $promoteToAdmin))->create($userid);
        }
        /** @psalm-suppress PossiblyNullArgument */
        AuditLogs::create(new PermissionLevelChanged($this->requester->userid, $userid, Users2TeamsTargets::IsAdmin, (int) $isAdmin, $teamid));
        return (int) $isAdmin;
    }

    private function patchIsSomething(Users2TeamsTargets $what, int $userid, int $teamid, int $content): int
    {
        if ($what === Users2TeamsTargets::IsAdmin) {
            return $this->patchIsAdmin($userid, $teamid, (bool) $content);
        }
        // only sysdamin can do that
        if ($this->requester->userData['is_sysadmin'] === 0) {
            throw new IllegalActionException('Only a sysadmin can modify is_owner value.');
        }
        $sql = 'UPDATE users2teams SET ' . $what->value . ' = :content WHERE `users_id` = :userid AND `teams_id` = :team';
        $req = $this->Db->prepare($sql);
        $req->bindValue(':content', $content, PDO::PARAM_INT);
        $req->bindValue(':userid', $userid, PDO::PARAM_INT);
        $req->bindValue(':team', $teamid, PDO::PARAM_INT);

        $this->Db->execute($req);
        return $content;
    }

    private function wasAdminAlready(int $userid): bool
    {
        $sql = 'SELECT COUNT(users_id)
                FROM users2teams
                WHERE `users_id` = :userid
                    AND `is_admin` = 1';
        $req = $this->Db->prepare($sql);
        $req->bindValue(':userid', $userid, PDO::PARAM_INT);
        $this->Db->execute($req);
        return $req->fetchColumn() > 0;
    }
}
