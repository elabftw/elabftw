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
use Elabftw\Elabftw\CreateNotificationParams;
use Elabftw\Exceptions\ImproperActionException;
use Elabftw\Models\Notifications;
use Elabftw\Models\Users;

class EmailNotificationsTest extends \PHPUnit\Framework\TestCase
{
    public function testSendEmails(): void
    {
        // create a notification to fake send so there is something to process
        $Notifications = new Notifications(new Users(1));
        $body = array(
            'experiment_id' => 1,
            'commenter_userid' => 2,
        );
        $Notifications->create(new CreateNotificationParams(Notifications::COMMENT_CREATED, $body));

        $body = array('userid' => 3);
        $Notifications->create(new CreateNotificationParams(Notifications::USER_CREATED, $body));

        $body = array('userid' => 3);
        $Notifications->create(new CreateNotificationParams(Notifications::USER_NEED_VALIDATION, $body));
        $Notifications->create(new CreateNotificationParams(Notifications::SELF_NEED_VALIDATION));
        $Notifications->create(new CreateNotificationParams(Notifications::SELF_IS_VALIDATED));
        // create a deadline close to now
        $d = new DateTime();
        $d->modify('+ 5 min');
        $body = array(
                'step_id' => 1,
                'entity_id' => 1,
                'entity_page' => 'experiments',
                'deadline' => $d->format('Y-m-d H:i:s'),
        );
        $Notifications->create(new CreateNotificationParams(Notifications::STEP_DEADLINE, $body));


        $stub = $this->createStub(Email::class);
        $stub->method('sendEmail')->willReturn(true);
        $EmailNotifications = new EmailNotifications($stub);
        // valid ones
        $EmailNotifications->sendEmails();

        // unknown notification category
        // made separately so sendEmails can hit the return statement
        $Notifications->create(new CreateNotificationParams(1337));
        $this->expectException(ImproperActionException::class);
        $EmailNotifications->sendEmails();
    }
}
