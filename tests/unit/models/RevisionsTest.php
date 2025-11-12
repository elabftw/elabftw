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
use Elabftw\Exceptions\ImproperActionException;
use Elabftw\Models\Users\Users;
use Elabftw\Traits\TestsUtilsTrait;

class RevisionsTest extends \PHPUnit\Framework\TestCase
{
    use TestsUtilsTrait;

    private const int MAX_REVISIONS = 10;

    private Users $Users;

    private Experiments $Experiments;

    private Revisions $Revisions;

    protected function setUp(): void
    {
        $this->Users = $this->getRandomUserInTeam(1);
        $this->Experiments = $this->getFreshExperimentWithGivenUser($this->Users);
        $this->Revisions = new Revisions($this->Experiments, self::MAX_REVISIONS, 1, 10);
    }

    public function testGetApiPath(): void
    {
        $this->assertSame(sprintf('api/v2/experiments/%d/revisions/', $this->Experiments->id), $this->Revisions->getApiPath());
    }

    public function testCreate(): void
    {
        $this->assertIsInt($this->Revisions->create('Ohaiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiii'));
    }

    public function testReadAll(): void
    {
        $this->assertIsArray($this->Revisions->readAll());
    }

    public function testRestore(): void
    {
        $id = $this->Revisions->create('Ohai');
        $this->Revisions->setId($id);
        $this->assertIsArray($this->Revisions->patch(Action::Replace, array()));
    }

    public function testRestoreLocked(): void
    {
        $id = $this->Revisions->create('Ohai');
        $this->Revisions->setId($id);
        $this->Experiments->patch(Action::Lock, array());
        $this->expectException(ImproperActionException::class);
        $this->Revisions->patch(Action::Replace, array());
    }

    // create a bunch of revisions and ensure we don't have more than max number
    public function testMaxNumber(): void
    {
        for ($i = 0; $i < 12; $i++) {
            $this->Revisions->create('wéééééééééé' . $i);
        }
        $this->assertLessThanOrEqual(self::MAX_REVISIONS, count($this->Revisions->readAll()));
    }

    public function testDestroy(): void
    {
        $this->expectException(ImproperActionException::class);
        $this->Revisions->destroy();
    }
        public function testCreateWithSufficientDelta(): void
    {
        // Create initial revision with known content
        $initialBody = str_repeat('a', 200); // 200 chars
        $firstRevId = $this->Revisions->create($initialBody);
        $this->assertGreaterThan(0, $firstRevId);
        
        // Create revision with large enough delta (> 100 chars difference)
        $newBody = str_repeat('b', 350); // 350 chars - delta of 150
        $secondRevId = $this->Revisions->create($newBody);
        
        // Should create a NEW revision, not overwrite
        $this->assertGreaterThan($firstRevId, $secondRevId);
        $this->assertNotEquals($firstRevId, $secondRevId);
        
        // Verify we have 2 revisions
        $allRevisions = $this->Revisions->readAll();
        $this->assertCount(2, $allRevisions);
    }

    public function testCreateWithoutSufficientDeltaOverwrites(): void
    {
        // Create initial revision
        $initialBody = str_repeat('a', 200);
        $firstRevId = $this->Revisions->create($initialBody);
        $this->assertGreaterThan(0, $firstRevId);
        
        // Try to create revision with small delta (< 100 chars, and < 10 days)
        $newBody = str_repeat('a', 220); // Only 20 chars difference
        $secondRevId = $this->Revisions->create($newBody);
        
        // Should OVERWRITE the same revision
        $this->assertEquals($firstRevId, $secondRevId);
        
        // Verify we still have only 1 revision
        $allRevisions = $this->Revisions->readAll();
        $this->assertCount(1, $allRevisions);
    }

    public function testMaxRevisionsEnforcement(): void
    {
        $this->Revisions = new Revisions($this->Experiments, 3, 100, 10); // max 3 revisions
        
        // Create 5 revisions with sufficient delta
        $revisionIds = [];
        for ($i = 0; $i < 5; $i++) {
            $body = str_repeat('x', 200 + ($i * 150)); // Each has > 100 char delta
            $revisionIds[] = $this->Revisions->create($body);
        }
        
        // Should only have 3 revisions (oldest 2 should be deleted)
        $allRevisions = $this->Revisions->readAll();
        $this->assertCount(3, $allRevisions);
        
        // Verify the latest 3 revisions are kept
        $existingIds = array_column($allRevisions, 'id');
        $this->assertContains($revisionIds[4], $existingIds);
        $this->assertContains($revisionIds[3], $existingIds);
        $this->assertContains($revisionIds[2], $existingIds);
        $this->assertNotContains($revisionIds[0], $existingIds);
        $this->assertNotContains($revisionIds[1], $existingIds);
    }

