<?php

/**
 * @package   Elabftw\Elabftw
 * @author    Nicolas CARPi / Deltablot
 * @copyright 2026 Nicolas CARPi
 * @license   https://www.gnu.org/licenses/agpl-3.0.html AGPL-3.0
 * @see       https://www.elabftw.net Official website
 */

declare(strict_types=1);

namespace Elabftw\Auth;

use DateTimeImmutable;
use Elabftw\Elabftw\Authentication;
use Elabftw\Enums\AuthMethod;
use Elabftw\Exceptions\UnauthorizedException;
use Elabftw\Interfaces\LoginStepInterface;
use Elabftw\Models\Users\Users;

final class LoginFlow
{
    public function __construct(
        private readonly MfaPolicy $mfaPolicy,
        private readonly PasswordRenewalPolicy $passwordRenewalPolicy,
        private readonly SelectableTeamsProvider $selectableTeamsProvider,
        private readonly UserLoginValidator $loginValidator,
    ) {}

    public function start(Authentication $authentication): LoginStepInterface
    {
        $user = new Users($authentication->userid);

        if ($this->mfaPolicy->isRequired($user)) {
            return new MfaRequired($authentication);
        }

        return $this->afterMfa($authentication);
    }

    public function afterMfa(Authentication $authentication): LoginStepInterface
    {
        if (
            $authentication->method === AuthMethod::Local
            && $this->passwordRenewalPolicy->isRequired(
                $this->getPasswordModifiedAt($authentication->userid),
            )
        ) {
            return new PasswordRenewalRequired($authentication);
        }

        return $this->afterPasswordRenewal($authentication);
    }

    public function afterPasswordRenewal(
        Authentication $authentication,
    ): LoginStepInterface {
        $teams = $this->selectableTeamsProvider->getForUser(
            $authentication->userid,
        );

        return match ($teams->count()) {
            0 => new TeamRequestRequired($authentication),
            1 => $this->finalize(
                $authentication,
                $teams->first()->id,
            ),
            default => new TeamSelectionRequired(
                $authentication,
                $teams,
            ),
        };
    }

    public function selectTeam(
        Authentication $authentication,
        int $teamId,
    ): UserLoginContext {
        $teams = $this->selectableTeamsProvider->getForUser(
            $authentication->userid,
        );

        if (!$teams->contains($teamId)) {
            throw new UnauthorizedException();
        }

        return $this->finalize(
            $authentication,
            $teamId,
        );
    }

    private function getPasswordModifiedAt(int $userid): DateTimeImmutable
    {
        $user = new Users($userid);

        return new DateTimeImmutable(
            $user->userData['password_modified_at'],
        );
    }

    private function finalize(
        Authentication $authentication,
        int $teamId,
    ): UserLoginContext {
        return $this->loginValidator->validate(
            $authentication,
            $teamId,
        );
    }
}
