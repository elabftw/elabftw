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
use Elabftw\Exceptions\IllegalActionException;
use Elabftw\Interfaces\AuthInterface;
use Elabftw\Models\Config;

/**
 * Anonymous auth service
 */
class AnonAuth implements AuthInterface
{
    /** @var AuthResponse $AuthResponse */
    private $AuthResponse;

    public function __construct(Config $config, int $team)
    {
        // TODO this won't work for elabid! or will it?
        // maybe this should not be here so we can have only the team as param
        if (!$config->configArr['anon_users']) {
            throw new IllegalActionException('Cannot login as anon because it is not allowed by sysadmin!');
        }
        $this->AuthResponse = new AuthResponse('anon');
        $this->AuthResponse->userid = 0;
        $this->AuthResponse->isAnonymous = true;
        $this->AuthResponse->selectedTeam = $team;
    }

    /**
     * Nothing to do here because anonymous user can't be authenticated!
     */
    public function tryAuth(): AuthResponse
    {
        return $this->AuthResponse;
    }
}
