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

final readonly class TeamMembership
{
    public function __construct(
        public int $id,
        public string $name,
        public bool $isOwner,
        public bool $isAdmin,
    ) {}
}
