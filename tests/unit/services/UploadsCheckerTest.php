<?php

declare(strict_types=1);
/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2023 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

namespace Elabftw\Services;

use Elabftw\Elabftw\CreateUploadFromLocalFile;
use Elabftw\Elabftw\Db;
use Elabftw\Enums\Storage;
use Elabftw\Models\Items;
use Elabftw\Traits\TestsUtilsTrait;
use PDO;
use Symfony\Component\Console\Output\BufferedOutput;

use function array_column;
use function dirname;
use function sprintf;
use function str_repeat;
use function array_map;

final class UploadsCheckerTest extends \PHPUnit\Framework\TestCase
{
    use TestsUtilsTrait;

    private Db $Db;

    private Items $Entity;

    private BufferedOutput $output;

    private UploadsChecker $UploadsChecker;

    /** @var list<string> */
    private array $createdLongNames = array();

    protected function setUp(): void
    {
        $this->Db = Db::getConnection();
        $this->Db->beginTransaction();
        $this->Entity = $this->getFreshItem();
        $this->output = new BufferedOutput();
        $this->UploadsChecker = new UploadsChecker($this->output);
    }

    protected function tearDown(): void
    {
        try {
            $fs = Storage::LOCAL->getStorage()->getFs();
            foreach ($this->createdLongNames as $longName) {
                if ($fs->fileExists($longName)) {
                    $fs->delete($longName);
                }
            }
        } finally {
            $this->Db->rollBack();
        }
    }

    public function testFixMethodsReturnZeroWhenNothingNeedsFixing(): void
    {
        $this->assertSame(0, $this->UploadsChecker->fixNullFilesize());
        $this->assertSame(0, $this->UploadsChecker->fixNullHash());
    }

    public function testGetStats(): void
    {
        $before = UploadsChecker::getStats();
        $upload = $this->createUpload();
        $afterLocalUpload = UploadsChecker::getStats();

        $this->assertSame((int) $before['count_all'] + 1, (int) $afterLocalUpload['count_all']);
        $this->assertSame((int) $before['filesize_all'] + (int) $upload['filesize'], (int) $afterLocalUpload['filesize_all']);
        $this->assertSame((int) $before['count_storage_local'] + 1, (int) $afterLocalUpload['count_storage_local']);
        $this->assertSame((int) $before['count_storage_s3'], (int) $afterLocalUpload['count_storage_s3']);

        $this->setStorage((int) $upload['id'], Storage::S3);
        $afterS3Switch = UploadsChecker::getStats();

        $this->assertSame((int) $before['count_storage_local'], (int) $afterS3Switch['count_storage_local']);
        $this->assertSame((int) $before['count_storage_s3'] + 1, (int) $afterS3Switch['count_storage_s3']);

        $this->setNullHashAndFilesize((int) $upload['id']);
        $afterNulling = UploadsChecker::getStats();

        $this->assertSame((int) $before['count_null_hash'] + 1, (int) $afterNulling['count_null_hash']);
        $this->assertSame((int) $before['count_null_filesize'] + 1, (int) $afterNulling['count_null_filesize']);
        $this->assertSame((int) $before['filesize_all'], (int) $afterNulling['filesize_all']);
    }

    public function testGetNullColumn(): void
    {
        $filesizeUpload = $this->createUpload();
        $hashUpload = $this->createUpload();
        $this->setNullFilesize((int) $filesizeUpload['id']);
        $this->setHash((int) $hashUpload['id'], null, null);

        $nullFilesizes = array_map('intval', array_column($this->UploadsChecker->getNullColumn('filesize'), 'id'));
        $nullHashes = array_map('intval', array_column($this->UploadsChecker->getNullColumn('hash'), 'id'));

        $this->assertContains((int) $filesizeUpload['id'], $nullFilesizes);
        $this->assertNotContains((int) $hashUpload['id'], $nullFilesizes);
        $this->assertContains((int) $hashUpload['id'], $nullHashes);
        $this->assertNotContains((int) $filesizeUpload['id'], $nullHashes);
    }

    public function testFixNullFilesize(): void
    {
        $upload = $this->createUpload();
        $expectedFilesize = (int) $upload['filesize'];
        $this->setNullFilesize((int) $upload['id']);

        $this->assertSame(1, $this->UploadsChecker->fixNullFilesize());

        $fresh = $this->fetchUpload((int) $upload['id']);
        $this->assertSame($expectedFilesize, (int) $fresh['filesize']);
        $this->assertSame('', $this->output->fetch());
    }

    public function testFixNullFilesizeReportsMetadataErrorAndContinues(): void
    {
        $upload = $this->createUpload();
        $missingLongName = sprintf('missing/%d.zip', $upload['id']);
        $this->setMissingFileWithNullFilesize((int) $upload['id'], $missingLongName);

        $this->assertSame(0, $this->UploadsChecker->fixNullFilesize());

        $fresh = $this->fetchUpload((int) $upload['id']);
        $this->assertNull($fresh['filesize']);
        $this->assertStringContainsString('Error:', $this->output->fetch());
    }

