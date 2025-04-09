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
use Elabftw\Exceptions\InvalidMfaCodeException;
use Elabftw\Interfaces\AuthInterface;
use Elabftw\Models\Users;
use Elabftw\Services\MfaHelper;
use Elabftw\Services\UsersHelper;
use Override;

/**
 * Multi Factor Auth service
 */
final class Mfa implements AuthInterface
{
    private AuthResponse $AuthResponse;

    public function __construct(private MfaHelper $MfaHelper, private string $code)
    {
        $this->AuthResponse = new AuthResponse();
    }

    #[Override]
    public function tryAuth(): AuthResponse
    {
        $Users = new Users($this->MfaHelper->userid);

        if (!$this->MfaHelper->verifyCode($this->code)) {
            throw new InvalidMfaCodeException($this->MfaHelper->userid);
        }

        $this->AuthResponse->hasVerifiedMfa = true;
        $this->AuthResponse->mfaSecret = $Users->userData['mfa_secret'];
        $this->AuthResponse->isValidated = (bool) $Users->userData['validated'];
        $this->AuthResponse->userid = $this->MfaHelper->userid;
        $UsersHelper = new UsersHelper($this->AuthResponse->userid);
        $this->AuthResponse->setTeams($UsersHelper);

        return $this->AuthResponse;
    }
}
