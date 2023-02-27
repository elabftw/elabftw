<?php declare(strict_types=1);
/**
 * @package   Elabftw\Elabftw
 * @author    Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2012 Nicolas CARPi
 * @license   https://www.gnu.org/licenses/agpl-3.0.html AGPL-3.0
 * @see       https://www.elabftw.net Official website
 */

namespace Elabftw\Elabftw;

use Elabftw\Exceptions\ImproperActionException;
use Elabftw\Services\UsersHelper;

/**
 * Response object sent by an Auth service
 */
class AuthResponse
{
    public int $userid;

    /** @var array<int, array<int, string>> don't use an array of Team but just the ids and name */
    public $selectableTeams = array();

    public int $selectedTeam;

    public bool $isInSeveralTeams = false;

    // when user needs to request access to a team
    public bool $initTeamRequired = false;

    // info (email/name) about user that needs to request a team
    public array $initTeamUserInfo = array();

    public bool $isAnonymous = false;

    public ?string $mfaSecret = null;

    public bool $isAdmin = false;

    public bool $isSysAdmin = false;

    public bool $hasVerifiedMfa = false;

    public function setTeams(): void
    {
        $UsersHelper = new UsersHelper($this->userid);
        $this->selectableTeams = $UsersHelper->getTeamsFromUserid();

        // if the user only has access to one team, use this one directly
        $teamCount = count($this->selectableTeams);
        if ($teamCount === 1) {
            $this->selectedTeam = (int) $this->selectableTeams[0]['id'];
        } elseif ($teamCount === 0) {
            throw new ImproperActionException('Could not find a team!');
        } else {
            $this->isInSeveralTeams = true;
        }
    }
}
