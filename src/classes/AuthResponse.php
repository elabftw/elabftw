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

    public bool $isAnonymous = false;

    public ?string $mfaSecret;

    public bool $hasVerifiedMfa = false;

    public string $isAuthBy;

    public function __construct(string $authBy)
    {
        $this->isAuthBy = $authBy;
    }

    public function setTeams(): void
    {
        $UsersHelper = new UsersHelper($this->userid);
        $this->selectableTeams = $UsersHelper->getTeamsFromUserid();

        // if the user only has access to one team, use this one directly
        if (count($this->selectableTeams) === 1) {
            $this->selectedTeam = (int) $this->selectableTeams[0]['id'];
        } else {
            $this->isInSeveralTeams = true;
        }
    }
}
