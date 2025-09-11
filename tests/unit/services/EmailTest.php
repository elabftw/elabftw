<?php

declare(strict_types=1);
/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2023 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

namespace Elabftw\Services;

use Elabftw\Enums\EmailTarget;
use Elabftw\Exceptions\ImproperActionException;
use Elabftw\Models\Info;
use Elabftw\Models\Teams;
use Elabftw\Models\Users\UltraAdmin;
use Elabftw\Traits\TestsUtilsTrait;
use Monolog\Handler\NullHandler;
use Monolog\Logger;
use Symfony\Component\Mailer\Exception\TransportException;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;

class EmailTest extends \PHPUnit\Framework\TestCase
{
    use TestsUtilsTrait;

    private Email $Email;

    private Logger $Logger;

    protected function setUp(): void
    {
        $this->Logger = new Logger('elabftw');
        // use NullHandler because we don't care about logs here
        $this->Logger->pushHandler(new NullHandler());
        $MockMailer = $this->createMock(MailerInterface::class);
        $this->Email = new Email($MockMailer, $this->Logger, 'toto@yopmail.com', demoMode: false);
    }

    public function testTestemailSendInDemo(): void
    {
        $Logger = new Logger('elabftw');
        $Logger->pushHandler(new NullHandler());
        $MockMailer = $this->createMock(MailerInterface::class);
        $EmailInDemo = new Email($MockMailer, $this->Logger, 'toto@yopmail.com', demoMode: true);
        $this->assertFalse($EmailInDemo->testemailSend('toto@example.com'));
    }

    public function testTestemailSend(): void
    {
        $this->assertTrue($this->Email->testemailSend('toto@example.com'));
    }

    public function testNotConfigured(): void
    {
        $MockMailer = $this->createMock(MailerInterface::class);
        $NotConfiguredEmail = new Email($MockMailer, $this->Logger, 'notconfigured@example.com');
        $this->assertFalse($NotConfiguredEmail->testemailSend('toto@example.com'));
    }

    public function testTransportException(): void
    {
        $MockMailer = $this->createMock(MailerInterface::class);
        $MockMailer->method('send')->willThrowException(new TransportException());
        $Email = new Email($MockMailer, $this->Logger, 'yep@nope.blah');
        $this->expectException(ImproperActionException::class);
        $Email->testemailSend('toto@example.com');

    }

    public function testMassEmail(): void
    {
        $instanceInfo = new Info()->readAll();
        $Team1 = new Teams(new UltraAdmin(), 1);
        $team1Stats = $Team1->getStats();
        $replyTo = new Address('sender@example.com', 'Sergent Garcia');
        // Note that non-validated users are not active users
        $this->assertTrue($instanceInfo['active_users_count'] >= $this->Email->massEmail(EmailTarget::ActiveUsers, null, '', 'yep', $replyTo, true));
        // not grouped
        $this->assertTrue($instanceInfo['active_users_count'] >= $this->Email->massEmail(EmailTarget::ActiveUsers, null, '', 'yep', $replyTo, false));
        // FIXME this doesn't work and I couldn't figure out why
        //$this->assertEquals($team1Stats['active_users_count'], $this->Email->massEmail(EmailTarget::Team, 1, 'Message to team 1', 'yep', $replyTo, true));
        $this->assertEquals(0, $this->Email->massEmail(EmailTarget::TeamGroup, 1, 'Important message', 'yep', $replyTo, true));
        // TODO make it variable
        //$this->assertEquals(9, $this->Email->massEmail(EmailTarget::Admins, null, 'Important message to admins', 'yep', $replyTo, true));
        $this->assertTrue($this->Email->massEmail(EmailTarget::Admins, null, 'Important message to admins', 'yep', $replyTo, true) > 1);
        $this->assertEquals(1, $this->Email->massEmail(EmailTarget::Sysadmins, null, 'Important message to sysadmins', 'yep', $replyTo, true));
        $this->assertEquals(0, $this->Email->massEmail(EmailTarget::BookableItem, 1, 'Oops', 'My cells died', $replyTo, true));
        $this->assertEquals($team1Stats['active_admins_count'], $this->Email->massEmail(EmailTarget::AdminsOfTeam, 1, 'Important message to admins of a team', 'yep', $replyTo, true));
    }

    public function testSendEmail(): void
    {
        $this->assertTrue($this->Email->sendEmail(new Address('a@a.fr', 'blah'), 's', 'b'));
        // with cc
        $this->assertTrue($this->Email->sendEmail(new Address('a@a.fr', 'blah'), 's', 'b', array(new Address('cc@example.com', 'cc'))));
    }

    public function testNotifySysadminsTsBalance(): void
    {
        $this->assertTrue($this->Email->notifySysadminsTsBalance(12));
    }
}
