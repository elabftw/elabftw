<?php

declare(strict_types=1);
/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2026 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

namespace Elabftw\Models;

use Elabftw\Elabftw\Db;
use Elabftw\Enums\Action;
use Elabftw\Enums\BodyContentType;
use Elabftw\Exceptions\AppException;
use Elabftw\Exceptions\ImproperActionException;
use Elabftw\Exceptions\ResourceNotFoundException;
use Elabftw\Models\Users\Users;
use Elabftw\Services\BodyRevisionsStore;
use Elabftw\Traits\TestsUtilsTrait;
use PDO;

use function array_column;
use function array_map;
use function array_reverse;
use function count;
use function sprintf;
use function str_repeat;

class BodyRevisionsTest extends \PHPUnit\Framework\TestCase
{
    use TestsUtilsTrait;

    private Users $Users;

    private Experiments $Experiments;

    private BodyRevisions $BodyRevisions;

    protected function setUp(): void
    {
        $this->Users = $this->getRandomUserInTeam(1);
        $this->Experiments = $this->getFreshExperimentWithGivenUser($this->Users);
        $this->BodyRevisions = new BodyRevisions($this->Experiments);
    }

    public function testEveryBodyIsRecorded(): void
    {
        $saved = array();
        for ($i = 0; $i < 5; $i++) {
            $saved[] = $this->saveBody(sprintf('<p>Weight of sample %d: %d mg</p>', $i, $i * 7));
        }
        $revisions = $this->BodyRevisions->readAll();
        $this->assertCount(5, $revisions);
        // newest first
        foreach (array_reverse($revisions) as $index => $revision) {
            $this->assertSame($saved[$index], $this->readBody($revision['id']));
            $this->assertSame($this->Users->userData['userid'], $revision['userid']);
        }
    }

    public function testSameBodyIsNotRecordedTwice(): void
    {
        $this->assertSame(0, $this->BodyRevisions->create(''));
        $first = $this->saveBody('<p>First version</p>');
        $this->saveBody('<p>First version</p>');
        $this->assertCount(1, $this->BodyRevisions->readAll());
        $this->saveBody('<p>Second version</p>');
        // going back to an older body is a change
        $this->saveBody('<p>First version</p>');
        $revisions = $this->BodyRevisions->readAll();
        $this->assertCount(3, $revisions);
        $this->assertSame($first, $this->readBody($revisions[0]['id']));
    }

    public function testKeyframes(): void
    {
        // the deltas must only cost the change
        $base = $this->getLargeBody();
        $saved = array();
        for ($i = 0; $i < BodyRevisionsStore::KEYFRAME_INTERVAL + 2; $i++) {
            $saved[] = $this->saveBody($base . sprintf('<p>Measurement %d: %d mg</p>', $i, $i * 13));
        }
        $revisions = array_reverse($this->BodyRevisions->readAll());
        $this->assertSame(array(0, 1, BodyRevisionsStore::KEYFRAME_INTERVAL - 1, 0, 1), array(
            $revisions[0]['depth'],
            $revisions[1]['depth'],
            $revisions[BodyRevisionsStore::KEYFRAME_INTERVAL - 1]['depth'],
            $revisions[BodyRevisionsStore::KEYFRAME_INTERVAL]['depth'],
            $revisions[BodyRevisionsStore::KEYFRAME_INTERVAL + 1]['depth'],
        ));
        $this->assertNull($revisions[BodyRevisionsStore::KEYFRAME_INTERVAL]['parent_id']);
        $this->assertSame($revisions[0]['id'], $revisions[1]['parent_id']);
        // the end of a chain needs all its parents
        foreach (array(1, BodyRevisionsStore::KEYFRAME_INTERVAL - 1, BodyRevisionsStore::KEYFRAME_INTERVAL, BodyRevisionsStore::KEYFRAME_INTERVAL + 1) as $index) {
            $this->assertSame($saved[$index], $this->readBody($revisions[$index]['id']));
        }
        $sizes = $this->readStoredSizes();
        $this->assertLessThan(100, $sizes[$revisions[1]['id']]);
        $this->assertGreaterThan($sizes[$revisions[1]['id']] * 5, $sizes[$revisions[0]['id']]);
    }

