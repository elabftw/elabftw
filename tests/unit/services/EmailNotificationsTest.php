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
use Elabftw\Enums\Action;
use Elabftw\Enums\EntityType;
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
use Symfony\Component\Console\Output\ConsoleOutput;

class EmailNotificationsTest extends \PHPUnit\Framework\TestCase
{
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
            array('item' => 12, 'start' => $d->format('Y-m-d H:i:s'), 'end' => $d->format('Y-m-d H:i:s')),
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

        $stub = $this->createStub(Email::class);
        $stub->method('sendEmail')->willReturn(true);
        $EmailNotifications = new EmailNotifications($stub);
        // valid ones
        $EmailNotifications->sendEmails(new ConsoleOutput());
    }
}
