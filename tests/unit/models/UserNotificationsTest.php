<?php

declare(strict_types=1);
/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2023 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

namespace Elabftw\Models;

use Elabftw\Enums\Action;
use Elabftw\Models\Notifications\StepDeadline;
use Elabftw\Models\Notifications\UserNotifications;

class UserNotificationsTest extends \PHPUnit\Framework\TestCase
{
    private UserNotifications $UserNotifications;

    private Users $Users;

    protected function setUp(): void
    {
        $this->Users = new Users(1, 1);
        $this->UserNotifications = new UserNotifications($this->Users, 1);
    }

    public function testGetApiPath(): void
    {
        $this->assertEquals('api/v2/users/1/notifications/', $this->UserNotifications->getApiPath());
    }

    public function testReadAll(): void
    {
        // create one so we have something to read
        $Notif = new StepDeadline(1, 1, 'experiments', '2023-02-28 01:24:21');
        $Notif->create(1);
        // also remove this setting so we go in all code paths
        $this->Users->userData['notif_step_deadline'] = 0;
        $this->assertIsArray($this->UserNotifications->readAll());
    }

    public function testReadOne(): void
    {
        $this->assertIsArray($this->UserNotifications->readOne());
    }

    public function testPatch(): void
    {
        $this->assertIsArray($this->UserNotifications->patch(Action::Update, array()));
    }

    public function testDestroy(): void
    {
        $this->assertTrue($this->UserNotifications->destroy());
    }
}
