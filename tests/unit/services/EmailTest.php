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
use Monolog\Handler\NullHandler;
use Monolog\Logger;
use Symfony\Component\Mailer\Exception\TransportException;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;

class EmailTest extends \PHPUnit\Framework\TestCase
{
    private Email $Email;

    private Logger $Logger;

    protected function setUp(): void
    {
        $this->Logger = new Logger('elabftw');
        // use NullHandler because we don't care about logs here
        $this->Logger->pushHandler(new NullHandler());
        $MockMailer = $this->createMock(MailerInterface::class);
        $this->Email = new Email($MockMailer, $this->Logger, 'toto@yopmail.com');
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
        $replyTo = new Address('sender@example.com', 'Sergent Garcia');
        // Note that non-validated users are not active users

        // count the actual number of items in db (for users & admins), so that the test can be automated
        $activeUsersEmails = $this->Email->getAllEmailAddresses(EmailTarget::ActiveUsers);
        $adminsEmails = $this->Email->getAllEmailAddresses(EmailTarget::Admins);
        $sysAdminsEmails = $this->Email->getAllEmailAddresses(EmailTarget::Sysadmins);

        $activeUsersEmailsCount = self::countEmails($activeUsersEmails);
        $adminsEmailsCount = self::countEmails($adminsEmails);
        $sysAdminsEmailsCount = self::countEmails($sysAdminsEmails);

        // count the admins for current team
        $TeamsHelper = new TeamsHelper(1);
        $adminsIds = $TeamsHelper->getAllAdminsUserid();
        // we have to remove the current user which counts as one admin too many
        $allAdminsCountWithoutCurrentSender = count($adminsIds) - 1;

        // assert emails sent count with emails that can be fetched with getAllEmailAddresses()
        $this->assertEquals($activeUsersEmailsCount, $this->Email->massEmail(EmailTarget::ActiveUsers, null, '', 'yep', $replyTo));
        $this->assertEquals($adminsEmailsCount, $this->Email->massEmail(EmailTarget::Admins, null, 'Important message to admins', 'yep', $replyTo));
        $this->assertEquals($sysAdminsEmailsCount, $this->Email->massEmail(EmailTarget::Sysadmins, null, 'Important message to sysadmins', 'yep', $replyTo));
        $this->assertEquals($allAdminsCountWithoutCurrentSender, $this->Email->massEmail(EmailTarget::AdminsOfTeam, 1, 'Important message to admins of a team', 'yep', $replyTo));
        $this->assertEquals(135, $this->Email->massEmail(EmailTarget::Team, 1, 'Important message', 'yep', $replyTo));
        $this->assertEquals(0, $this->Email->massEmail(EmailTarget::TeamGroup, 1, 'Important message', 'yep', $replyTo));
        $this->assertEquals(0, $this->Email->massEmail(EmailTarget::BookableItem, 1, 'Oops', 'My cells died', $replyTo));

    }

    public function testSendEmail(): void
    {
        $this->assertTrue($this->Email->sendEmail(new Address('a@a.fr', 'blah'), 's', 'b'));
    }

    public function testNotifySysadminsTsBalance(): void
    {
        $this->assertTrue($this->Email->notifySysadminsTsBalance(12));
    }

    private function countEmails(array $batches): int
    {
        $count = 0;
        foreach ($batches as $batch) {
            $count += count($batch);
        }
        return $count;
    }
}
