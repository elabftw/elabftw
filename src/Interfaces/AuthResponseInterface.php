<?php

/**
 * @package   Elabftw\Elabftw
 * @author    Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2025 Nicolas CARPi
 * @license   https://www.gnu.org/licenses/agpl-3.0.html AGPL-3.0
 * @see       https://www.elabftw.net Official website
 */
declare(strict_types=1);

namespace Elabftw\Interfaces;

use Elabftw\Models\Users\Users;
use Elabftw\Services\UsersHelper;

interface AuthResponseInterface
{
    public function isAnonymous(): bool;

    public function hasVerifiedMfa(): bool;

    public function setTeams(UsersHelper $helper): self;

    public function setAuthenticatedUserid(int $userid): self;

    public function getSelectableTeams(): array;

    public function isInSeveralTeams(): bool;

    public function initTeamRequired(): bool;

    public function setInitTeamRequired(bool $required): self;

    public function setInitTeamInfo(array $info): void;

    public function getInitTeamInfo(): array;

    public function teamRequestSelectionRequired(): bool;

    public function mustRenewPassword(): bool;

    public function getAuthUserid(): int;

    public function getSelectedTeam(): int;

    public function setSelectedTeam(int $team): self;

    public function getUser(): Users;
}
