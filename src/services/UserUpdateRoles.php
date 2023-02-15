<?php declare(strict_types=1);
/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2022 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

namespace Elabftw\Services;

use function array_key_exists;
use Elabftw\Elabftw\Db;
use Elabftw\Exceptions\ImproperActionException;
use Elabftw\Models\Users;
use PDO;
use PDOStatement;
use function settype;

final class UserUpdateRoles
{
    protected Db $Db;

    private bool $isAdmin = false;

    private bool $isSysadmin = false;

    private bool $targetIsSysadmin = false;

    private ?bool $inputIsAdmin = null;

    private ?array $inputAdminOfTeams = null;

    private ?bool $inputIsSysadmin = null;

    public function __construct(private Users $User, private Users $Requester, array $params)
    {
        $this->Db = Db::getConnection();

        $this->isSysadmin = (bool) $this->Requester->userData['is_sysadmin'];
        $this->isAdmin = (bool) $this->Requester->userData['is_admin'];
        $this->targetIsSysadmin = (bool) $this->User->userData['is_sysadmin'];

        $this->inputIsAdmin = $this->paramExists($params, 'is_admin', 'bool');
        $this->inputAdminOfTeams = $this->paramExists($params, 'admin_of_teams', 'array');
        $this->inputIsSysadmin = $this->paramExists($params, 'is_sysadmin', 'bool');
    }

    // private function checkUserGroup(int $usergroup): int
    // {
    //     $usergroup = Check::usergroup($usergroup);
    //     // a sysadmin can do what they want, no need to check further
    //     if ($this->isSysadmin === 1) {
    //         return $usergroup;
    //     }
    //     // prevent an admin from promoting a user to sysadmin
    //     if ($this->isAdmin === 1 && $usergroup === 1) {
    //         throw new ImproperActionException('Only a sysadmin can promote another user to sysadmin.');
    //     }
    //     // a non sysadmin cannot demote a sysadmin
    //     if ($usergroup !== 1 && $this->targetIsSysadmin) {
    //         throw new ImproperActionException('Only a sysadmin can demote another sysadmin.');
    //     }
    //     // if requester is not admin the only valid usergroup is 4 (user)
    //     if ($this->isAdmin !== 1) {
    //         return 4;
    //     }
    //     return $usergroup;
    // }

    public function update(): void
    {
        if (isset($this->inputIsSysadmin)) {
            $this->updateSysadminStatus();
        }

        if (isset($this->inputAdminOfTeams)) {
            $this->updateAdminStatusSeveralTeams();
        }

        if (isset($this->inputIsAdmin)) {
            $this->canUpdateOneOrExplode();
            $this->updateOneTeam($this->inputIsAdmin);
        }
    }

    private function updateSysadminStatus(): void
    {
        if (!$this->isSysadmin) {
            // a non sysadmin cannot promote a user to sysadmin
            if ($this->inputIsSysadmin === true) {
                throw new ImproperActionException('Only a sysadmin can promote another user to sysadmin.');
            }

            // a non sysadmin cannot demote a sysadmin
            if ($this->inputIsSysadmin === false) {
                throw new ImproperActionException('Only a sysadmin can demote another sysadmin.');
            }
        }

        // sysadmins can pro-/demote other sysadmins
        if ($this->inputIsSysadmin !== $this->targetIsSysadmin) {
            $sql = 'UPDATE users SET is_sysadmin = :is_sysadmin WHERE userid = :userid';
            $req = $this->Db->prepare($sql);
            $req->bindValue(':is_sysadmin', $this->inputIsSysadmin, PDO::PARAM_INT);
            $req->bindValue(':userid', $this->User->userid, PDO::PARAM_INT);
            $this->Db->execute($req);
        }
    }

    private function updateAdminStatusSeveralTeams(): void
    {
        if (!$this->isSysadmin) {
            throw new ImproperActionException('Only a sysadmin can change the admin status of a user in several teams at once.');
        }

        $teamsOfUser = array_column($this->User->userData['teams'], 'id');
        $currentlyAdminOfTeams = array_keys(array_column($this->User->userData['teams'], 'is_admin', 'id'), 1, true);
        // check that input is list of teams which user is actually member of
        /** @psalm-suppress PossiblyNullArgument */
        $inputValidTeams = array_intersect($this->inputAdminOfTeams, $teamsOfUser);

        $req = $this->getUpdateReq();

        $addToTeams = array_diff($inputValidTeams, $currentlyAdminOfTeams);
        foreach ($addToTeams as $team) {
            $req->bindValue(':is_admin', 1);
            $req->bindValue(':team', $team, PDO::PARAM_INT);
            $this->Db->execute($req);
        }

        $removeFromTeams = array_diff($currentlyAdminOfTeams, $inputValidTeams);
        foreach ($removeFromTeams as $team) {
            $req->bindValue(':is_admin', 0);
            $req->bindValue(':team', $team, PDO::PARAM_INT);
            $this->Db->execute($req);
        }
    }

    private function updateOneTeam(bool $value): void
    {
        $req = $this->getUpdateReq();
        $req->bindValue(':is_admin', $value, PDO::PARAM_INT);
        $req->bindValue(':team', $this->User->team, PDO::PARAM_INT);
        $this->Db->execute($req);
    }

    /**
     * Is user role related value provided in params array
     */
    private function paramExists(array $params, string $key, string $type): mixed
    {
        $value = null;
        if (array_key_exists($key, $params)) {
            $value = $params[$key];
            if (!settype($value, $type)) {
                throw new ImproperActionException(
                    sprintf('Wrong value provided for parameter %s. Expect value of type %s', $key, $type)
                );
            }
        }
        return $value;
    }

    private function getUpdateReq(): PDOStatement
    {
        $sql = 'UPDATE users2teams SET is_admin = :is_admin
            WHERE users_id = :users_id AND teams_id = :team';
        $req = $this->Db->prepare($sql);
        $req->bindValue(':users_id', $this->User->userid, PDO::PARAM_INT);

        return $req;
    }

    /**
     * target and requester (admin) need to be in same team if not sysadmin
     */
    private function canUpdateOneOrExplode(): void
    {
        if (!$this->isSysadmin && !($this->isAdmin && $this->User->team === $this->Requester->team)) {
            throw new ImproperActionException('You need to be an admin in the same team or a sysadmin to change a users admin status.');
        }
    }
}
