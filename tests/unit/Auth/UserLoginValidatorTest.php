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

use Elabftw\Elabftw\Authentication;
use Elabftw\Elabftw\Db;
use Elabftw\Enums\AuthMethod;
use Elabftw\Exceptions\ImproperActionException;
use Elabftw\Exceptions\UnauthorizedException;
use PHPUnit\Framework\TestCase;

final class UserLoginValidatorTest extends TestCase
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

    public function testValidateReturnsLoginContext(): void
    {
        $context = new UserLoginValidator()->validate(
            new Authentication(1, AuthMethod::Local),
            1,
        );

        self::assertSame(1, $context->getUserid());
        self::assertSame(1, $context->getTeam());
        self::assertSame(AuthMethod::Local, $context->getAuthMethod());
    }

    public function testValidateRejectsMissingMembership(): void
    {
        $this->expectException(UnauthorizedException::class);
        new UserLoginValidator()->validate(
            new Authentication(1, AuthMethod::Local),
            2147483647,
        );
    }

    public function testValidateRejectsArchivedMembership(): void
    {
        $req = $this->Db->prepare(
            'UPDATE users2teams SET is_archived = 1
                WHERE users_id = 1 AND teams_id = 1',
        );
        $this->Db->execute($req);

        $this->expectException(ImproperActionException::class);
        $this->expectExceptionMessage('archived');
        new UserLoginValidator()->validate(
            new Authentication(1, AuthMethod::Local),
            1,
        );
    }

    public function testValidateRejectsExpiredAccount(): void
    {
        $req = $this->Db->prepare(
            'UPDATE users SET valid_until = :valid_until WHERE userid = 1',
        );
        $req->bindValue(':valid_until', '2000-01-01');
        $this->Db->execute($req);

        $this->expectException(ImproperActionException::class);
        $this->expectExceptionMessage('expired');
        new UserLoginValidator()->validate(
            new Authentication(1, AuthMethod::Local),
            1,
        );
    }

    public function testValidateRejectsUnvalidatedAccount(): void
    {
        $req = $this->Db->prepare(
            'UPDATE users SET validated = 0 WHERE userid = 1',
        );
        $this->Db->execute($req);

        $this->expectException(ImproperActionException::class);
        $this->expectExceptionMessage('not validated');
        new UserLoginValidator()->validate(
            new Authentication(1, AuthMethod::Local),
            1,
        );
    }
}
