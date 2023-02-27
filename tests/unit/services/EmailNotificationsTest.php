<?php declare(strict_types=1);
/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2021 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

namespace Elabftw\Services;

use DateTime;
use Elabftw\Models\Notifications\CommentCreated;
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
        $Notifications = new CommentCreated(1, 2);
        $Notifications->create(1);
        $Notifications = new UserCreated(3, 'Some team name');
        $Notifications->create(1);
        $Notifications = new UserNeedValidation(3, 'Some team name');
        $Notifications->create(1);
        $Notifications = new SelfNeedValidation();
        $Notifications->create(1);
        $Notifications = new SelfIsValidated();
        $Notifications->create(1);

        // create a deadline close to now
        $d = new DateTime();
        $d->modify('+ 5 min');
        $Notifications = new StepDeadline(1, 1, 'experiments', $d->format('Y-m-d H:i:s'));
        $Notifications->create(1);

        $stub = $this->createStub(Email::class);
        $stub->method('sendEmail')->willReturn(true);
        $EmailNotifications = new EmailNotifications($stub);
        // valid ones
        $EmailNotifications->sendEmails();
    }
}
