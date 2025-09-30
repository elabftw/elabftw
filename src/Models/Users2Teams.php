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
use Elabftw\Enums\BinaryValue;
use Elabftw\Enums\Users2TeamsTargets;
use Elabftw\Exceptions\IllegalActionException;
use Elabftw\Exceptions\ImproperActionException;
use Elabftw\Models\Notifications\OnboardingEmail;
use Elabftw\Models\Users\Users;
use Elabftw\Services\UsersHelper;
use Elabftw\Services\TeamsHelper;
use Elabftw\Services\UserArchiver;
use PDO;

/**
 * Manage the link between users and teams
 */
final class Users2Teams
{
    protected Db $Db;

    public function __construct(private Users $requester)
    {
        $this->Db = Db::getConnection();
    }

    /**
     * Add one user to one team
     */
    public function create(int $userid, int $teamid, BinaryValue $isAdmin = BinaryValue::False, bool $isValidated = false): bool
    {
        // primary key will take care of ensuring there are no duplicate tuples
        $sql = 'INSERT IGNORE INTO users2teams (`users_id`, `teams_id`, `is_admin`) VALUES (:userid, :team, :is_admin);';
        $req = $this->Db->prepare($sql);
        $req->bindValue(':userid', $userid, PDO::PARAM_INT);
        $req->bindValue(':team', $teamid, PDO::PARAM_INT);
        $req->bindValue(':is_admin', $isAdmin->value, PDO::PARAM_INT);
        $res = $this->Db->execute($req);

        AuditLogs::create(new TeamAddition($teamid, $isAdmin->value, $this->requester->userid ?? 0, $userid));
        if ($isValidated) {
            new Teams($this->requester, $teamid)->sendOnboardingEmailToUser($userid, $isAdmin);
        }

        return $res;
    }

    public function patchUser2Team(array $params, int $targetUserid): int
    {
        return $this->patchIsSomething(
            Users2TeamsTargets::from($params['target']),
            $targetUserid,
            (int) $params['team'],
            BinaryValue::from((int) $params['content']),
        );
    }

    /**
     * Add one user to n teams
     *
     * @param array<array-key, int> $teamIdArr this is the validated array of teams that exist
     */
    public function addUserToTeams(int $userid, array $teamIdArr, BinaryValue $isAdmin = BinaryValue::False, bool $isValidated = false): void
    {
        foreach ($teamIdArr as $teamId) {
            $this->create($userid, $teamId, $isAdmin, $isValidated);
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
        $this->requesterCanModifyInTeamOrExplode($teamid);
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

    private function requesterCanModifyInTeamOrExplode(int $teamid): void
    {
        $TeamsHelper = new TeamsHelper($teamid);
        if (!(
            $this->requester->userData['is_sysadmin']
            || $this->requester->userData['can_manage_users2teams']
            || $TeamsHelper->isAdminInTeam($this->requester->userData['userid'])
        )) {
            throw new IllegalActionException('User tried to modify a team where they are not admin');
        }
    }

    private function patchIsAdmin(int $userid, int $teamid, BinaryValue $isAdmin): int
    {
        $this->requesterCanModifyInTeamOrExplode($teamid);
        $promoteToAdmin = $isAdmin->toBoolean() && !$this->wasAdminAlready($userid);

        $sql = 'UPDATE users2teams SET is_admin = :is_admin WHERE `users_id` = :userid AND `teams_id` = :team';
        $req = $this->Db->prepare($sql);
        $req->bindValue(':is_admin', $isAdmin->value, PDO::PARAM_INT);
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
        AuditLogs::create(new PermissionLevelChanged($this->requester->userid, $userid, Users2TeamsTargets::IsAdmin, $isAdmin->value, $teamid));
        return $isAdmin->value;
    }

    private function patchIsArchived(int $userid, int $teamid, BinaryValue $content): int
    {
        $this->requesterCanModifyInTeamOrExplode($teamid);
        return new UserArchiver($this->requester, new Users($userid, $teamid))
            ->setArchived($content)->value;
    }

    private function patchIsSomething(Users2TeamsTargets $what, int $userid, int $teamid, BinaryValue $content): int
    {
        if ($what === Users2TeamsTargets::IsAdmin) {
            return $this->patchIsAdmin($userid, $teamid, $content);
        }
        if ($what === Users2TeamsTargets::IsArchived) {
            return $this->patchIsArchived($userid, $teamid, $content);
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
        return $content->value;
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
