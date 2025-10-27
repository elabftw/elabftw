<?php

declare(strict_types=1);
/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

namespace Elabftw\Models;

use Elabftw\Enums\Action;
use Elabftw\Enums\BasePermissions;
use Elabftw\Enums\Scope;
use Elabftw\Enums\Usergroup;
use Elabftw\Enums\Users2TeamsTargets;
use Elabftw\Exceptions\IllegalActionException;
use Elabftw\Exceptions\ImproperActionException;
use Elabftw\Exceptions\ResourceNotFoundException;
use Elabftw\Models\Users\Users;
use Elabftw\Params\UserParams;
use Elabftw\Traits\TestsUtilsTrait;

class UsersTest extends \PHPUnit\Framework\TestCase
{
    use TestsUtilsTrait;

    private Users $Users;

    protected function setUp(): void
    {
        $requester = new Users(1, 1);
        $this->Users = new Users(1, 1, $requester);
    }

    public function testPopulate(): void
    {
        $this->assertTrue(is_array($this->Users->userData));
        $this->expectException(ResourceNotFoundException::class);
        new Users(1337);
    }

    public function testPostAction(): void
    {
        $this->assertIsInt($this->Users->postAction(Action::Create, array(
            'team' => 1,
            'firstname' => 'test',
            'lastname' => 'post',
            'email' => 'test@example.com',
            'orgid' => 'XYZ123',
        )));
    }

    public function testAllowUntrustedLogin(): void
    {
        $this->assertTrue($this->Users->allowUntrustedLogin());
        $this->assertTrue((new Users(2, 1))->allowUntrustedLogin());
    }

    public function testReadAllFromTeam(): void
    {
        $this->assertIsArray($this->Users->readAllFromTeam());
    }

    public function testReadFromQuery(): void
    {
        $this->assertIsArray($this->Users->readFromQuery('', 0, true, true));
        $this->assertIsArray($this->Users->readFromQuery('', 0, true, false));
        $this->assertIsArray($this->Users->readFromQuery('', 0, false, true));
        $this->assertIsArray($this->Users->readFromQuery('', 0, false, false));
        $this->assertIsArray($this->Users->readFromQuery('Toto', 1, false, false));
    }

    public function testUpdateAccount(): void
    {
        // A user SHOULD NOT be able to update their own address (under default settings)
        $params = array(
            'email' => 'tatabis@yopmail.com',
            'firstname' => 'Tata',
            'lastname' => 'Yep',
            'orcid' => '0000-0002-7494-5555',
        );
        $this->expectException(ImproperActionException::class);
        (new Users(4, 2, new Users(4, 2)))->patch(Action::Update, $params);
    }

    public function testUpdateAccountAsSysadmin(): void
    {
        // A sysadmin SHOULD be able to update any email address.
        $sysadminUser = new Users(1, 1);
        $params = array(
            'email' => 'tatabis@yopmail.com',
            'firstname' => 'Tata',
            'lastname' => 'Yep',
            'orcid' => '0000-0002-7494-5555',
        );
        $result = (new Users(4, 2, $sysadminUser))->patch(Action::Update, $params);
        $this->assertEquals('tatabis@yopmail.com', $result['email']);
        $this->assertEquals('Yep', $result['lastname']);
    }

    public function testUpdateWrongOrcid(): void
    {
        $this->expectException(ImproperActionException::class);
        (new Users(4, 2, new Users(4, 2)))->patch(Action::Update, array('orcid' => 'blah'));
    }

    public function testClearOrcid(): void
    {
        $this->Users->patch(Action::Update, array('orcid' => '0000-0002-7494-5555'));
        $this->assertEquals('0000-0002-7494-5555', $this->Users->userData['orcid']);

        $this->Users->patch(Action::Update, array('orcid' => null));
        $this->assertEmpty($this->Users->userData['orcid'], message: 'Orcid is not empty.');

        $this->Users->patch(Action::Update, array('orcid' => ''));
        $this->assertEmpty($this->Users->userData['orcid'], message: 'Orcid is not empty.');
    }

    public function testUpdatePreferences(): void
    {
        $prefsArr = array(
            'limit_nb' => 12,
            'sc_create' => 'c',
            'sc_edit' => 'e',
            'sc_favorite' => 'f',
            'sc_todo' => 't',
            'sc_search' => 's',
            'scope_experiments' => Scope::Everything->value,
            'lang' => 'en_GB',
            'pdf_format' => 'A4',
            'default_read' => BasePermissions::Organization->toJson(),
            'display_mode' => 'it',
            'sort' => 'date',
            'orderby' => 'desc',
        );
        $result = $this->Users->patch(Action::Update, $prefsArr);
        $this->assertEquals(12, $result['limit_nb']);
    }

    public function testUpdateCanManageUsers2TeamsAsUser(): void
    {
        $user = $this->getRandomUserInTeam(1);
        $this->expectException(IllegalActionException::class);
        $user->update(new UserParams('can_manage_users2teams', '1'));
    }

    public function testReadAll(): void
    {
        // read as Admin
        $res = $this->Users->readAll();
        $this->assertArrayHasKey('last_login', $res[0]);
        // now as user
        $user = $this->getUserInTeam(2);
        $Users = new Users(null, null, $user);
        $res = $Users->readAll();
        $this->assertArrayNotHasKey('auth_service', $res[0]);
    }

    public function testIsAdminOf(): void
    {
        $this->assertTrue($this->Users->isAdminOf(1));
        $this->assertTrue($this->Users->isAdminOf(2));
        $admin2 = $this->getUserInTeam(team: 2, admin: 1);
        $this->assertTrue($this->Users->isAdminOf($admin2->userid));
        $this->assertFalse($admin2->isAdminOf(2));
    }

