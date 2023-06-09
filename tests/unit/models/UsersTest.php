<?php declare(strict_types=1);
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
use Elabftw\Exceptions\ImproperActionException;
use Elabftw\Exceptions\ResourceNotFoundException;

class UsersTest extends \PHPUnit\Framework\TestCase
{
    private Users $Users;

    protected function setUp(): void
    {
        $requester = new Users(1, 1);
        $this->Users= new Users(1, 1, $requester);
    }

    public function testPopulate(): void
    {
        $this->assertTrue(is_array($this->Users->userData));
        $this->expectException(ResourceNotFoundException::class);
        new Users(1337);
    }

    public function testAllowUntrustedLogin(): void
    {
        $this->assertFalse($this->Users->allowUntrustedLogin());
        $this->assertTrue((new Users(2, 1))->allowUntrustedLogin());
    }

    public function testReadAllFromTeam(): void
    {
        $this->assertIsArray($this->Users->readAllFromTeam());
    }

    public function testUpdateAccount(): void
    {
        $params = array(
            'email' => 'tatabis@yopmail.com',
            'firstname' => 'Tata',
            'lastname' => 'Yep',
            'orcid' => '0000-0002-7494-5555',
            'password' => 'new super password',
        );
        $result = (new Users(4, 2, new Users(4, 2)))->patch(Action::Update, $params);
        $this->assertEquals('tatabis@yopmail.com', $result['email']);
        $this->assertEquals('Yep', $result['lastname']);
    }

    public function testUpdateWrongOrcid(): void
    {
        $this->expectException(ImproperActionException::class);
        (new Users(4, 2, new Users(4, 2)))->patch(Action::Update, array('orcid' => 'blah'));
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
            'show_team' => 'on',
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

    public function testReadAll(): void
    {
        $this->assertIsArray($this->Users->readAll());
    }

    public function testIsAdminOf(): void
    {
        $this->assertTrue($this->Users->isAdminOf(1));
        $this->assertTrue($this->Users->isAdminOf(2));
        $this->assertFalse($this->Users->isAdminOf(4));
        $tata = new Users(4, 2);
        $this->assertFalse($tata->isAdminOf(2));
    }

    public function testGetPage(): void
    {
        $this->assertEquals('api/v2/users/', $this->Users->getPage());
    }

    public function testUpdateTooShortPassword(): void
    {
        $Users = new Users(4, 2, new Users(4, 2));
        $this->expectException(ImproperActionException::class);
        $Users->patch(Action::Update, array('password' => 'short'));
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
        $Admin = new Users(4, 2);
        $Users = new Users(5, 2, $Admin);
        $this->assertIsArray($Users->patch(Action::Lock, array()));
    }

    public function testCreateUser(): void
    {
        // force admin validation so we can run all code paths
        $Config = Config::getConfig();
        $Config->patch(Action::Update, array('admin_validate' => 1));
        $this->assertIsInt($this->Users->createOne('blahblah@yop.fr', array('Bravo'), 'blah', 'yop', 'somePassword!', 2, false, false));
        $Config->patch(Action::Update, array('admin_validate' => 0));
        $this->assertIsInt($this->Users->createOne('blahblah2@yop.fr', array('Bravo'), 'blah2', 'yop', 'somePassword!', 2, true, false));
    }

    public function testUnArchiveButAnotherUserExists(): void
    {
        // this user is archived already
        $Admin = new Users(4, 2);
        $Users = new Users(5, 2, $Admin);
        // create another active user with the same email
        ExistingUser::fromScratch($Users->userData['email'], array('Alpha'), 'f', 'l', 4, false, false);
        // try to unarchive
        $this->expectException(ImproperActionException::class);
        $Users->patch(Action::Lock, array());
    }

    public function testReadAllActiveFromTeam(): void
    {
        $this->assertCount(6, $this->Users->readAllActiveFromTeam());
    }

    public function testDestroy(): void
    {
        $Admin = new Users(4, 2);
        $id = $Admin->createOne('testdestroy@a.fr', array('Bravo'), 'Life', 'isShort', 'yololololol', 4, false, false);
        $Target = new Users($id, 2, $Admin);
        $this->assertTrue($Target->destroy());
    }

    public function testDestroyWithExperiments(): void
    {
        $this->expectException(ImproperActionException::class);
        $this->Users->destroy();
    }
}
