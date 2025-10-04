<?php

/**
 * @package   Elabftw\Elabftw
 * @author    Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2025 Nicolas CARPi
 * @license   https://www.gnu.org/licenses/agpl-3.0.html AGPL-3.0
 * @see       https://www.elabftw.net Official website
 */

declare(strict_types=1);

namespace Elabftw\Auth;

use Elabftw\Enums\Language;
use Elabftw\Models\Users\AnonymousUser;
use Elabftw\Models\Users\Users;
use Override;

final class AnonAuthResponse extends AuthResponse
{
    public function __construct(int $team, private Language $lang)
    {
        $this->setSelectedTeam($team);
    }

    #[Override]
    public function isAnonymous(): bool
    {
        return true;
    }

    #[Override]
    public function getAuthUserid(): int
    {
        return 0;
    }

    #[Override]
    public function getUser(): Users
    {
        return new AnonymousUser($this->getSelectedTeam(), $this->lang);
    }
}
