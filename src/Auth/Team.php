<?php

/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

declare(strict_types=1);

namespace Elabftw\Auth;

use Elabftw\Elabftw\AuthResponse;
use Elabftw\Exceptions\UnauthorizedException;
use Elabftw\Interfaces\AuthInterface;
use Elabftw\Models\Users;
use Elabftw\Services\TeamsHelper;
use Override;

/**
 * Team auth service: for when you are already auth but you had to select a team
 */
final class Team implements AuthInterface
{
    private AuthResponse $AuthResponse;

    public function __construct(int $userid, int $team)
    {
        $Users = new Users($userid);
        $this->AuthResponse = new AuthResponse();
        $this->AuthResponse->userid = $userid;
        $this->AuthResponse->isValidated = (bool) $Users->userData['validated'];
        $this->AuthResponse->selectedTeam = $team;
    }

    #[Override]
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
