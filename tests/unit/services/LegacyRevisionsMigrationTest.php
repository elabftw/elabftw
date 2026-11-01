<?php

declare(strict_types=1);
/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2026 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

namespace Elabftw\Services;

use Elabftw\Elabftw\Db;
use Elabftw\Elabftw\Migrations;
use Elabftw\Enums\Action;
use Elabftw\Enums\EntityType;
use Elabftw\Exceptions\ImproperActionException;
use Elabftw\Models\BodyRevisions;
use Elabftw\Models\Experiments;
use Elabftw\Models\Users\Users;
use Elabftw\Traits\TestsUtilsTrait;
use PDO;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\Console\Output\NullOutput;

use function array_column;
use function array_reverse;
use function count;
use function date;
use function hash;
use function sprintf;
use function str_repeat;
use function strtotime;

class LegacyRevisionsMigrationTest extends \PHPUnit\Framework\TestCase
{
    use TestsUtilsTrait;

    private Db $Db;

    private Experiments $Experiments;

    private BufferedOutput $Output;

    protected function setUp(): void
    {
        $this->Db = Db::getConnection();
        // like the tables of the previous release, without foreign keys so an orphan can exist
        foreach (EntityType::cases() as $entityType) {
            $this->Db->q(sprintf('CREATE TABLE IF NOT EXISTS %s_revisions (
                id int unsigned NOT NULL AUTO_INCREMENT,
                item_id int unsigned NOT NULL,
                body mediumtext NOT NULL,
                content_type tinyint NOT NULL DEFAULT 1,
                created_at timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
                modified_at timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                userid int unsigned NOT NULL,
                PRIMARY KEY (id),
                KEY item_id (item_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE utf8mb4_0900_ai_ci', $entityType->value));
        }
        $this->Experiments = $this->getFreshExperimentWithGivenUser(new Users(1, 1));
        $this->Output = new BufferedOutput();
    }

    protected function tearDown(): void
    {
        foreach (EntityType::cases() as $entityType) {
            $this->Db->q(sprintf('DROP TABLE IF EXISTS %s_revisions', $entityType->value));
        }
    }

    public function testIsTheDataStepOfItsMigration(): void
    {
        $this->assertInstanceOf(LegacyRevisionsMigration::class, Migrations::getDataStep(LegacyRevisionsMigration::MIGRATION, new NullOutput()));
        $this->assertNull(Migrations::getDataStep(Migrations::generateUuidV7(), new NullOutput()));
    }

    public function testUp(): void
    {
        // longer than a chain, from two users, with characters outside of ascii
        $legacy = array();
        $text = '';
        for ($i = 0; $i < BodyRevisionsStore::KEYFRAME_INTERVAL + 10; $i++) {
            $text .= sprintf('<p>Mesure %d : %d mg — échantillon «%s» ✓</p>', $i, $i * 3, str_repeat('é', $i % 5));
            $legacy[] = $this->insertLegacy(EntityType::Experiments, $this->Experiments->id ?? 0, $text, $this->getDate($i), 1 + $i % 2);
        }
        $Items = $this->getFreshItemWithGivenUser(new Users(1, 1));
        $this->insertLegacy(EntityType::Items, $Items->id ?? 0, '# Markdown', $this->getDate(1), 1, 2);
        // its entry does not exist anymore
        $this->insertLegacy(EntityType::Experiments, 999999999, '<p>orphan</p>', $this->getDate(1), 1);

        new LegacyRevisionsMigration($this->Output)->up();

        $revisions = array_reverse(new BodyRevisions($this->Experiments)->readAll());
        $this->assertCount(count($legacy), $revisions);
        foreach ($revisions as $index => $revision) {
            $this->assertSame($legacy[$index]['created_at'], $revision['created_at']);
            $this->assertSame($legacy[$index]['userid'], $revision['userid']);
            $this->assertSame($legacy[$index]['body'], new BodyRevisions($this->Experiments, $revision['id'])->readOne()['body']);
        }
        $this->assertSame(array(0, 1, 0), array($revisions[0]['depth'], $revisions[1]['depth'], $revisions[BodyRevisionsStore::KEYFRAME_INTERVAL]['depth']));
        $itemRevision = new BodyRevisions($Items, new BodyRevisions($Items)->readAll()[0]['id'])->readOne();
        $this->assertSame(array('# Markdown', 2), array($itemRevision['body'], $itemRevision['content_type']));
        $this->assertStringContainsString('1 of entries that do not exist anymore left out', $this->Output->fetch());

        // safe to run again
        new LegacyRevisionsMigration($this->Output)->up();
        $this->assertCount(count($legacy), new BodyRevisions($this->Experiments)->readAll());
        $this->assertStringContainsString(sprintf('experiments_revisions: 0 revisions imported, %d already recorded', count($legacy)), $this->Output->fetch());
    }

    // the previous release recorded each save in both tables
    public function testUpSkipsWhatIsAlreadyRecorded(): void
    {
        $saved = (string) $this->Experiments->patch(Action::Update, array('body' => '<p>Saved with the previous release</p>'))['body'];
        $recordedAt = (string) new BodyRevisions($this->Experiments)->readAll()[0]['created_at'];
        $this->insertLegacy(EntityType::Experiments, $this->Experiments->id ?? 0, '<p>Older</p>', $this->getDate(0), 1);
        $this->insertLegacy(EntityType::Experiments, $this->Experiments->id ?? 0, $saved, date('Y-m-d H:i:s', (int) strtotime($recordedAt) + 1), 1);

        new LegacyRevisionsMigration($this->Output)->up();

        $revisions = new BodyRevisions($this->Experiments)->readAll();
        $this->assertCount(2, $revisions);
        $this->assertSame($this->getDate(0), $revisions[1]['created_at']);
    }

    public function testDown(): void
    {
        $this->Experiments->patch(Action::Update, array('body' => '<p>One</p>'));
        $this->Experiments->patch(Action::Update, array('body' => '<p>Two</p>'));
        $expected = $this->readFingerprints('experiments_body_revisions', 'entity_id', 'body_zstd', $this->Experiments->id ?? 0);

        new LegacyRevisionsMigration($this->Output)->down();

        $copied = $this->readFingerprints('experiments_revisions', 'item_id', 'body', $this->Experiments->id ?? 0);
        $this->assertCount(2, $copied);
        $this->assertSame(array_column($expected, 'created_at'), array_column($copied, 'created_at'));
        $this->assertSame(array(hash('sha256', '<p>One</p>'), hash('sha256', '<p>Two</p>')), array_column($copied, 'sha256'));

        // safe to run again
        new LegacyRevisionsMigration($this->Output)->down();
        $this->assertCount(2, $this->readFingerprints('experiments_revisions', 'item_id', 'body', $this->Experiments->id ?? 0));
    }

    public function testDownCopiesWhatIsNotCorrupted(): void
    {
        foreach (array('<p>One</p>', '<p>Two</p>', '<p>Three</p>') as $body) {
            $this->Experiments->patch(Action::Update, array('body' => $body));
        }
        $ids = array_column(array_reverse(new BodyRevisions($this->Experiments)->readAll()), 'id');
        $req = $this->Db->prepare('UPDATE experiments_body_revisions SET body_sha256 = :sha256 WHERE id = :id');
        $req->bindValue(':sha256', str_repeat('0', 64));
        $req->bindValue(':id', $ids[1], PDO::PARAM_INT);
        $this->Db->execute($req);

        new LegacyRevisionsMigration($this->Output)->down();

        // the third one needs the second one to be rebuilt
        $copied = $this->readFingerprints('experiments_revisions', 'item_id', 'body', $this->Experiments->id ?? 0);
        $this->assertSame(array(hash('sha256', '<p>One</p>')), array_column($copied, 'sha256'));
        $this->assertStringContainsString(sprintf('%d, %d', $ids[1], $ids[2]), $this->Output->fetch());
    }

    public function testDownNeedsTheLegacyTables(): void
    {
        $this->Db->q('DROP TABLE experiments_revisions');
        $this->expectException(ImproperActionException::class);
        new LegacyRevisionsMigration($this->Output)->down();
    }

    /**
     * @return array{body: string, created_at: string, userid: int}
     */
    private function insertLegacy(EntityType $entityType, int $entityId, string $body, string $createdAt, int $userid, int $contentType = 1): array
    {
        $req = $this->Db->prepare(sprintf(
            'INSERT INTO %s_revisions (item_id, body, content_type, created_at, modified_at, userid) VALUES (:item_id, :body, :content_type, :created_at, :modified_at, :userid)',
            $entityType->value,
        ));
        $req->bindValue(':item_id', $entityId, PDO::PARAM_INT);
        $req->bindValue(':body', $body);
        $req->bindValue(':content_type', $contentType, PDO::PARAM_INT);
        $req->bindValue(':created_at', $createdAt);
        $req->bindValue(':modified_at', $createdAt);
        $req->bindValue(':userid', $userid, PDO::PARAM_INT);
        $this->Db->execute($req);
        return array('body' => $body, 'created_at' => $createdAt, 'userid' => $userid);
    }

    private function getDate(int $hours): string
    {
        return date('Y-m-d H:i:s', (int) strtotime('2024-03-01 10:00:00') + $hours * 3600);
    }

    private function readFingerprints(string $table, string $entityColumn, string $bodyColumn, int $entityId): array
    {
        $sha = $bodyColumn === 'body' ? 'SHA2(body, 256) AS sha256, ' : '';
        $req = $this->Db->prepare(sprintf('SELECT %s created_at FROM %s WHERE %s = :id ORDER BY created_at, id', $sha, $table, $entityColumn));
        $req->bindValue(':id', $entityId, PDO::PARAM_INT);
        $this->Db->execute($req);
        return $req->fetchAll();
    }
}
