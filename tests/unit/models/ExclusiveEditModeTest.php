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
use Elabftw\Exceptions\ImproperActionException;
use Elabftw\Traits\TestsUtilsTrait;

class ExclusiveEditModeTest extends \PHPUnit\Framework\TestCase
{
    use TestsUtilsTrait;

    private Experiments $Experiments;

    private Experiments $Visitor;

    protected function setUp(): void
    {
        $owner = $this->getRandomUserInTeam(1, admin: 1);
        $visitor = $this->getRandomUserInTeam(1);
        $this->Experiments = $this->getFreshExperimentWithGivenUser($owner);
        $this->Visitor = new Experiments($visitor, $this->Experiments->id);
    }

    public function testLockExperiment(): void
    {
        $this->assertFalse($this->Experiments->ExclusiveEditMode->isActive());
        $this->assertTrue($this->Experiments->ExclusiveEditMode->activate());
        // will be false because it's ourself
        $this->assertFalse($this->Experiments->ExclusiveEditMode->isActive());
        $this->assertTrue($this->Visitor->ExclusiveEditMode->isActive());
        $exclusiveArr = $this->Experiments->ExclusiveEditMode->readOne();
        $this->assertEquals(1, $exclusiveArr['locked_by']);
        $this->assertEquals('Toto Le sysadmin', $exclusiveArr['locked_by_human']);
        $this->assertIsString($exclusiveArr['locked_at']);
        $this->assertEquals(19, strlen($exclusiveArr['locked_at']));
        $this->Experiments->patch(Action::Update, array('title' => $this->Experiments->entityData['title']));
        $this->Experiments->ExclusiveEditMode->destroy();
        $this->assertFalse($this->Experiments->ExclusiveEditMode->isActive());
    }

    public function testPatchExperiment(): void
    {
        $this->assertTrue($this->Experiments->ExclusiveEditMode->activate());
        // locker can patch
        $this->assertNull($this->Experiments->ExclusiveEditMode->canPatchOrExplode(Action::Update));
        // visitor can Pin
        $this->assertNull($this->Visitor->ExclusiveEditMode->canPatchOrExplode(Action::Pin));
        // visitor cannot patch
        $this->expectException(ImproperActionException::class);
        $this->Visitor->ExclusiveEditMode->canPatchOrExplode(Action::Update);
    }
}
