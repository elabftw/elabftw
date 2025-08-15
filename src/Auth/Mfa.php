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
use Elabftw\Models\Users\Users;
use Elabftw\Services\MfaHelper;
use Elabftw\Services\UsersHelper;
use Override;

/**
 * This class is responsible for verifying that the code sent corresponds to the secret from the user
 */
final class Mfa implements AuthInterface
{
    public function __construct(
        private readonly MfaHelper $MfaHelper,
        private readonly int $userid,
        private readonly string $code,
    ) {}

    #[Override]
    public function tryAuth(): AuthResponse
    {
        if (!$this->MfaHelper->verifyCode($this->code)) {
            throw new InvalidMfaCodeException($this->userid);
        }
        $Users = new Users($this->userid);
        $AuthResponse = new AuthResponse();

        $AuthResponse->hasVerifiedMfa = true;
        $AuthResponse->isValidated = (bool) $Users->userData['validated'];
        $AuthResponse->userid = $this->userid;
        $UsersHelper = new UsersHelper($AuthResponse->userid);
        return $AuthResponse->setTeams($UsersHelper);
    }
}
