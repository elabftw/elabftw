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
use Elabftw\Exceptions\ForbiddenException;
use Elabftw\Exceptions\ImproperActionException;
use Elabftw\Models\Users\AuthenticatedUser;
use Elabftw\Traits\TestsUtilsTrait;
use PDO;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;

final class ImpersonationTest extends TestCase
{
    use TestsUtilsTrait;

    private const int SYSADMIN_TEAM = 1;

    private const int ADMIN_TEAM = 2;

    public function testSysadminCanImpersonateAndStop(): void
    {
        $sysadmin = new AuthenticatedUser(1, self::SYSADMIN_TEAM);
        $targetId = $this->getUserIdFromEmail('titi@yopmail.com');
        $Session = $this->getSession($sysadmin);
        $sessionId = $Session->getId();
        $Impersonation = new Impersonation($Session, false);

        $Impersonation->start($sysadmin, $targetId);

        self::assertTrue($Impersonation->isActive());
        self::assertSame(1, $Impersonation->getImpersonatorId());
        self::assertSame($targetId, $Session->get('userid'));
        self::assertSame(self::SYSADMIN_TEAM, $Session->get('team'));
        self::assertNotSame($sessionId, $Session->getId());
        // still valid on next requests
        $Impersonation->validate();

        $Impersonation->stop();

        self::assertFalse($Impersonation->isActive());
        self::assertNull($Impersonation->getImpersonatorId());
        self::assertSame(1, $Session->get('userid'));
        self::assertSame(self::SYSADMIN_TEAM, $Session->get('team'));
    }

    public function testSysadminGoesToTargetTeam(): void
    {
        $sysadmin = new AuthenticatedUser(1, self::SYSADMIN_TEAM);
        $Session = $this->getSession($sysadmin);
        new Impersonation($Session, false)->start($sysadmin, $this->getUserIdFromEmail('user1@demo.elabftw.net'));
        self::assertSame(self::ADMIN_TEAM, $Session->get('team'));
    }

    public function testCannotImpersonateSelf(): void
    {
        $sysadmin = new AuthenticatedUser(1, self::SYSADMIN_TEAM);
        $this->expectException(ImproperActionException::class);
        new Impersonation($this->getSession($sysadmin), false)->start($sysadmin, 1);
    }

    public function testCannotNestImpersonations(): void
    {
        $sysadmin = new AuthenticatedUser(1, self::SYSADMIN_TEAM);
        $Session = $this->getSession($sysadmin);
        $Impersonation = new Impersonation($Session, false);
        $Impersonation->start($sysadmin, $this->getUserIdFromEmail('titi@yopmail.com'));
        $this->expectException(ImproperActionException::class);
        $Impersonation->start($sysadmin, $this->getUserIdFromEmail('tutu@yopmail.com'));
    }

    public function testCannotStartFromAnotherSessionUser(): void
    {
        $sysadmin = new AuthenticatedUser(1, self::SYSADMIN_TEAM);
        $Session = $this->getSession($this->getAdmin());
        $this->expectException(ForbiddenException::class);
        new Impersonation($Session, false)->start($sysadmin, $this->getUserIdFromEmail('titi@yopmail.com'));
    }

    public function testCannotStopWithoutImpersonation(): void
    {
        $this->expectException(ImproperActionException::class);
        new Impersonation($this->getSession(new AuthenticatedUser(1, self::SYSADMIN_TEAM)), false)->stop();
    }

    public function testAdminCannotImpersonateWhenDisabled(): void
    {
        $admin = $this->getAdmin();
        $this->expectException(ForbiddenException::class);
        new Impersonation($this->getSession($admin), false)->start($admin, $this->getUserIdFromEmail('user1@demo.elabftw.net'));
    }

    public function testAdminCanImpersonateUserOfTheirTeam(): void
    {
        $admin = $this->getAdmin();
        $targetId = $this->getUserIdFromEmail('user1@demo.elabftw.net');
        $Session = $this->getSession($admin);
        $Impersonation = new Impersonation($Session, true);

        $Impersonation->start($admin, $targetId);

        self::assertSame($targetId, $Session->get('userid'));
        self::assertSame(self::ADMIN_TEAM, $Session->get('team'));
        $Impersonation->validate();

        // the instance setting is turned off while impersonating
        $this->expectException(ForbiddenException::class);
        new Impersonation($Session, false)->validate();
    }

    public function testAdminCannotImpersonateUserOfAnotherTeam(): void
    {
        $admin = $this->getAdmin();
        $this->expectException(ImproperActionException::class);
        new Impersonation($this->getSession($admin), true)->start($admin, $this->getUserIdFromEmail('titi@yopmail.com'));
    }

    public function testAdminCannotImpersonateSysadmin(): void
    {
        $admin = $this->getAdmin();
        // add the sysadmin in the admin's team so only the privilege check stands in the way
        $Db = Db::getConnection();
        $req = $Db->prepare('INSERT INTO users2teams (users_id, teams_id, is_admin) VALUES (1, :team, 0)');
        $req->bindValue(':team', self::ADMIN_TEAM, PDO::PARAM_INT);
        $Db->execute($req);
        try {
            $this->expectException(ForbiddenException::class);
            new Impersonation($this->getSession($admin), true)->start($admin, 1);
        } finally {
            $req = $Db->prepare('DELETE FROM users2teams WHERE users_id = 1 AND teams_id = :team');
            $req->bindValue(':team', self::ADMIN_TEAM, PDO::PARAM_INT);
            $Db->execute($req);
        }
    }

    public function testAdminCannotImpersonatePrivilegedUser(): void
    {
        $admin = $this->getAdmin();
        $targetId = $this->getUserIdFromEmail('user1@demo.elabftw.net');
        $Db = Db::getConnection();
        $req = $Db->prepare('UPDATE users SET can_manage_users2teams = :value WHERE userid = :userid');
        $req->bindValue(':userid', $targetId, PDO::PARAM_INT);
        $req->bindValue(':value', 1, PDO::PARAM_INT);
        $Db->execute($req);
        try {
            $this->expectException(ForbiddenException::class);
            new Impersonation($this->getSession($admin), true)->start($admin, $targetId);
        } finally {
            $req->bindValue(':value', 0, PDO::PARAM_INT);
            $Db->execute($req);
        }
    }

    public function testUserCannotImpersonate(): void
    {
        $user = new AuthenticatedUser($this->getUserIdFromEmail('user1@demo.elabftw.net'), self::ADMIN_TEAM);
        $this->expectException(ForbiddenException::class);
        new Impersonation($this->getSession($user), true)->start($user, $this->getUserIdFromEmail('admin1@demo.elabftw.net'));
    }

    public function testCannotImpersonateUnvalidatedUser(): void
    {
        $sysadmin = new AuthenticatedUser(1, self::SYSADMIN_TEAM);
        $Db = Db::getConnection();
        $req = $Db->prepare("SELECT userid FROM users WHERE email = 'notyetvalidateduser@yopmail.com'");
        $Db->execute($req);
        $this->expectException(ImproperActionException::class);
        new Impersonation($this->getSession($sysadmin), false)->start($sysadmin, (int) $req->fetchColumn());
    }

    private function getAdmin(): AuthenticatedUser
    {
        return new AuthenticatedUser($this->getUserIdFromEmail('admin1@demo.elabftw.net'), self::ADMIN_TEAM);
    }

    private function getSession(AuthenticatedUser $user): Session
    {
        $Session = new Session(new MockArraySessionStorage());
        $Session->start();
        $Session->set('is_auth', 1);
        $Session->set('userid', $user->userid);
        $Session->set('team', $user->team);
        return $Session;
    }
}
