<?php

declare(strict_types=1);
/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2025 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

namespace Elabftw\Models;

use Elabftw\Enums\Action;
use Elabftw\Exceptions\IllegalActionException;
use Elabftw\Exceptions\ImproperActionException;
use Elabftw\Models\Users\Users;
use Elabftw\Services\Email;
use Elabftw\Traits\TestsUtilsTrait;
use Monolog\Handler\NullHandler;
use Monolog\Logger;
use Symfony\Component\Mailer\MailerInterface;

class InstanceTest extends \PHPUnit\Framework\TestCase
{
    use TestsUtilsTrait;

    private Instance $Instance;

    private Email $email;

    protected function setUp(): void
    {
        $logger = new Logger('elabftw');
        // use NullHandler because we don't care about logs here
        $logger->pushHandler(new NullHandler());
        $MockMailer = $this->createMock(MailerInterface::class);
        $this->email = new Email($MockMailer, $logger, 'toto@yopmail.com', demoMode: false);
        $this->Instance = new Instance(new Users(1, 1), $this->email, true);
    }

    public function testUserNotSysadmin(): void
    {
        $Instance = new Instance($this->getUserInTeam(2), $this->email, true);
        $this->expectException(IllegalActionException::class);
        $Instance->postAction(Action::Destroy, array());
    }

    public function testApiPath(): void
    {
        $this->assertEquals('api/v2/instance/', $this->Instance->getApiPath());
    }

    public function testClearNoLogin(): void
    {
        $this->assertSame(0, $this->Instance->postAction(Action::AllowUntrusted, array()));
    }

    public function testClearLockoutDevices(): void
    {
        $this->assertSame(0, $this->Instance->postAction(Action::ClearLockedOutDevices, array()));
    }

    public function testEmailTest(): void
    {
        $this->assertSame(0, $this->Instance->postAction(Action::Test, array('email' => 'null@example.com')));
    }

    public function testMassEmail(): void
    {
        $this->assertSame(0, $this->Instance->postAction(Action::Email, array('target' => 'sysadmins', 'subject' => 'a', 'body' => 'a')));
    }

    public function testInvalidAction(): void
    {
        $this->expectException(ImproperActionException::class);
        $this->Instance->postAction(Action::Create, array());
    }
}
