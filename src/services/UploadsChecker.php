<?php

/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2023 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

declare(strict_types=1);

namespace Elabftw\Services;

use Elabftw\Elabftw\Db;
use Elabftw\Enums\Storage;
use Elabftw\Models\Uploads;
use PDO;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Check uploads for correct hash and filesize
 */
class UploadsChecker
{
    private Db $Db;

    public function __construct()
    {
        $this->Db = Db::getConnection();
    }

    public function getStats(): array
    {
        $sql = 'SELECT
            COUNT(id) AS count_all,
            SUM(filesize) AS filesize_all,
            COUNT(CASE WHEN hash IS NULL THEN 1 END) AS count_null_hash,
            COUNT(CASE WHEN filesize IS NULL THEN 1 END) AS count_null_filesize
            FROM uploads';
        $req = $this->Db->prepare($sql);
        $this->Db->execute($req);
        return $req->fetch();
    }

    public function getNullColumn(string $column): array
    {
        // don't use bindParam here for the column, it doesn't work.
        // TODO do it also for S3 storage
        $sql = sprintf('SELECT id, storage, long_name FROM uploads WHERE %s IS NULL AND storage = 1', $column);
        $req = $this->Db->prepare($sql);
        $this->Db->execute($req);
        return $req->fetchAll();
    }

    public function fixNullFilesize(): int
    {
        $toFix = $this->getNullColumn('filesize');
        $fixedCount = 0;
        foreach ($toFix as $upload) {
            $storageFs = Storage::from($upload['storage'])->getStorage()->getFs();
            $filesize = $storageFs->fileSize($upload['long_name']);
            $sql = 'UPDATE uploads SET filesize = :filesize WHERE id = :id';
            $req = $this->Db->prepare($sql);
            $req->bindParam(':filesize', $filesize, PDO::PARAM_INT);
            $req->bindParam(':id', $upload['id'], PDO::PARAM_INT);
            $this->Db->execute($req);
            $fixedCount += 1;
        }
        return $fixedCount;
    }

    public function recomputeHash(OutputInterface $output, bool $dryRun): int
    {
        $sql = 'SELECT id, hash, storage, long_name FROM uploads ORDER BY uploads.created_at DESC';
        $req = $this->Db->prepare($sql);
        $this->Db->execute($req);
        $uploads = $req->fetchAll();
        foreach ($uploads as $upload) {
            $hash = hash_file(Uploads::HASH_ALGORITHM, dirname(__DIR__, 2) . '/uploads/' . $upload['long_name']);
            if (empty($hash)) {
                continue;
            }
            if ($upload['hash'] !== $hash) {
                $output->writeln(sprintf('Found hash mismatch for upload id: %d, stored at %s', $upload['id'], $upload['long_name']));
                $output->writeln(sprintf('Expected: %s but calculated: %s', $upload['hash'], $hash));
                if (!$dryRun) {
                    $output->writeln('Replacing faulty hash in database...');
                    $this->updateHash($upload['id'], $hash);
                } else {
                    $output->writeln('Not replacing faulty hash in database because dry-run mode enabled.');
                }
            }
        }
        return count($uploads);
    }

    public function fixNullHash(): int
    {
        $toFix = $this->getNullColumn('hash');
        $fixedCount = 0;
        foreach ($toFix as $upload) {
            $hash = hash_file(Uploads::HASH_ALGORITHM, dirname(__DIR__, 2) . '/uploads/' . $upload['long_name']);
            if (empty($hash)) {
                continue;
            }
            $this->updateHash($upload['id'], $hash);
            $fixedCount += 1;
        }
        return $fixedCount;
    }

    private function updateHash(int $id, string $hash): bool
    {
        $sql = 'UPDATE uploads SET hash = :hash, hash_algorithm = :hash_algorithm WHERE id = :id';
        $req = $this->Db->prepare($sql);
        $req->bindParam(':hash', $hash);
        $req->bindValue(':hash_algorithm', Uploads::HASH_ALGORITHM);
        $req->bindParam(':id', $id, PDO::PARAM_INT);
        return $this->Db->execute($req);
    }
}
