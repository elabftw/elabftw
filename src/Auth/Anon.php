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
use Elabftw\Exceptions\IllegalActionException;
use Elabftw\Interfaces\AuthInterface;
use Override;

/**
 * Anonymous auth service
 */
final class Anon implements AuthInterface
{
    private AuthResponse $AuthResponse;

    public function __construct(bool $isAnonAllowed, int $team)
    {
        if (!$isAnonAllowed) {
            throw new IllegalActionException('Cannot login as anon because it is not allowed by sysadmin!');
        }
        $this->AuthResponse = new AuthResponse();
        $this->AuthResponse->userid = 0;
        $this->AuthResponse->isAnonymous = true;
        $this->AuthResponse->isValidated = true;
        $this->AuthResponse->selectedTeam = $team;
    }

    /**
     * Nothing to do here because anonymous user can't be authenticated!
     */
    #[Override]
    public function tryAuth(): AuthResponse
    {
        return $this->AuthResponse;
    }
}
