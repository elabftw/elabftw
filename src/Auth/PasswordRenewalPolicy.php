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

final readonly class PasswordRenewalPolicy
{
    public function __construct(
        private int $maxPasswordAgeDays,
    ) {}

    public function isRequired(
        DateTimeImmutable $passwordModifiedAt,
    ): bool {
        if ($this->maxPasswordAgeDays === 0) {
            return false;
        }

        $daysSinceModification = (new DateTimeImmutable())
            ->diff($passwordModifiedAt)
            ->days;

        return $daysSinceModification > $this->maxPasswordAgeDays;
    }
}
