<?php

/**
 * @package   Elabftw\Elabftw
 * @author    Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2025 Nicolas CARPi
 * @license   https://www.gnu.org/licenses/agpl-3.0.html AGPL-3.0
 * @see       https://www.elabftw.net Official website
 */

declare(strict_types=1);

namespace Elabftw\Auth;

use Elabftw\Elabftw\Db;
use Elabftw\Exceptions\ImproperActionException;
use Elabftw\Interfaces\AuthResponseInterface;
use Elabftw\Models\Users\Users;
use Elabftw\Services\UsersHelper;
use Override;
use PDO;

use function count;

/**
 * Response object sent by an Auth service
 */
class AuthResponse implements AuthResponseInterface
{
    public bool $isInSeveralTeams = false;

    // when a new local user must pick an initial team (no existing local account)
    public bool $initTeamRequired = false;

    // when an existing user has no team and must request one
    public bool $teamSelectionRequired = false;

    public bool $teamRequestSelectionRequired = false;

    // info (email/name) about user that needs to request a team
    public array $initTeamUserInfo = array();

    protected array $selectableTeams = array();

    protected int $selectedTeam = 0;

    protected int $userid = 0;

    #[Override]
    public function setInitTeamRequired(bool $required): self
    {
        $this->initTeamRequired = $required;
        return $this;
    }

    #[Override]
    public function isAnonymous(): bool
    {
        return false;
    }

    #[Override]
    public function hasVerifiedMfa(): bool
    {
        return false;
    }

    #[Override]
    public function getAuthUserid(): int
    {
        return $this->userid;
    }

    #[Override]
    public function getSelectedTeam(): int
    {
        return $this->selectedTeam;
    }

    #[Override]
    public function getUser(): Users
    {
        return $this->selectedTeam > 0 ? new Users($this->userid, $this->selectedTeam) : new Users($this->userid);
    }

    #[Override]
    public function getSelectableTeams(): array
    {
        return $this->selectableTeams;
    }

    #[Override]
    public function isInSeveralTeams(): bool
    {
        return $this->isInSeveralTeams;
    }

    #[Override]
    public function setInitTeamInfo(array $info): void
    {
        $this->initTeamUserInfo = $info;
    }

    #[Override]
    public function teamRequestSelectionRequired(): bool
    {
        return $this->teamRequestSelectionRequired;
    }

    #[Override]
    public function getInitTeamInfo(): array
    {
        return $this->initTeamUserInfo;
    }

    #[Override]
    public function initTeamRequired(): bool
    {
        return $this->initTeamRequired;
    }

    #[Override]
    public function mustRenewPassword(): bool
    {
        return false;
    }

    #[Override]
    public function setSelectedTeam(int $team): self
    {
        $Db = Db::getConnection();
        // check if user belongs to the team and is not archived
        $sql = 'SELECT is_admin, is_archived FROM users2teams WHERE users_id = :userid AND teams_id = :team';
        $req = $Db->prepare($sql);
        $req->bindValue(':userid', $this->getAuthUserid(), PDO::PARAM_INT);
        $req->bindValue(':team', $this->getSelectedTeam(), PDO::PARAM_INT);
        $res = $Db->fetch($req);
        if (($req->rowCount() !== 1 || $res['is_archived'] === 1) && !$this->isAnonymous()) {
            throw new ImproperActionException(_('This account is archived in this team and cannot login.'));
        }
        $this->selectedTeam = $team;
        return $this;
    }

    #[Override]
    public function setAuthenticatedUserid(int $userid): self
    {
        $this->userid = $userid;
        return $this;
    }

    #[Override]
    public function setTeams(UsersHelper $helper): self
    {
        $this->selectableTeams = $helper->getTeamsFromUserid();

        // if the user only has access to one team, use this one directly
        $teamCount = count($this->selectableTeams);
        if ($teamCount === 1) {
            $this->selectedTeam = $this->selectableTeams[0]['id'];
        } elseif ($teamCount === 0) {
            $this->teamSelectionRequired = true;
            $this->teamRequestSelectionRequired = true;
            $this->initTeamUserInfo = array(
                'userid' => $this->userid,
            );
        } else {
            $this->isInSeveralTeams = true;
        }
        return $this;
    }
}