    public function testGetApiPath(): void
    {
        $this->assertEquals('api/v2/users/', $this->Users->getApiPath());
    }

    public function testUpdateTooShortPassword(): void
    {
        $Users = new Users(4, 2, new Users(4, 2));
        $this->expectException(ImproperActionException::class);
        $Users->patch(Action::Update, array('password' => 'short'));
    }

    public function testDisable2fa(): void
    {
        $Users = new Users(4, 2, new Users(4, 2));
        $this->assertIsArray($Users->patch(Action::Disable2fa, array()));
        $Users = new Users(2, 1, new Users(4, 2));
        $this->expectException(IllegalActionException::class);
        $Users->patch(Action::Disable2fa, array());
    }

    public function testUpdatePasswordNoCurrentPasswordProvided(): void
    {
        $Users = new Users(4, 2, new Users(4, 2));
        $this->expectException(ImproperActionException::class);
        $Users->patch(Action::UpdatePassword, array('password' => 'newPassw0rd'));
    }

    public function testUpdatePasswordIncorrectCurrentPasswordProvided(): void
    {
        $Users = new Users(4, 2, new Users(4, 2));
        $this->expectException(ImproperActionException::class);
        $Users->patch(Action::UpdatePassword, array('password' => 'newPassw0rd', 'current_password' => 'incorrectPassword'));
    }

    public function testUpdatePasswordWithEmptyPassword(): void
    {
        $Users = new Users(4, 2, new Users(4, 2));
        $this->expectException(ImproperActionException::class);
        $Users->patch(Action::UpdatePassword, array('password' => '', 'current_password' => 'testPassword'));
    }

    public function testUpdatePassword(): void
    {
        $Users = new Users(4, 2, new Users(4, 2));
        $this->assertIsArray($Users->patch(Action::UpdatePassword, array('password' => 'demodemodemo', 'current_password' => 'testPassword')));
    }

    public function testResetPassword(): void
    {
        $Users = new Users(4, 2, new Users(4, 2));
        $this->assertTrue($Users->resetPassword('demodemodemo'));
    }

    public function testInvalidColumn(): void
    {
        $this->expectException(ImproperActionException::class);
        $this->Users->update(new UserParams('invalid_column', 23));
    }

    public function testUpdatePasswordAsSysadmin(): void
    {
        $Users = new Users(4, 2, new Users(1, 1));
        $this->assertIsArray($Users->patch(Action::UpdatePassword, array('password' => 'demodemodemo')));
    }

    public function testTryToBecomeSysadmin(): void
    {
        $Users = new Users(4, 2, new Users(4, 2));
        $this->expectException(IllegalActionException::class);
        $Users->patch(Action::Update, array('is_sysadmin' => 1));
    }

    public function testInvalidateToken(): void
    {
        $this->assertTrue($this->Users->invalidateToken());
    }

    public function testValidate(): void
    {
        // current user is already validated but that's ok
        $this->assertIsArray($this->Users->patch(Action::Validate, array()));
    }

    public function testToggleArchive(): void
    {
        // tata in bravo
        $Admin = $this->getUserInTeam(team: 2, admin: 1);
        $user2 = $this->getUserInTeam(team: 2);
        $Users2Teams = new Users2Teams($Admin);
        $this->assertEquals(1, $Users2Teams->patchUser2Team(array(
            'target' => Users2TeamsTargets::IsArchived->value,
            'content' => '1',
            'team' => '2',
        ), $user2->userid));
    }

    public function testCreateUser(): void
    {
        // force admin validation so we can run all code paths
        $Config = Config::getConfig();
        $Config->patch(Action::Update, array('admin_validate' => 1));
        $this->assertIsInt($this->Users->createOne('blahblah@yop.fr', array('Bravo'), 'blah', 'yop', 'somePassword!', Usergroup::Admin, false, false));
        $Config->patch(Action::Update, array('admin_validate' => 0));
        $this->assertIsInt($this->Users->createOne('blahblah2@yop.fr', array('Bravo'), 'blah2', 'yop', 'somePassword!', Usergroup::Admin, true, false));
    }

    public function testArchiveWithoutPermission(): void
    {
        $Admin = $this->getUserInTeam(team: 2, admin: 1);
        $user2 = $this->getUserInTeam(team: 2);
        $Users = new Users($user2->userid, 2, $Admin);
        $Config = Config::getConfig();
        $Config->patch(Action::Update, array('admins_archive_users' => 0));
        $this->expectException(ImproperActionException::class);
        $Users->patch(Action::Archive, array());

        $Config->patch(Action::Update, array('admins_archive_users' => 1));
    }

    public function testReadAllActiveFromTeam(): void
    {
        $this->assertTrue(count($this->Users->readAllActiveFromTeam()) > 3);
    }

    public function testAddUserToTeam(): void
    {
        // add a user from team bravo into team alpha
        $user2 = $this->getUserInTeam(team: 2);
        $Users = new Users($user2->userid, 2, new Users(1, 1));
        $this->assertIsArray($Users->patch(Action::Add, array('team' => 1)));
        // try the reverse
        $Users = new Users(1, 1, new Users($user2->userid, 2));
        $this->expectException(IllegalActionException::class);
        $Users->patch(Action::Add, array('team' => 2));
    }

    public function testDestroy(): void
    {
        $Admin = $this->getUserInTeam(team: 2, admin: 1);
        $id = $Admin->createOne('testdestroy@a.fr', array('2'), 'Life', 'isShort', 'yololololol', Usergroup::User, false, false);
        $Target = new Users($id, 2, $Admin);
        $this->assertTrue($Target->destroy());
    }

    public function testDestroyWithExperiments(): void
    {
        $this->expectException(ImproperActionException::class);
        $this->Users->destroy();
    }
}
