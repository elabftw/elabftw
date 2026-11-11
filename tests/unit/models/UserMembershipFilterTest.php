<?php

declare(strict_types=1);

namespace Elabftw\Models;

use Elabftw\Elabftw\Db;
use Elabftw\Models\Users\Users;
use Elabftw\Traits\TestsUtilsTrait;
use PHPUnit\Framework\TestCase;

use function array_column;

final class UserMembershipFilterTest extends TestCase
{
    use TestsUtilsTrait;

    private Db $Db;

    private Users $Users;

    private int $userid;

    protected function setUp(): void
    {
        $this->Db = Db::getConnection();
        $this->Db->beginTransaction();
        $user = $this->getUserInTeam(1);
        $this->userid = $user->getUserid();
        $this->Users = new Users(1, 1);
        $delete = $this->Db->prepare('DELETE FROM users2teams WHERE users_id = :userid');
        $delete->execute(array('userid' => $this->userid));
        $insert = $this->Db->prepare('INSERT INTO users2teams (users_id, teams_id, is_admin, is_archived) VALUES (:userid, :team, :admin, :archived)');
        $insert->execute(array('userid' => $this->userid, 'team' => 1, 'admin' => 0, 'archived' => 1));
        $insert->execute(array('userid' => $this->userid, 'team' => 2, 'admin' => 1, 'archived' => 0));
    }

    protected function tearDown(): void
    {
        $this->Db->rollBack();
    }

    public function testActiveMembershipMustBeInRequestedTeam(): void
    {
        self::assertNotContains($this->userid, array_column($this->Users->readFromQuery(teamId: 1, onlyActive: true), 'userid'));
        self::assertContains($this->userid, array_column($this->Users->readFromQuery(teamId: 2, onlyActive: true), 'userid'));
    }

    public function testArchivedMembershipMustBeInRequestedTeam(): void
    {
        self::assertContains($this->userid, array_column($this->Users->readFromQuery(teamId: 1, onlyArchived: true), 'userid'));
        self::assertNotContains($this->userid, array_column($this->Users->readFromQuery(teamId: 2, onlyArchived: true), 'userid'));
    }

    public function testAdministratorMembershipMustBeInRequestedTeam(): void
    {
        self::assertNotContains($this->userid, array_column($this->Users->readFromQuery(teamId: 1, onlyAdmins: true), 'userid'));
        self::assertContains($this->userid, array_column($this->Users->readFromQuery(teamId: 2, onlyAdmins: true), 'userid'));
    }

    public function testGlobalSearchStillMatchesAnyMembership(): void
    {
        self::assertContains($this->userid, array_column($this->Users->readFromQuery(onlyActive: true), 'userid'));
        self::assertContains($this->userid, array_column($this->Users->readFromQuery(onlyArchived: true), 'userid'));
        self::assertContains($this->userid, array_column($this->Users->readFromQuery(onlyAdmins: true), 'userid'));
    }

    public function testScopedResultPreservesOtherMembershipDetails(): void
    {
        $users = array_column($this->Users->readFromQuery(teamId: 2, onlyAdmins: true, onlyActive: true), null, 'userid');
        $memberships = array_column($users[$this->userid]['teams'], null, 'id');
        self::assertArrayHasKey(1, $memberships);
        self::assertArrayHasKey(2, $memberships);
        self::assertSame(1, $memberships[1]['is_archived']);
        self::assertSame(0, $memberships[2]['is_archived']);
    }

    public function testReadAllExposesActiveFilterWithoutChangingDefaultResults(): void
    {
        $previous = $_GET;
        try {
            $_GET = array('onlyActive' => '1', 'team' => '1');
            self::assertNotContains($this->userid, array_column($this->Users->readAll(), 'userid'));
            $_GET['team'] = '2';
            self::assertContains($this->userid, array_column($this->Users->readAll(), 'userid'));
            unset($_GET['team']);
            self::assertContains($this->userid, array_column($this->Users->readAll(), 'userid'));
            $archive = $this->Db->prepare('UPDATE users2teams SET is_archived = 1 WHERE users_id = :userid');
            $archive->execute(array('userid' => $this->userid));
            self::assertNotContains($this->userid, array_column($this->Users->readAll(), 'userid'));
            $_GET = array();
            self::assertContains($this->userid, array_column($this->Users->readAll(), 'userid'));
        } finally {
            $_GET = $previous;
        }
    }
}