    public function testFixNullHash(): void
    {
        $upload = $this->createUpload();
        $expectedHash = $upload['hash'];
        $expectedAlgorithm = $upload['hash_algorithm'];
        $this->setHash((int) $upload['id'], null, null);

        $this->assertSame(1, $this->UploadsChecker->fixNullHash());

        $fresh = $this->fetchUpload((int) $upload['id']);
        $this->assertSame($expectedHash, $fresh['hash']);
        $this->assertSame($expectedAlgorithm, $fresh['hash_algorithm']);
    }

    public function testRecomputeHashDoesNothingForMatchingHash(): void
    {
        $upload = $this->createUpload();

        $count = $this->UploadsChecker->recomputeHash(true);

        $this->assertSame((int) UploadsChecker::getStats()['count_all'], $count);
        $this->assertStringNotContainsString(
            sprintf('Found hash mismatch for upload id: %d', $upload['id']),
            $this->output->fetch(),
        );
    }

    public function testRecomputeHashDryRunDoesNotReplaceMismatch(): void
    {
        $upload = $this->createUpload();
        $wrongHash = str_repeat('0', 64);
        $this->setHash((int) $upload['id'], $wrongHash, null);

        $count = $this->UploadsChecker->recomputeHash(true);

        $this->assertSame((int) UploadsChecker::getStats()['count_all'], $count);
        $fresh = $this->fetchUpload((int) $upload['id']);
        $this->assertSame($wrongHash, $fresh['hash']);
        $this->assertNull($fresh['hash_algorithm']);

        $output = $this->output->fetch();
        $this->assertStringContainsString(
            sprintf('Found hash mismatch for upload id: %d', $upload['id']),
            $output,
        );
        $this->assertStringContainsString(
            sprintf('Expected: %s but calculated: %s', $wrongHash, $upload['hash']),
            $output,
        );
        $this->assertStringContainsString(
            'Not replacing faulty hash in database because dry-run mode enabled.',
            $output,
        );
    }

    public function testRecomputeHashReplacesMismatch(): void
    {
        $upload = $this->createUpload();
        $wrongHash = str_repeat('0', 64);
        $this->setHash((int) $upload['id'], $wrongHash, null);

        $count = $this->UploadsChecker->recomputeHash(false);

        $this->assertSame((int) UploadsChecker::getStats()['count_all'], $count);
        $fresh = $this->fetchUpload((int) $upload['id']);
        $this->assertSame($upload['hash'], $fresh['hash']);
        $this->assertSame($upload['hash_algorithm'], $fresh['hash_algorithm']);

        $output = $this->output->fetch();
        $this->assertStringContainsString(
            sprintf('Found hash mismatch for upload id: %d', $upload['id']),
            $output,
        );
        $this->assertStringContainsString('Replacing faulty hash in database...', $output);
    }

    private function createUpload(): array
    {
        $id = $this->Entity->Uploads->create(new CreateUploadFromLocalFile(
            'uploads-checker-test.zip',
            dirname(__DIR__, 2) . '/_data/importable.zip',
        ));
        $this->Entity->Uploads->setId($id);
        $upload = $this->Entity->Uploads->uploadData;

        $this->assertSame(
            Storage::LOCAL->value,
            (int) $upload['storage'],
            'UploadsCheckerTest expects the test configuration to store uploads locally.',
        );
        $this->createdLongNames[] = $upload['long_name'];

        return $upload;
    }

    private function fetchUpload(int $id): array
    {
        $req = $this->Db->prepare('SELECT id, hash, hash_algorithm, filesize, storage, long_name FROM uploads WHERE id = :id');
        $req->bindValue(':id', $id, PDO::PARAM_INT);
        $this->Db->execute($req);
        return $req->fetch();
    }

    private function setNullFilesize(int $id): void
    {
        $req = $this->Db->prepare('UPDATE uploads SET filesize = NULL WHERE id = :id');
        $req->bindValue(':id', $id, PDO::PARAM_INT);
        $this->Db->execute($req);
    }

    private function setNullHashAndFilesize(int $id): void
    {
        $req = $this->Db->prepare('UPDATE uploads SET hash = NULL, filesize = NULL WHERE id = :id');
        $req->bindValue(':id', $id, PDO::PARAM_INT);
        $this->Db->execute($req);
    }

    private function setMissingFileWithNullFilesize(int $id, string $longName): void
    {
        $req = $this->Db->prepare('UPDATE uploads SET filesize = NULL, long_name = :long_name WHERE id = :id');
        $req->bindValue(':long_name', $longName);
        $req->bindValue(':id', $id, PDO::PARAM_INT);
        $this->Db->execute($req);
    }

    private function setHash(int $id, ?string $hash, ?string $algorithm): void
    {
        $req = $this->Db->prepare('UPDATE uploads SET hash = :hash, hash_algorithm = :hash_algorithm WHERE id = :id');
        $req->bindValue(':hash', $hash, $hash === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
        $req->bindValue(':hash_algorithm', $algorithm, $algorithm === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
        $req->bindValue(':id', $id, PDO::PARAM_INT);
        $this->Db->execute($req);
    }

    private function setStorage(int $id, Storage $storage): void
    {
        $req = $this->Db->prepare('UPDATE uploads SET storage = :storage WHERE id = :id');
        $req->bindValue(':storage', $storage->value, PDO::PARAM_INT);
        $req->bindValue(':id', $id, PDO::PARAM_INT);
        $this->Db->execute($req);
    }
}
