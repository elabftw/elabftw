<?php

declare(strict_types=1);
/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2022 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

namespace Elabftw\Services;

use Elabftw\Enums\Action;
use Elabftw\Enums\Usergroup;
use Elabftw\Exceptions\IllegalActionException;
use Elabftw\Models\Config;
use Elabftw\Models\Users\Users;
use Elabftw\Traits\TestsUtilsTrait;

class UserCreatorTest extends \PHPUnit\Framework\TestCase
{
    use TestsUtilsTrait;

    private UserCreator $UserCreator;

    protected function setUp(): void
    {
        $this->UserCreator = new UserCreator($this->getUserInTeam(1, admin: 1), array(
            'team' => 1,
            'email' => 'livelongandprosper@vulcan.gov.vn',
            'firstname' => 'Leonard',
            'lastname' => 'Nimoy',
            'usergroup' => Usergroup::User->value,
        ));
    }

    protected function tearDown(): void
    {
        $Config = Config::getConfig();
        $Config->patch(Action::Update, array('admins_create_users' => '1'));
    }

    public function testCreate(): void
    {
        $this->assertIsInt($this->UserCreator->create());
    }

    public function testCreateFromAdminUser(): void
    {
        $Admin = $this->getUserInTeam(team: 2, admin: 1);
        $UserCreator = new UserCreator($Admin, array(
            'team' => 2,
            'email' => 'praisetheprophets@staff.ds9.bjr',
            'firstname' => 'Kira',
            'lastname' => 'Nerys',
            'usergroup' => Usergroup::User->value,
        ));
        $this->assertIsInt($UserCreator->create());
    }

    public function testCreateSysadminFromAdminUser(): void
    {
        $UserCreator = new UserCreator($this->getUserInTeam(2), array(
            'team' => 2,
            'email' => 'vic@holodeck.ds9.bjr',
            'firstname' => 'Vic',
            'lastname' => 'Fontaine',
            'usergroup' => Usergroup::Sysadmin->value,
        ));
        $this->expectException(IllegalActionException::class);
        $UserCreator->create();
    }

    public function testCreateButItIsDisabled(): void
    {
        $Config = Config::getConfig();
        $Config->patch(Action::Update, array('admins_create_users' => '0'));
        $UserCreator = new UserCreator(new Users(4, 2), array(
            'team' => 2,
            'email' => 'vic@holodeck.ds9.bjr',
            'firstname' => 'Vic',
            'lastname' => 'Fontaine',
            'usergroup' => Usergroup::Sysadmin->value,
        ));
        $this->expectException(IllegalActionException::class);
        $UserCreator->create();
    }
}
