<?php

declare(strict_types=1);

/**
 * @author Nicolas CARPi / Deltablot
 * @copyright 2026 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

namespace Elabftw\Elabftw;

use Elabftw\Auth\Impersonation;
use Elabftw\Exceptions\ForbiddenException;
use Elabftw\Models\Config;
use Elabftw\Models\Users\AuthenticatedUser;
use Elabftw\Models\Users\Users;
use Elabftw\Traits\TestsUtilsTrait;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;

/**
 * Make sure the restrictions of impersonation are really applied when booting the app, not only in isolation
 */
final class AppImpersonationTest extends TestCase
{
    use TestsUtilsTrait;

    private const int SYSADMIN_TEAM = 1;

    private int $targetId;

    protected function setUp(): void
    {
        $this->targetId = $this->getUserIdFromEmail('user1@demo.elabftw.net');
    }

    // the connection is a singleton: never leak the flag to the other tests
    protected function tearDown(): void
    {
        Db::getConnection()->setReadOnly(false);
    }

    public function testWriteRequestIsRefusedAndSessionIsKept(): void
    {
        $App = $this->getApp(Request::create(
            '/app/controllers/UcpController.php',
            Request::METHOD_POST,
            array('mfa_secret' => 'JBSWY3DPEHPK3PXP', 'mfa_code' => '123456'),
            server: array('SCRIPT_NAME' => '/app/controllers/UcpController.php'),
        ));
        try {
            $App->boot();
            $this->fail('A write request was accepted while impersonating.');
        } catch (ForbiddenException) {
            // a refused request must not log out the impersonator
            $this->assertSame($this->targetId, $App->Session->get('userid'));
            $this->assertSame(1, new Impersonation($App->Session, false)->getImpersonatorId());
        }
    }

    public function testMethodOverrideIsRefused(): void
    {
        $App = $this->getApp(Request::create(
            '/app/controllers/UcpController.php',
            Request::METHOD_POST,
            array('mfa_secret' => 'JBSWY3DPEHPK3PXP', 'mfa_code' => '123456'),
            server: array('SCRIPT_NAME' => '/app/controllers/UcpController.php', 'HTTP_X_HTTP_METHOD_OVERRIDE' => 'GET'),
        ));
        $this->expectException(ForbiddenException::class);
        $App->boot();
    }

    public function testReadRequestMakesTheDatabaseReadOnly(): void
    {
        $App = $this->getApp(Request::create('/experiments.php', server: array('SCRIPT_NAME' => '/experiments.php')));
        $App->boot();

        $this->assertSame(1, $App->Users->impersonatorId);
        $this->assertSame($this->targetId, $App->Users->userid);
        $this->assertTrue(Db::getConnection()->isReadOnly());
        // whatever code runs next, it cannot write
        $this->expectException(ForbiddenException::class);
        Db::getConnection()->prepare('UPDATE users SET firstname = firstname WHERE userid = 0');
    }

    public function testStoppingAndLoggingOutCanWrite(): void
    {
        foreach (array('/app/logout.php', '/app/controllers/ImpersonationController.php') as $script) {
            $method = $script === '/app/logout.php' ? Request::METHOD_GET : Request::METHOD_POST;
            $App = $this->getApp(Request::create($script, $method, server: array('SCRIPT_NAME' => $script)));
            $App->boot();
            $this->assertFalse(Db::getConnection()->isReadOnly(), $script);
        }
    }

    public function testNoImpersonationNoRestriction(): void
    {
        $Session = new Session(new MockArraySessionStorage());
        $Session->set('is_auth', 1);
        $Session->set('userid', $this->targetId);
        $Session->set('team', 2);
        $App = $this->newApp(Request::create('/app/controllers/UcpController.php', Request::METHOD_POST, server: array('SCRIPT_NAME' => '/app/controllers/UcpController.php')), $Session);
        $App->boot();

        $this->assertNull($App->Users->impersonatorId);
        $this->assertFalse(Db::getConnection()->isReadOnly());
    }

    /**
     * Get an App whose session is a Sysadmin impersonating a user, started through the real code path
     */
    private function getApp(Request $Request): App
    {
        $sysadmin = new AuthenticatedUser(1, self::SYSADMIN_TEAM);
        $Session = new Session(new MockArraySessionStorage());
        $Session->start();
        $Session->set('is_auth', 1);
        $Session->set('userid', $sysadmin->userid);
        $Session->set('team', $sysadmin->team);
        new Impersonation($Session, false)->start($sysadmin, $this->targetId);
        return $this->newApp($Request, $Session);
    }

    private function newApp(Request $Request, Session $Session): App
    {
        return new App(
            $Request,
            $Session,
            Config::getConfig(),
            $this->createStub(LoggerInterface::class),
            new Users(),
        );
    }
}
