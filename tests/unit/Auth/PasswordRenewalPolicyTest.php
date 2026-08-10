<?php

declare(strict_types=1);

/**
 * @author Nicolas CARPi / Deltablot
 * @copyright 2026 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

namespace Elabftw\Auth;

use DateTimeImmutable;
use PHPUnit\Framework\TestCase;

final class PasswordRenewalPolicyTest extends TestCase
{
    public function testZeroMaximumAgeDisablesRenewal(): void
    {
        self::assertFalse(
            new PasswordRenewalPolicy(0)->isRequired(
                new DateTimeImmutable('-10 years'),
            ),
        );
    }

    public function testOldPasswordRequiresRenewal(): void
    {
        self::assertTrue(
            new PasswordRenewalPolicy(1)->isRequired(
                new DateTimeImmutable('-2 days'),
            ),
        );
    }

    public function testRecentPasswordDoesNotRequireRenewal(): void
    {
        self::assertFalse(
            new PasswordRenewalPolicy(30)->isRequired(
                new DateTimeImmutable(),
            ),
        );
    }
}
