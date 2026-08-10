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

use Elabftw\Elabftw\Db;
use Elabftw\Enums\AuthMethod;
use PHPUnit\Framework\TestCase;

final class CookieLoginTest extends TestCase
{
    private Db $Db;

    protected function setUp(): void
    {
        $this->Db = Db::getConnection();
        $this->Db->beginTransaction();
    }

    protected function tearDown(): void
    {
        $this->Db->rollBack();
    }

    public function testGetContextAuthenticatesCookieAndValidatesTeam(): void
    {
        $token = CookieToken::fromScratch();
        $token->saveToken(1);

        $context = new CookieLogin(
            new Cookie(60, $token),
            new UserLoginValidator(),
            1,
        )->getContext();

        self::assertSame(1, $context->getUserid());
        self::assertSame(1, $context->getTeam());
        self::assertSame(AuthMethod::Cookie, $context->getAuthMethod());
    }
}
