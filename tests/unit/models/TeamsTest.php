<?php

declare(strict_types=1);
/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2021 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

namespace Elabftw\Models;

use Elabftw\Enums\Action;
use Elabftw\Exceptions\IllegalActionException;
use Elabftw\Exceptions\ImproperActionException;
use Elabftw\Models\Users\Users;

class TeamsTest extends \PHPUnit\Framework\TestCase
{
    private Teams $Teams;

    protected function setUp(): void
    {
        $this->Teams = new Teams(new Users(1, 1), 1);
    }

    public function testGetApiPath(): void
    {
        $this->assertEquals('api/v2/teams/', $this->Teams->getApiPath());
    }

    public function testCreate(): void
    {
        $this->assertIsInt($this->Teams->postAction(Action::Create, array('name' => 'Test team')));
    }

    public function testImproperAction(): void
    {
        $this->expectException(ImproperActionException::class);
        $this->Teams->patch(Action::Timestamp, array());
    }

    public function testUpdate(): void
    {
        $params = array(
            'announcement' => '',
            'newcomer_threshold' => '12',
            'orgid' => 'Something',
        );
        $this->assertIsArray($this->Teams->patch(Action::Update, $params));
        $new = 'yep';
        $params = array(
            'announcement' => $new,
        );
        $patched = $this->Teams->patch(Action::Update, $params);
        $this->assertIsArray($patched);
        $this->assertEquals($new, $patched['announcement']);
    }

    public function testReadNamesFromIds(): void
    {
        $this->assertCount(3, $this->Teams->readNamesFromIds(array(1, 2, 3)));
    }

    public function testRead(): void
    {
        $this->assertIsArray($this->Teams->readOne());
        $this->assertIsArray($this->Teams->readAll());
        $this->assertIsArray($this->Teams->readAllVisible());
    }

    public function testDestroy(): void
    {
        $id = $this->Teams->postAction(Action::Create, array('name' => 'Destroy me'));
        $this->Teams->setId($id);
        $this->Teams->bypassWritePermission = true;
        $this->assertTrue($this->Teams->destroy());
        // try to destroy a team with data
        $this->Teams->setId(1);
        $this->expectException(ImproperActionException::class);
        $this->Teams->destroy();
    }

    public function testSendOnboardingEmails(): void
    {
        $userids = array('userids' => array(1, 2, 3, 4, 5));

        $this->assertIsArray($this->Teams->patch(
            Action::SendOnboardingEmails,
            $userids,
        ));

        $Team = new Teams(new Users(2, 1), 1);
        $this->expectException(IllegalActionException::class);
        $Team->patch(
            Action::SendOnboardingEmails,
            $userids,
        );
    }
}
