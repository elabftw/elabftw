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
    /** @var int $userid */
    public $userid;

    /** @var array<int, array<int, string>> don't use an array of Team but just the ids and name */
    public $selectableTeams = array();

    /** @var int $selectedTeam */
    public $selectedTeam;

    /** @var bool $isAnonymous */
    public $isAnonymous = false;

    /** @var string $mfaSecret */
    public $mfaSecret = '';

    /** @var bool $hasVerifiedMfa */
    public $hasVerifiedMfa = false;

    /** @var string $isAuthBy */
    public $isAuthBy;

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
        }
    }
}
