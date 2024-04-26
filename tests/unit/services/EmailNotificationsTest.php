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
use Elabftw\Models\Notifications\CommentCreated;
use Elabftw\Models\Notifications\EventDeleted;
use Elabftw\Models\Notifications\MathjaxFailed;
use Elabftw\Models\Notifications\PdfAppendmentFailed;
use Elabftw\Models\Notifications\SelfIsValidated;
use Elabftw\Models\Notifications\SelfNeedValidation;
use Elabftw\Models\Notifications\StepDeadline;
use Elabftw\Models\Notifications\UserCreated;
use Elabftw\Models\Notifications\UserNeedValidation;

class EmailNotificationsTest extends \PHPUnit\Framework\TestCase
{
    public function testSendEmails(): void
    {
        // create a notification to fake send so there is something to process
        $Notifications = new CommentCreated('experiments', 1, 2);
        $Notifications->create(1);
        $Notifications = new UserCreated(3, 'Some team name');
        $Notifications->create(1);
        $Notifications = new UserNeedValidation(3, 'Some team name');
        $Notifications->create(1);
        $Notifications = new SelfNeedValidation();
        $Notifications->create(1);
        $Notifications = new SelfIsValidated();
        $Notifications->create(1);
        $Notifications = new MathjaxFailed(1, 'experiments');
        $Notifications->create(1);
        $Notifications = new PdfAppendmentFailed(1, 'experiments', 'file1.pdf, file2.pdf');
        $Notifications->create(1);

        $d = new DateTime();

        $Notifications = new EventDeleted(
            array('item' => 12, 'start' => $d->format('Y-m-d H:i:s'), 'end' => $d->format('Y-m-d H:i:s')),
            'Daniel Balavoine',
        );
        $targetCount = $Notifications->postAction(Action::Create, array(
            'msg' => 'Had to cancel booking because my cells died :(',
            'target' => 'team',
            'targetid' => 1,
        ));
        $this->assertEquals(9, $targetCount);
        $this->assertIsArray($Notifications->readOne());
        $this->assertIsArray($Notifications->patch(Action::Update, array()));
        $this->assertIsString($Notifications->getPage());
        $this->assertFalse($Notifications->destroy());

        // create a deadline close to now
        $d->modify('+ 5 min');
        $Notifications = new StepDeadline(1, 1, 'experiments', $d->format('Y-m-d H:i:s'));
        $Notifications->create(1);
        // create it several times to toggle it and go in all code paths
        $Notifications->create(1);
        $Notifications->create(1);

        $stub = $this->createStub(Email::class);
        $stub->method('sendEmail')->willReturn(true);
        $EmailNotifications = new EmailNotifications($stub);
        // valid ones
        $EmailNotifications->sendEmails();
    }
}
