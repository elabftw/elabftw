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

use Elabftw\Exceptions\InvalidMfaCodeException;
use Elabftw\Interfaces\AuthInterface;
use Elabftw\Interfaces\AuthResponseInterface;
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
    public function tryAuth(): AuthResponseInterface
    {
        if (!$this->MfaHelper->verifyCode($this->code)) {
            throw new InvalidMfaCodeException();
        }
        return new MfaAuthResponse()
            ->setAuthenticatedUserid($this->userid)
            ->setTeams(new UsersHelper($this->userid));
    }
}
