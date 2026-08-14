<?php

declare(strict_types=1);

namespace Elabftw\Tests\Services;

use Elabftw\Elabftw\Db;
use Elabftw\Enums\EntityType;
use Elabftw\Exceptions\ImproperActionException;
use Elabftw\Services\EntityPruner;
use Elabftw\Traits\TestsUtilsTrait;
use PHPUnit\Framework\TestCase;

use function date;
use function strtotime;

class EntityPrunerTest extends TestCase
{
    use TestsUtilsTrait;

    public function testCleanupIdFilter(): void
    {
        $Exp1 = $this->getFreshExperiment();
        $id1 = $Exp1->id;
        $Exp1->destroy();

        $Exp2 = $this->getFreshExperiment();
        $id2 = $Exp2->id;
        $Exp2->destroy();

        // Prune only $id1
        $Cleaner = new EntityPruner(EntityType::Experiments, array($id1));
        $res = $Cleaner->cleanup();

        $this->assertEquals(1, $res);

        // Verify Exp1 is gone
        $req = Db::getConnection()->prepare('SELECT id FROM experiments WHERE id = :id');
        $req->execute(array(':id' => $id1));
        $this->assertEmpty($req->fetchAll());

        // Verify Exp2 is still there (soft deleted)
        $req->execute(array(':id' => $id2));
        $this->assertNotEmpty($req->fetchAll());
    }

    public function testCleanupTeamFilter(): void
    {
        $Item = $this->getFreshItem(1);
        $id = $Item->id;
        $Item->destroy();

        // Matching ID but wrong team must not prune
        $CleanerWrongTeam = new EntityPruner(EntityType::Items, array($id), array(), array(999));
        $res = $CleanerWrongTeam->cleanup();
        $this->assertSame(0, $res);

        // Matching ID and correct team must prune
        $CleanerMatch = new EntityPruner(EntityType::Items, array($id), array(), array(1));
        $res = $CleanerMatch->cleanup();
        $this->assertSame(1, $res);

        $req = Db::getConnection()->prepare('SELECT id FROM items WHERE id = :id');
        $req->execute(array(':id' => $id));
        $this->assertEmpty($req->fetchAll());
    }

    public function testCleanupSinceFilter(): void
    {
        $Exp = $this->getFreshExperiment();
        $id = $Exp->id;
        $Exp->destroy();

        // since far in future -> nothing pruned
        $future = date('Y-m-d H:i:s', strtotime('+1 year'));
        $Cleaner = new EntityPruner(EntityType::Experiments, array($id), array(), array(), $future);
        $res = $Cleaner->cleanup();
        $this->assertEquals(0, $res);

        // since in past -> pruned
        $past = date('Y-m-d H:i:s', strtotime('-1 day'));
        $Cleaner = new EntityPruner(EntityType::Experiments, array($id), array(), array(), $past);
        $res = $Cleaner->cleanup();
        $this->assertEquals(1, $res);
    }

    public function testCleanupUserFilter(): void
    {
        $Exp = $this->getFreshExperiment();
        $id = $Exp->id;
        $userid = $Exp->Users->userid;
        $Exp->destroy();

        // Matching ID but wrong user must not prune
        $CleanerWrongUser = new EntityPruner(EntityType::Experiments, array($id), array(999));
        $res = $CleanerWrongUser->cleanup();
        $this->assertSame(0, $res);

        // Matching ID and correct user must prune
        $CleanerMatch = new EntityPruner(EntityType::Experiments, array($id), array($userid));
        $res = $CleanerMatch->cleanup();
        $this->assertSame(1, $res);

        $req = Db::getConnection()->prepare('SELECT id FROM experiments WHERE id = :id');
        $req->execute(array(':id' => $id));
        $this->assertEmpty($req->fetchAll());
    }

    public function testCleanupCombinedFilters(): void
    {
        // AND-combined: --user + --team
        $Item = $this->getFreshItem(1);
        $id = $Item->id;
        $userid = $Item->Users->userid;
        $Item->destroy();

        // Prune with matching user but WRONG team
        $CleanerWrongTeam = new EntityPruner(EntityType::Items, array($id), array($userid), array(999));
        $res = $CleanerWrongTeam->cleanup();
        $this->assertSame(0, $res, 'Should not prune because team does not match (AND logic)');

        // Prune with matching team but WRONG user
        $CleanerWrongUser = new EntityPruner(EntityType::Items, array($id), array(999), array(1));
        $res = $CleanerWrongUser->cleanup();
        $this->assertSame(0, $res, 'Should not prune because user does not match (AND logic)');

        // Prune with matching both
        $CleanerMatch = new EntityPruner(EntityType::Items, array($id), array($userid), array(1));
        $res = $CleanerMatch->cleanup();
        $this->assertSame(1, $res, 'Should prune because both filters match');
    }

    public function testCleanupInvalidAbsoluteDateThrows(): void
    {
        $this->expectException(ImproperActionException::class);
        $Cleaner = new EntityPruner(EntityType::Experiments, array(), array(), array(), '2026-02-30');
        $Cleaner->cleanup();
    }
}
