<?php
/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */
declare(strict_types=1);

namespace Elabftw\Services;

use Elabftw\Elabftw\AuthResponse;
use Elabftw\Interfaces\AuthInterface;
use Elabftw\Maps\Team;

/**
 * Team auth service: for when you are already auth but you had to select a team
 */
class TeamAuth implements AuthInterface
{
    /** @var AuthResponse $AuthResponse */
    private $AuthResponse;

    public function __construct(int $userid, int $team)
    {
        $this->AuthResponse = new AuthResponse();
        $this->AuthResponse->userid = $userid;
        $this->AuthResponse->selectedTeam = $team;
        $this->AuthResponse->isAuthenticated = true;
    }

    /**
     * Nothing to do here
     */
    public function tryAuth(): AuthResponse
    {
        return $this->AuthResponse;
    }
}
