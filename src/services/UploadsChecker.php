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
        $sql = sprintf('SELECT id, storage, long_name FROM uploads WHERE %s IS NULL', $column);
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

    public function fixNullHash(): int
    {
        $toFix = $this->getNullColumn('hash');
        $fixedCount = 0;
        foreach ($toFix as $upload) {
            // TODO implement it for S3 storage
            if ($upload['storage'] === Storage::S3->value) {
                continue;
            }
            $hash = hash_file(Uploads::HASH_ALGORITHM, dirname(__DIR__, 2) . '/uploads/' . $upload['long_name']);
            if (empty($hash)) {
                continue;
            }
            $sql = 'UPDATE uploads SET hash = :hash, hash_algorithm = :hash_algorithm WHERE id = :id';
            $req = $this->Db->prepare($sql);
            $req->bindParam(':hash', $hash);
            $req->bindValue(':hash_algorithm', Uploads::HASH_ALGORITHM);
            $req->bindParam(':id', $upload['id'], PDO::PARAM_INT);
            $this->Db->execute($req);
            $fixedCount += 1;
        }
        return $fixedCount;
    }
}