    public function testEntityDataUpdatedAfterNewRevision(): void
    {
        $newBody = str_repeat('test', 100);
        $beforeTime = (new \DateTimeImmutable())->format('Y-m-d H:i:s');
        
        $revId = $this->Revisions->create($newBody);
        $this->assertGreaterThan(0, $revId);
        
        // Re-read entity to verify database was updated
        $this->Experiments->setId($this->Experiments->id);
        $freshData = $this->Experiments->readOne();
        
        // Entity data should be updated in database
        $this->assertEquals($newBody, $freshData['body']);
        $this->assertGreaterThanOrEqual($beforeTime, $freshData['modified_at']);
    }

    public function testCreateWithEmptyBody(): void
    {
        $result = $this->Revisions->create('');
        $this->assertEquals(0, $result);
        
        // Should not create any revision
        $allRevisions = $this->Revisions->readAll();
        $this->assertCount(0, $allRevisions);
    }

    public function testOverwriteUpdatesContentType(): void
    {
        // Create initial revision
        $initialBody = str_repeat('a', 200);
        $firstRevId = $this->Revisions->create($initialBody);
        
        // Overwrite with small change
        $newBody = str_repeat('a', 210);
        $secondRevId = $this->Revisions->create($newBody);
        
        // Should be same revision ID
        $this->assertEquals($firstRevId, $secondRevId);
        
        // Verify the revision was actually updated
        $this->Revisions->setId($secondRevId);
        $revision = $this->Revisions->readOne();
        $this->assertEquals($newBody, $revision['body']);
    }
    
    public function testCreateWithSatisfiedTimeConstraint(): void
    {
        // Use minDays = 0 for easier testing, or a small value
        $this->Revisions = new Revisions($this->Experiments, 10, 100, 0);
        
        // Create initial revision
        $initialBody = str_repeat('a', 200);
        $firstRevId = $this->Revisions->create($initialBody);
        $this->assertGreaterThan(0, $firstRevId);
        
        // Mock modified_at to be in the past (simulate 1 day ago)
        $pastTime = (new \DateTimeImmutable())->modify('-1 day')->format('Y-m-d H:i:s');
        $this->Experiments->Db->prepare(
            'UPDATE experiments SET modified_at = :modified_at WHERE id = :id'
        )->execute([
            'modified_at' => $pastTime,
            'id' => $this->Experiments->id,
        ]);
        
        // Create revision with small delta (< 100 chars) but time constraint satisfied
        $newBody = str_repeat('a', 210); // Only 10 chars difference
        $secondRevId = $this->Revisions->create($newBody);
        
        // Should create NEW revision due to time constraint being satisfied
        $this->assertNotEquals($firstRevId, $secondRevId);
        $this->assertGreaterThan($firstRevId, $secondRevId);
        $this->assertCount(2, $this->Revisions->readAll());
    }

    public function testCreateWithBothConstraintsSatisfied(): void
    {
        $this->Revisions = new Revisions($this->Experiments, 10, 100, 0);
        
        // Create initial revision
        $initialBody = str_repeat('a', 200);
        $firstRevId = $this->Revisions->create($initialBody);
        
        // Mock modified_at to be in the past
        $pastTime = (new \DateTimeImmutable())->modify('-1 day')->format('Y-m-d H:i:s');
        $this->Experiments->Db->prepare(
            'UPDATE experiments SET modified_at = :modified_at WHERE id = :id'
        )->execute([
            'modified_at' => $pastTime,
            'id' => $this->Experiments->id,
        ]);
        
        // Create revision with both large delta AND time satisfied
        $newBody = str_repeat('b', 350); // 150 chars difference
        $secondRevId = $this->Revisions->create($newBody);
        
        // Should create NEW revision
        $this->assertNotEquals($firstRevId, $secondRevId);
        $this->assertCount(2, $this->Revisions->readAll());
    }

    public function testCreateWithNeitherConstraintSatisfied(): void
    {
        // This is similar to your existing testCreateWithoutSufficientDeltaOverwrites
        // but explicitly tests when BOTH constraints are NOT met
        $this->Revisions = new Revisions($this->Experiments, 10, 100, 999); // High minDays
        
        $initialBody = str_repeat('a', 200);
        $firstRevId = $this->Revisions->create($initialBody);
        
        // Small delta and not enough time passed
        $newBody = str_repeat('a', 210);
        $secondRevId = $this->Revisions->create($newBody);
        
        // Should OVERWRITE the same revision
        $this->assertEquals($firstRevId, $secondRevId);
        $this->assertCount(1, $this->Revisions->readAll());
    }    
}
