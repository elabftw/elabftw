<?php

declare(strict_types=1);
/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2021 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

namespace Elabftw\Services;

use DateTime;
use Elabftw\Elabftw\Db;
use Elabftw\Enums\Action;
use Elabftw\Enums\EntityType;
use Elabftw\Enums\Notifications;
use Elabftw\Factories\NotificationsFactory;
use Elabftw\Models\Notifications\CommentCreated;
use Elabftw\Models\Notifications\EventDeleted;
use Elabftw\Models\Notifications\MathjaxFailed;
use Elabftw\Models\Notifications\PdfAppendmentFailed;
use Elabftw\Models\Notifications\SelfIsValidated;
use Elabftw\Models\Notifications\SelfNeedValidation;
use Elabftw\Models\Notifications\StepDeadline;
use Elabftw\Models\Notifications\UserCreated;
use Elabftw\Models\Notifications\UserNeedValidation;
use Elabftw\Models\Users\Users;
use Elabftw\Traits\TestsUtilsTrait;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\Console\Output\ConsoleOutput;
use PDO;
use TypeError;

class EmailNotificationsTest extends \PHPUnit\Framework\TestCase
{
    use TestsUtilsTrait;

    public function testSendEmails(): void
    {
        // create a notification to fake send so there is something to process
        $targetUser = new Users(1);
        $Notifications = new CommentCreated($targetUser, EntityType::Experiments->toPage(), 1, 2);
        $Notifications->create();
        $Notifications = new UserCreated($targetUser, 3, 'Some team name');
        $Notifications->create();
        $Notifications = new UserNeedValidation($targetUser, 3, 'Some team name');
        $Notifications->create();
        $Notifications = new SelfNeedValidation($targetUser);
        $Notifications->create();
        $Notifications = new SelfIsValidated($targetUser);
        $Notifications->create();
        $Notifications = new MathjaxFailed($targetUser, 1, EntityType::Experiments->toPage());
        $Notifications->create();
        $Notifications = new PdfAppendmentFailed($targetUser, 1, EntityType::Experiments->toPage(), 'file1.pdf, file2.pdf');
        $Notifications->create();

        $d = new DateTime();

        $Notifications = new EventDeleted(
            $targetUser,
            array(
                'item' => 12,
                'team' => 1,
                'userid' => 1,
                'start' => $d->format('Y-m-d H:i:s'),
                'end' => $d->format('Y-m-d H:i:s'),
            ),
            'Daniel Balavoine',
        );
        $targetCount = $Notifications->postAction(Action::Create, array(
            'msg' => 'Had to cancel booking because my cells died :(',
            'target' => 'team',
            'targetid' => 1,
        ));
        // TODO fix so it can vary in tests
        //$this->assertEquals(22, $targetCount);
        $this->assertIsInt($targetCount);
        $this->assertIsArray($Notifications->readOne());
        $this->assertIsArray($Notifications->patch(Action::Update, array()));
        $this->assertIsString($Notifications->getApiPath());
        $this->assertFalse($Notifications->destroy());

        // create a deadline close to now
        $d->modify('+ 5 min');
        $Notifications = new StepDeadline($targetUser, 1, 1, EntityType::Experiments->toPage(), $d->format('Y-m-d H:i:s'));
        $Notifications->create();
        // create it several times to toggle it and go in all code paths
        $Notifications->create();
        $Notifications->create();

        $this->stubEmail();
    }

    public function testDoNotSendEmailsToArchivedUserInAllTeams(): void
    {
        $targetUser = $this->getRandomUserInTeam(1);

        $Notifications = new CommentCreated($targetUser, EntityType::Experiments->toPage(), 1, 2);
        $notif = $Notifications->create();

        $this->stubEmail();

        // Check if email is sent for non archived users
        $Db = Db::getConnection();
        $sql = 'SELECT email_sent, email_sent_at FROM notifications WHERE id = :id';
        $req = $Db->prepare($sql);
        $req->bindParam(':id', $notif, PDO::PARAM_INT);
        $Db->execute($req);
        $row = $req->fetch();
        $this->assertSame(1, $row['email_sent']);
        $this->assertNotNull($row['email_sent_at']);

        // archive user
        $this->updateArchiveStatus($targetUser->userid, 1);
        $NotificationsFails = new CommentCreated($targetUser, EntityType::Experiments->toPage(), 1, 2);
        $notifFails = $NotificationsFails->create();
        $this->assertSame(0, $notifFails);

        // Restore user archive status
        $this->updateArchiveStatus($targetUser->userid, 0);
    }

    public function testAccountNotificationsWithoutTeam(): void
    {
        $targetUser = new Users(1);
        foreach (array(Notifications::UserCreated, Notifications::UserNeedValidation) as $category) {
            foreach (array(
                array('{"userid":3}', ''),
                array('{"userid":3,"team":null}', ''),
                array('{"userid":3,"team":"Some team name"}', 'Some team name'),
            ) as [$body, $team]) {
                $Factory = new NotificationsFactory($targetUser, $category->value, $body);
                $expected = $category === Notifications::UserCreated
                    ? new UserCreated($targetUser, 3, $team)
                    : new UserNeedValidation($targetUser, 3, $team);
                $this->assertSame($expected->getEmail(), $Factory->getMailable()->getEmail());
            }
        }
    }

    public function testSendEmailsContinuesAfterTypeError(): void
    {
        // send existing notifications before testing two new ones
        $this->stubEmail();
        $targetUser = new Users(1);
        new SelfIsValidated($targetUser)->create();
        new SelfIsValidated($targetUser)->create();

        $email = $this->createMock(Email::class);
        $email->expects($this->exactly(2))
            ->method('sendEmail')
            ->willThrowException(new TypeError('Invalid notification payload'));
        $output = new BufferedOutput();
        $EmailNotifications = new EmailNotifications($email);
        $EmailNotifications->sendEmails($output);

        $this->assertStringContainsString('Error sending notification: Invalid notification payload', $output->fetch());
    }

    private function stubEmail(): void
    {
        $stub = $this->createStub(Email::class);
        $stub->method('sendEmail')->willReturn(true);
        $EmailNotifications = new EmailNotifications($stub);
        // valid ones
        $EmailNotifications->sendEmails(new ConsoleOutput());
    }
}
