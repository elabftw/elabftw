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

use Codeception\Attribute\DataProvider;
use Elabftw\Exceptions\UnauthorizedException;
use Elabftw\Models\Config;
use Elabftw\Models\Users\AnonymousUser;
use Elabftw\Models\Users\AuthenticatedUser;
use Elabftw\Models\Users\Users;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use ReflectionClass;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;

final class AppAnonymousSessionTest extends TestCase
{
    #[DataProvider('provideAnonymousPages')]
    public function testDisabledAnonymousSessionIsInvalidated(string $scriptName): void
    {
        $App = $this->getApp($scriptName);
        $sessionId = $App->Session->getId();

        try {
            $App->boot();
            self::fail('Expected anonymous session to be rejected after anonymous access was disabled.');
        } catch (UnauthorizedException) {
            self::assertSame(array(), $App->Session->all());
            self::assertNotSame($sessionId, $App->Session->getId());
        }
    }

    public static function provideAnonymousPages(): array
    {
        return array(
            'api' => array('/app/controllers/ApiController.php'),
            'database' => array('/database.php'),
            'download' => array('/app/download.php'),
            'experiments' => array('/experiments.php'),
            'index' => array('/index.php'),
            'logout' => array('/app/logout.php'),
            'make' => array('/make.php'),
        );
    }

    public function testExplicitRequestUserCannotBypassAnonymousSessionRevocation(): void
    {
        $App = $this->getApp('/experiments.php');
        $requestUser = new ReflectionClass(AnonymousUser::class)->newInstanceWithoutConstructor();

        try {
            $App->boot($requestUser);
            self::fail('Expected anonymous session to be rejected before loading the request user.');
        } catch (UnauthorizedException) {
            self::assertSame(array(), $App->Session->all());
        }
    }

    public function testAnonymousSessionRemainsValidWhenEnabled(): void
    {
        $App = $this->getApp('/experiments.php');
        $App->Config->configArr['anon_users'] = '1';

        $App->boot();

        self::assertInstanceOf(AnonymousUser::class, $App->Users);
        self::assertSame(1, $App->Session->get('is_auth'));
        self::assertSame(1, $App->Session->get('is_anon'));
    }

    public function testAuthenticatedSessionRemainsValidWhenAnonymousAccessIsDisabled(): void
    {
        $App = $this->getApp('/experiments.php');
        $App->Session->remove('is_anon');
        $App->Session->set('userid', 1);

        $App->boot();

        self::assertInstanceOf(AuthenticatedUser::class, $App->Users);
        self::assertSame(1, $App->Session->get('is_auth'));
        self::assertSame(1, $App->Session->get('userid'));
    }

    public function testDisablingAnonymousAccessRevokesAnExistingSession(): void
    {
        $App = $this->getApp('/experiments.php');
        $App->Config->configArr['anon_users'] = '1';
        $App->boot();
        $App->Config->configArr['anon_users'] = '0';

        try {
            $App->boot();
            self::fail('Expected the existing anonymous session to be revoked after the policy changed.');
        } catch (UnauthorizedException) {
            self::assertSame(array(), $App->Session->all());
        }
    }

    private function getApp(string $scriptName): App
    {
        $Config = new ReflectionClass(Config::class)->newInstanceWithoutConstructor();
        $Config->configArr = array(
            'anon_users' => '0',
            'autologout_time' => '0',
            'lang' => 'en_GB',
        );
        $Request = Request::create($scriptName);
        $Request->server->set('SCRIPT_NAME', $scriptName);
        $Session = new Session(new MockArraySessionStorage());
        $Session->set('is_auth', 1);
        $Session->set('is_anon', 1);
        $Session->set('userid', 0);
        $Session->set('team', 1);

        return new App(
            $Request,
            $Session,
            $Config,
            $this->createStub(LoggerInterface::class),
            new ReflectionClass(Users::class)->newInstanceWithoutConstructor(),
        );
    }
}
