<?php declare(strict_types=1);
/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2021 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

namespace Elabftw\Services;

use Elabftw\Elabftw\CreateNotificationParams;
use Elabftw\Models\Notifications;

class EmailNotificationsTest extends \PHPUnit\Framework\TestCase
{
    public function testSendEmails(): void
    {
        // create a notification to fake send so there is something to process
        $Notifications = new Notifications(1);
        $Notifications->create(new CreateNotificationParams(1, array('fake' => 'notif')));
        $stub = $this->createStub(Email::class);
        $stub->method('sendEmail')->willReturn(true);
        $EmailNotifications = new EmailNotifications($stub);
        $EmailNotifications->sendEmails();
    }
}
