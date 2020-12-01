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

/**
 * Session auth service. When the session is already active
 */
class SessionAuth implements AuthInterface
{
    /** @var AuthResponse $AuthResponse */
    private $AuthResponse;

    public function __construct()
    {
        $this->AuthResponse = new AuthResponse('session');
    }

    /**
     * Nothing to do here because anonymous user can't be authenticated!
     */
    public function tryAuth(): AuthResponse
    {
        return $this->AuthResponse;
    }
}