    public function testBodyChangedElsewhereIsRecorded(): void
    {
        $Experiments = new Experiments($this->Users);
        $Experiments->setId($Experiments->create(body: '<p>From the template</p>'));
        $BodyRevisions = new BodyRevisions($Experiments);
        $fromTemplate = $Experiments->entityData['body'];
        $this->assertCount(0, $BodyRevisions->readAll());

        $this->saveBody('<p>Edited</p>', $Experiments);
        $revisions = $BodyRevisions->readAll();
        $this->assertCount(2, $revisions);
        $this->assertSame($fromTemplate, $this->readBody($revisions[1]['id'], $Experiments));
        $this->assertNull($revisions[1]['parent_id']);

        // something writes the body without going through update()
        $req = Db::getConnection()->prepare('UPDATE experiments SET body = :body WHERE id = :id');
        $req->bindValue(':body', '<p>Changed by a migration</p>');
        $req->bindValue(':id', $Experiments->id, PDO::PARAM_INT);
        Db::getConnection()->execute($req);
        $Experiments->readOne();
        $this->saveBody('<p>Edited again</p>', $Experiments);
        $revisions = $BodyRevisions->readAll();
        $this->assertCount(4, $revisions);
        $this->assertSame('<p>Changed by a migration</p>', $this->readBody($revisions[1]['id'], $Experiments));
        $this->assertSame($revisions[1]['id'], $revisions[0]['parent_id']);
    }

    public function testConcurrentSaves(): void
    {
        // large enough for the deltas to depend on their dictionary
        $base = $this->getLargeBody();
        $this->saveBody($base . '<p>Common ancestor</p>');
        // both loaded the same body
        $Other = new Experiments($this->Users, $this->Experiments->id);
        $mine = $this->saveBody($base . '<p>Mine</p>');
        $theirs = $this->saveBody($base . '<p>Theirs</p>', $Other);
        // every revision stays readable, whatever their order
        $bodies = array_map(fn(array $revision): string => $this->readBody($revision['id']), $this->BodyRevisions->readAll());
        $this->assertSame($theirs, $bodies[0]);
        $this->assertContains($mine, $bodies);
    }

    public function testSeveralBodyUpdatesInOneRequest(): void
    {
        $this->saveBody('<p>Before</p>');
        $this->Experiments->patch(Action::Update, array('body' => '<p>Replaced</p>', 'bodyappend' => '<p>Appended</p>'));
        $revisions = $this->BodyRevisions->readAll();
        $this->assertCount(3, $revisions);
        $this->assertSame($this->Experiments->entityData['body'], $this->readBody($revisions[0]['id']));
    }

    public function testEmptyBody(): void
    {
        $this->saveBody('<p>Something</p>');
        $this->saveBody('');
        $revisions = $this->BodyRevisions->readAll();
        $this->assertCount(2, $revisions);
        $this->assertSame('', $this->readBody($revisions[0]['id']));
        // an empty body is a fine dictionary: the next one is a keyframe
        $this->saveBody('<p>Something else</p>');
        $this->assertNull($this->BodyRevisions->readAll()[0]['parent_id']);
    }

    public function testContentTypeChange(): void
    {
        $this->saveBody('plain text');
        $this->Experiments->patch(Action::Update, array('content_type' => (string) BodyContentType::Markdown->value, 'body' => 'plain text'));
        $revisions = $this->BodyRevisions->readAll();
        $this->assertCount(2, $revisions);
        $this->assertSame(BodyContentType::Markdown->value, $revisions[0]['content_type']);
    }

    public function testGetApiPath(): void
    {
        $this->assertSame(sprintf('api/v2/experiments/%d/revisions/', $this->Experiments->id), $this->BodyRevisions->getApiPath());
    }

    public function testReadOne(): void
    {
        $this->Experiments->patch(Action::Update, array('content_type' => (string) BodyContentType::Markdown->value, 'body' => '# Title'));
        $revision = new BodyRevisions($this->Experiments, $this->BodyRevisions->readAll()[0]['id'])->readOne();
        $this->assertSame($this->Experiments->id, $revision['item_id']);
        $this->assertSame('# Title', $revision['body']);
        $this->assertStringContainsString('<h1', $revision['body_html']);
    }

    public function testRestore(): void
    {
        $old = $this->saveBody('<p>Old</p>');
        $this->saveBody('<p>Current</p>');
        $oldId = $this->BodyRevisions->readAll()[1]['id'];
        new BodyRevisions($this->Experiments, $oldId)->patch(Action::Replace, array());
        $this->assertSame($old, $this->Experiments->readOne()['body']);
        // restoring is a change like any other
        $revisions = $this->BodyRevisions->readAll();
        $this->assertCount(3, $revisions);
        $this->assertSame($old, $this->readBody($revisions[0]['id']));
    }

