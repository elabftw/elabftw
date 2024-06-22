<?php
/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @author Marcel Bolten <github@marcelbolten.de>
 * @copyright 2024 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

declare(strict_types=1);

namespace Elabftw\Models;

use Elabftw\Enums\Action;
use Elabftw\Enums\BasePermissions;
use Elabftw\Exceptions\ImproperActionException;

class ExclusiveEditModeTest extends \PHPUnit\Framework\TestCase
{
    private Experiments $ExperimentAdmin;

    private Experiments $ExperimentUser;

    private Items $ItemAdmin;

    protected function setUp(): void
    {
        $sysAdmin = new Users(1, 1);
        $this->ExperimentAdmin = new Experiments($sysAdmin, 1);
        $this->ExperimentAdmin->patch(Action::Update, array('canwrite' => BasePermissions::Team->toJson()));
        $this->ItemAdmin = new Items($sysAdmin, 1);
        $user = new Users(2, 1);
        $this->ExperimentUser = new Experiments($user, 1);
    }

    protected function tearDown(): void
    {
        // cleanup after expectException
        if ($this->ExperimentAdmin->ExclusiveEditMode->isActive) {
            $this->ExperimentAdmin->patch(Action::ExclusiveEditMode, array());
        }
        if ($this->ItemAdmin->ExclusiveEditMode->isActive) {
            $this->ItemAdmin->patch(Action::ExclusiveEditMode, array());
        }
    }

    public function testLockExperiment(): void
    {
        $this->assertFalse($this->ExperimentAdmin->ExclusiveEditMode->isActive);
        $this->ExperimentAdmin->patch(Action::ExclusiveEditMode, array());
        $this->assertTrue($this->ExperimentAdmin->ExclusiveEditMode->isActive);
        $this->assertEquals(1, $this->ExperimentAdmin->ExclusiveEditMode->dataArr['locked_by']);
        $this->assertEquals('Toto Le sysadmin', $this->ExperimentAdmin->ExclusiveEditMode->dataArr['fullname']);
        $this->assertIsString($this->ExperimentAdmin->ExclusiveEditMode->dataArr['locked_at']);
        $this->assertEquals(19, strlen($this->ExperimentAdmin->ExclusiveEditMode->dataArr['locked_at']));
        $this->ExperimentAdmin->patch(Action::Update, array('title' => $this->ExperimentAdmin->entityData['title']));
        $this->ExperimentAdmin->patch(Action::ExclusiveEditMode, array());
        $this->assertFalse($this->ExperimentAdmin->ExclusiveEditMode->isActive);
    }

    public function testLockItems(): void
    {
        $this->assertFalse($this->ItemAdmin->ExclusiveEditMode->isActive);
        $this->ItemAdmin->patch(Action::ExclusiveEditMode, array());
        $this->assertTrue($this->ItemAdmin->ExclusiveEditMode->isActive);
        $this->assertEquals(1, $this->ItemAdmin->ExclusiveEditMode->dataArr['locked_by']);
        $this->assertEquals('Toto Le sysadmin', $this->ItemAdmin->ExclusiveEditMode->dataArr['fullname']);
        $this->assertIsString($this->ItemAdmin->ExclusiveEditMode->dataArr['locked_at']);
        $this->assertEquals(19, strlen($this->ItemAdmin->ExclusiveEditMode->dataArr['locked_at']));
        $this->ItemAdmin->patch(Action::ExclusiveEditMode, array());
        $this->assertFalse($this->ItemAdmin->ExclusiveEditMode->isActive);
    }

    public function testPatchOfLockedEntityByNonAdmin(): void
    {
        $this->ExperimentAdmin->patch(Action::ExclusiveEditMode, array());
        $this->ExperimentUser->readOne();
        $this->expectException(ImproperActionException::class);
        $this->ExperimentUser->patch(Action::Update, array('title' => 'test'));
    }

    public function testPatchOfLockedEntityByAdmin(): void
    {
        $this->ExperimentUser->patch(Action::ExclusiveEditMode, array());
        $this->ExperimentAdmin->readOne();
        $this->expectException(ImproperActionException::class);
        $this->ExperimentAdmin->patch(Action::Update, array('title' => 'test'));
    }

    public function testEditModeRemovalBySysadmin(): void
    {
        $this->ExperimentUser->patch(Action::ExclusiveEditMode, array());
        $this->assertequals(
            $this->ExperimentUser->Users->userid,
            $this->ExperimentUser->entityData['exclusive_edit_mode']['locked_by']
        );
        $this->ExperimentAdmin->readOne();
        $this->ExperimentAdmin->patch(Action::ExclusiveEditMode, array());
        $this->assertFalse($this->ExperimentAdmin->ExclusiveEditMode->isActive);
    }

    public function testIllegalLockRemoval(): void
    {
        $this->ExperimentAdmin->patch(Action::ExclusiveEditMode, array());
        $this->ExperimentUser->readOne();
        $this->expectException(ImproperActionException::class);
        $this->ExperimentUser->patch(Action::ExclusiveEditMode, array());
    }
}
