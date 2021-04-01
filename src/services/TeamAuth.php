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
use Elabftw\Exceptions\UnauthorizedException;
use Elabftw\Interfaces\AuthInterface;

/**
 * Team auth service: for when you are already auth but you had to select a team
 */
class TeamAuth implements AuthInterface
{
    private AuthResponse $AuthResponse;

    public function __construct(int $userid, int $team)
    {
        $this->AuthResponse = new AuthResponse('team');
        $this->AuthResponse->userid = $userid;
        $this->AuthResponse->selectedTeam = $team;
    }

    public function tryAuth(): AuthResponse
    {
        // we cannot trust the team sent by POST
        // so make sure the user is part of that team
        $TeamsHelper = new TeamsHelper($this->AuthResponse->selectedTeam);
        if (!$TeamsHelper->isUserInTeam($this->AuthResponse->userid)) {
            throw new UnauthorizedException();
        }

        return $this->AuthResponse;
    }
}