    public function testRestoreLocked(): void
    {
        $this->saveBody('<p>Old</p>');
        $this->Experiments->patch(Action::Lock, array());
        $this->expectException(ImproperActionException::class);
        new BodyRevisions($this->Experiments, $this->BodyRevisions->readAll()[0]['id'])->patch(Action::Replace, array());
    }

    public function testCannotDelete(): void
    {
        $this->saveBody('<p>Forever</p>');
        $this->expectException(ImproperActionException::class);
        new BodyRevisions($this->Experiments, $this->BodyRevisions->readAll()[0]['id'])->destroy();
    }

    // what timestamping and signing do, so the history has the body they are about
    public function testRecordCurrentBody(): void
    {
        $Experiments = new Experiments($this->Users);
        $Experiments->setId($Experiments->create(body: '<p>Never saved through update()</p>'));
        $BodyRevisions = new BodyRevisions($Experiments);
        $this->assertSame(0, $BodyRevisions->create((string) $Experiments->entityData['body']));
        $revisions = $BodyRevisions->readAll();
        $this->assertCount(1, $revisions);
        $this->assertSame($Experiments->entityData['body'], $this->readBody($revisions[0]['id'], $Experiments));
        // already recorded
        $this->assertSame(0, $BodyRevisions->create((string) $Experiments->entityData['body']));
        $this->assertCount(1, $BodyRevisions->readAll());
    }

    public function testCorruptionIsDetected(): void
    {
        $this->saveBody('<p>One</p>');
        $this->saveBody('<p>Two</p>');
        $revisions = $this->BodyRevisions->readAll();
        $req = Db::getConnection()->prepare('UPDATE experiments_body_revisions SET body_sha256 = :hash WHERE id = :id');
        $req->bindValue(':hash', str_repeat('0', 64));
        $req->bindValue(':id', $revisions[0]['id'], PDO::PARAM_INT);
        Db::getConnection()->execute($req);
        $this->expectException(AppException::class);
        new BodyRevisions($this->Experiments, $revisions[0]['id'])->readOne();
    }

    public function testReadOneFromAnotherEntity(): void
    {
        $this->saveBody('<p>Mine</p>');
        $Other = $this->getFreshExperimentWithGivenUser($this->Users);
        $this->expectException(ResourceNotFoundException::class);
        new BodyRevisions($Other, $this->BodyRevisions->readAll()[0]['id'])->readOne();
    }

    public function testAllEntityTypes(): void
    {
        $Users = new Users(1, 1);
        $Items = $this->getFreshItemWithGivenUser($Users);
        $Templates = new Templates($Users);
        $Templates->setId($Templates->create(title: 'Body revisions'));
        $ItemsTypes = new ItemsTypes($Users);
        $ItemsTypes->setId($ItemsTypes->create(title: 'Body revisions'));
        foreach (array($Items, $Templates, $ItemsTypes) as $Entity) {
            $this->saveBody('<p>One</p>', $Entity);
            $last = $this->saveBody('<p>Two</p>', $Entity);
            $BodyRevisions = new BodyRevisions($Entity);
            $revisions = $BodyRevisions->readAll();
            $this->assertSame(2, count($revisions));
            $this->assertSame($last, $this->readBody($revisions[0]['id'], $Entity));
        }
    }

    private function getLargeBody(): string
    {
        $body = '';
        for ($i = 0; $i < 120; $i++) {
            $body .= sprintf('<p>Sample %d: centrifuge at %d rpm for %d minutes, then weigh the pellet.</p>', $i, 4000 + $i * 7, $i % 13);
        }
        return $body;
    }

    private function readBody(int $id, ?AbstractEntity $Entity = null): string
    {
        return new BodyRevisions($Entity ?? $this->Experiments, $id)->readOne()['body'];
    }

    // returns the body as it was stored, after filtering
    private function saveBody(string $body, ?AbstractEntity $Entity = null): string
    {
        $Entity ??= $this->Experiments;
        return (string) $Entity->patch(Action::Update, array('body' => $body))['body'];
    }

    /**
     * @return array<int, int> id => stored size of the compressed frame
     */
    private function readStoredSizes(): array
    {
        $req = Db::getConnection()->prepare('SELECT id, LENGTH(body_zstd) AS size FROM experiments_body_revisions WHERE entity_id = :id');
        $req->bindValue(':id', $this->Experiments->id, PDO::PARAM_INT);
        Db::getConnection()->execute($req);
        return array_column($req->fetchAll(), 'size', 'id');
    }
}
