<?php

/**
 * @package   Elabftw\Elabftw
 * @author    Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2012 Nicolas CARPi
 * @license   https://www.gnu.org/licenses/agpl-3.0.html AGPL-3.0
 * @see       https://www.elabftw.net Official website
 */

declare(strict_types=1);

namespace Elabftw\Elabftw;

use Elabftw\Models\Users;
use Elabftw\Services\UsersHelper;

/**
 * Response object sent by an Auth service
 */
final class AuthResponse
{
    public int $userid;

    public array $selectableTeams = array();

    public int $selectedTeam;

    public bool $isInSeveralTeams = false;

    // when user needs to request access to a team
    public bool $initTeamRequired = false;

    public bool $teamSelectionRequired = false;

    public bool $teamRequestSelectionRequired = false;

    public bool $isValidated = false;

    // info (email/name) about user that needs to request a team
    public array $initTeamUserInfo = array();

    public bool $isAnonymous = false;

    public ?string $mfaSecret = null;

    public bool $hasVerifiedMfa = false;

    public bool $mustRenewPassword = false;

    public function setTeams(UsersHelper $usersHelper): void
    {
        $this->selectableTeams = $usersHelper->getTeamsFromUserid();

        // if the user only has access to one team, use this one directly
        $teamCount = count($this->selectableTeams);
        if ($teamCount === 1) {
            $this->selectedTeam = $this->selectableTeams[0]['id'];
        } elseif ($teamCount === 0) {
            $Users = new Users($this->userid);
            $this->teamSelectionRequired = true;
            $this->teamRequestSelectionRequired = true;
            $this->initTeamUserInfo = array(
                'userid' => $Users->userData['userid'],
            );
        } else {
            $this->isInSeveralTeams = true;
        }
    }
}
