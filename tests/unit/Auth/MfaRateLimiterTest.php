<?php

declare(strict_types=1);

/**
 * @author Nicolas CARPi @ Deltablot
 * @copyright 2026 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

namespace Elabftw\Auth;

final class MfaRateLimiterTest extends \PHPUnit\Framework\TestCase
{
    private const int USERID = 1;

    private MfaRateLimiter $rateLimiter;

    protected function setUp(): void
    {
        $this->rateLimiter = new MfaRateLimiter();
        $this->rateLimiter->clear(self::USERID);
    }

    protected function tearDown(): void
    {
        $this->rateLimiter->clear(self::USERID);
    }

    public function testUserIsBlockedAfterFiveFailures(): void
    {
        for ($i = 0; $i < 4; $i++) {
            self::assertFalse(
                $this->rateLimiter->registerFailure(
                    self::USERID,
                ),
            );
        }

        self::assertTrue(
            $this->rateLimiter->registerFailure(
                self::USERID,
            ),
        );

        self::assertTrue(
            $this->rateLimiter->isBlocked(
                self::USERID,
            ),
        );
    }

    public function testClearRemovesRateLimit(): void
    {
        for ($i = 0; $i < 5; $i++) {
            $this->rateLimiter->registerFailure(
                self::USERID,
            );
        }

        self::assertTrue(
            $this->rateLimiter->isBlocked(self::USERID),
        );

        $this->rateLimiter->clear(self::USERID);

        self::assertFalse(
            $this->rateLimiter->isBlocked(self::USERID),
        );
    }
}
