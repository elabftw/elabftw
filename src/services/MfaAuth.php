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
use Elabftw\Exceptions\InvalidCredentialsException;
use Elabftw\Interfaces\AuthInterface;
use Elabftw\Models\Users;

/**
 * Multi Factor Auth service
 */
class MfaAuth implements AuthInterface
{
    private AuthResponse $AuthResponse;

    public function __construct(private MfaHelper $MfaHelper, private string $code)
    {
        $this->AuthResponse = new AuthResponse('mfa');
    }

    public function tryAuth(): AuthResponse
    {
        $Users = new Users($this->MfaHelper->userid);

        if (!$this->MfaHelper->verifyCode($this->code)) {
            throw new InvalidCredentialsException($this->MfaHelper->userid);
        }

        $this->AuthResponse->hasVerifiedMfa = true;
        $this->AuthResponse->mfaSecret = $Users->userData['mfa_secret'];
        $this->AuthResponse->userid = $this->MfaHelper->userid;
        $this->AuthResponse->setTeams();

        return $this->AuthResponse;
    }
}
