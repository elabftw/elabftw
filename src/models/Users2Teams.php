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
use Elabftw\Exceptions\IllegalActionException;
use Elabftw\Exceptions\ImproperActionException;
use Elabftw\Models\Notifications\OnboardingEmail;
use Elabftw\Services\Check;
use Elabftw\Services\UsersHelper;
use Elabftw\Services\TeamsHelper;
use PDO;

/**
 * Manage the link between users and teams
 */
class Users2Teams
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
        // primary key will take care of ensuring there are no duplicate tuples
        $sql = 'INSERT IGNORE INTO users2teams (`users_id`, `teams_id`, `groups_id`) VALUES (:userid, :team, :group);';
        $req = $this->Db->prepare($sql);
        $req->bindValue(':userid', $userid, PDO::PARAM_INT);
        $req->bindValue(':team', $teamid, PDO::PARAM_INT);
        $req->bindValue(':group', $group->value, PDO::PARAM_INT);
        $res = $this->Db->execute($req);
        AuditLogs::create(new TeamAddition($teamid, $group->value, $this->requester->userid ?? 0, $userid));

        // check onboarding email setting for each team
        $Team = new Teams(new Users(), $teamid);
        $Team->bypassReadPermission = true;
        if ($this->sendOnboardingEmailOfTeams && $Team->readOne()['onboarding_email_active'] === 1) {
            (new OnboardingEmail($teamid))->create($userid);
        }

        return $res;
    }

    public function patchUser2Team(array $params): int
    {
        $userid = (int) $params['userid'];
        $teamid = (int) $params['team'];
        if ($params['target'] === 'group') {
            return $this->patchTeamGroup(
                $userid,
                $teamid,
                Usergroup::from((int) $params['content']),
            );
        }

        // currently only other value for target is: is_owner
        return $this->patchIsOwner($userid, $teamid, (int) $params['content']);
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
    public function destroy(int $userid, int $teamid): bool
    {
        // make sure we are Admin in the team that we are removing the user from
        $TeamsHelper = new TeamsHelper($teamid);
        if (!$this->requester->userData['is_sysadmin'] || $TeamsHelper->isAdminInTeam($this->requester->userData['userid'])) {
            throw new ImproperActionException('Cannot remove user from team if not admin of said user in that team');
        }
        return $this->removeUserFromTeam($userid, $teamid);
    }

    private function removeUserFromTeam(int $userid, int $teamid): bool
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
        $res = $this->Db->execute($req);
        // also remove any api key for that user in that team
        $ApiKeys = new ApiKeys(new Users($userid, $teamid));
        $ApiKeys->destroyInTeam($teamid);

        AuditLogs::create(new TeamRemoval($teamid, 0, $userid));
        return $res;
    }

    private function patchTeamGroup(int $userid, int $teamid, Usergroup $group): int
    {
        $group = Check::usergroup($this->requester, $group);
        $promoteToAdmin = $group === Usergroup::Admin && !$this->wasAdminAlready($userid);
        // make sure requester is admin of target user
        if (!$this->requester->isAdminOf($userid) && $this->requester->userData['is_sysadmin'] !== 1) {
            throw new IllegalActionException('User tried to patch team group of another user but they are not admin');
        }

        $TeamsHelper = new TeamsHelper($teamid);
        if (!$TeamsHelper->isAdminInTeam($this->requester->userData['userid']) && $this->requester->userData['is_sysadmin'] !== 1) {
            throw new IllegalActionException('User tried to patch team group of a team where they are not admin');
        }
        $sql = 'UPDATE users2teams SET groups_id = :group WHERE `users_id` = :userid AND `teams_id` = :team';
        $req = $this->Db->prepare($sql);
        $req->bindValue(':group', $group->value, PDO::PARAM_INT);
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
        AuditLogs::create(new PermissionLevelChanged($this->requester->userid, $group->value, $userid, $teamid));
        return $group->value;
    }

    private function patchIsOwner(int $userid, int $teamid, int $content): int
    {
        // only sysdamin can do that
        if ($this->requester->userData['is_sysadmin'] === 0) {
            throw new IllegalActionException('Only a sysadmin can modify is_owner value.');
        }
        $sql = 'UPDATE users2teams SET is_owner = :content WHERE `users_id` = :userid AND `teams_id` = :team';
        $req = $this->Db->prepare($sql);
        $req->bindValue(':content', $content, PDO::PARAM_INT);
        $req->bindValue(':userid', $userid, PDO::PARAM_INT);
        $req->bindValue(':team', $teamid, PDO::PARAM_INT);

        $this->Db->execute($req);
        return $content;
    }

    private function wasAdminAlready(int $userid): bool
    {
        $sql = sprintf(
            'SELECT COUNT(users_id)
                FROM users2teams
                WHERE `users_id` = :userid
                    AND `groups_id` = %d',
            Usergroup::Admin->value,
        );
        $req = $this->Db->prepare($sql);
        $req->bindValue(':userid', $userid, PDO::PARAM_INT);
        $this->Db->execute($req);
        return $req->fetchColumn() > 0;
    }
}
