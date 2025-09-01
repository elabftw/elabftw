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

use Elabftw\Exceptions\UnauthorizedException;
use Elabftw\Interfaces\AuthInterface;
use Elabftw\Interfaces\AuthResponseInterface;
use Elabftw\Services\TeamsHelper;
use Override;

/**
 * Team auth service: for when you are already auth but you had to select a team
 */
final class Team implements AuthInterface
{
    public function __construct(private int $userid, private int $team) {}

    #[Override]
    public function tryAuth(): AuthResponseInterface
    {
        // we cannot trust the team sent by POST
        // so make sure the user is part of that team
        $TeamsHelper = new TeamsHelper($this->team);
        if (!$TeamsHelper->isUserInTeam($this->userid)) {
            throw new UnauthorizedException();
        }
        return new AuthResponse()
            ->setAuthenticatedUserid($this->userid)
            ->setSelectedTeam($this->team);
    }
}
