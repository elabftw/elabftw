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
use Elabftw\Enums\EnforceMfa;
use Elabftw\Models\Users\Users;
use PDO;
use PHPUnit\Framework\TestCase;

final class MfaPolicyTest extends TestCase
{
    private Db $Db;

    protected function setUp(): void
    {
        $this->Db = Db::getConnection();
        $this->Db->beginTransaction();
        $this->setMfaSecret(null);
    }

    protected function tearDown(): void
    {
        $this->Db->rollBack();
    }

    public function testExistingSecretAlwaysRequiresMfa(): void
    {
        $this->setMfaSecret('configured-secret');

        self::assertTrue(
            new MfaPolicy(EnforceMfa::Disabled)->isRequired(new Users(1)),
        );
    }

    public function testEveryoneRequiresMfa(): void
    {
        self::assertTrue(
            new MfaPolicy(EnforceMfa::Everyone)->isRequired(new Users(1)),
        );
    }

    public function testSysadminPolicyUsesSysadminFlag(): void
    {
        $this->setSysadmin(1);
        self::assertTrue(
            new MfaPolicy(EnforceMfa::SysAdmins)->isRequired(new Users(1)),
        );

        $this->setSysadmin(0);
        self::assertFalse(
            new MfaPolicy(EnforceMfa::SysAdmins)->isRequired(new Users(1)),
        );
    }

    public function testAdminPolicyUsesTeamAdministration(): void
    {
        $this->setTeamAdmin(1);
        self::assertTrue(
            new MfaPolicy(EnforceMfa::Admins)->isRequired(new Users(1)),
        );

        $this->setTeamAdmin(0);
        self::assertFalse(
            new MfaPolicy(EnforceMfa::Admins)->isRequired(new Users(1)),
        );
    }

    public function testDisabledPolicyDoesNotRequireMfa(): void
    {
        self::assertFalse(
            new MfaPolicy(EnforceMfa::Disabled)->isRequired(new Users(1)),
        );
    }

    private function setMfaSecret(?string $secret): void
    {
        $req = $this->Db->prepare(
            'UPDATE users SET mfa_secret = :secret WHERE userid = 1',
        );
        $req->bindValue(
            ':secret',
            $secret,
            $secret === null ? PDO::PARAM_NULL : PDO::PARAM_STR,
        );
        $this->Db->execute($req);
    }

    private function setSysadmin(int $isSysadmin): void
    {
        $req = $this->Db->prepare(
            'UPDATE users SET is_sysadmin = :is_sysadmin WHERE userid = 1',
        );
        $req->bindValue(':is_sysadmin', $isSysadmin, PDO::PARAM_INT);
        $this->Db->execute($req);
    }

    private function setTeamAdmin(int $isAdmin): void
    {
        $req = $this->Db->prepare(
            'UPDATE users2teams SET is_admin = :is_admin WHERE users_id = 1',
        );
        $req->bindValue(':is_admin', $isAdmin, PDO::PARAM_INT);
        $this->Db->execute($req);
    }
}
