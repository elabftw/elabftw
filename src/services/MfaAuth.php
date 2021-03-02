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
    private MfaHelper $MfaHelper;

    // user-provided code
    private string $code;

    private AuthResponse $AuthResponse;

    public function __construct(MfaHelper $mfa, string $code)
    {
        $this->MfaHelper = $mfa;
        $this->code = $code;
        $this->AuthResponse = new AuthResponse('mfa');
    }

    public function tryAuth(): AuthResponse
    {
        $Users = new Users($this->MfaHelper->userid);

        if (!$this->MfaHelper->verifyCode($this->code)) {
            throw new InvalidCredentialsException('The code you entered is not valid!');
        }

        $this->AuthResponse->hasVerifiedMfa = true;
        $this->AuthResponse->mfaSecret = $Users->userData['mfa_secret'];
        $this->AuthResponse->userid = $this->MfaHelper->userid;
        $this->AuthResponse->setTeams();

        return $this->AuthResponse;
    }
}
