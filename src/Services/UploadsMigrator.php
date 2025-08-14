<?php

/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2022 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

declare(strict_types=1);

namespace Elabftw\Services;

use Elabftw\Elabftw\Db;
use Elabftw\Enums\Storage;
use League\Flysystem\FilesystemOperator;
use PDO;

use function count;

/**
 * Migrate uploads to S3
 */
final class UploadsMigrator
{
    private Db $Db;

    public function __construct(private FilesystemOperator $sourceFs, private FilesystemOperator $targetFs)
    {
        $this->Db = Db::getConnection();
    }

    public function migrate(): int
    {
        $sql = 'UPDATE uploads SET storage = :storage WHERE id = :id';
        $req = $this->Db->prepare($sql);
        $req->bindValue(':storage', Storage::S3->value);

        $localFiles = $this->findLocal();
        foreach ($localFiles as $upload) {
            $this->targetFs->writeStream($upload['long_name'], $this->sourceFs->readStream($upload['long_name']));
            // also upload the thumbnail if it exists
            if ($this->sourceFs->fileExists($upload['long_name'] . '_th.jpg')) {
                $this->targetFs->writeStream($upload['long_name'] . '_th.jpg', $this->sourceFs->readStream($upload['long_name'] . '_th.jpg'));
            }
            $req->bindParam(':id', $upload['id'], PDO::PARAM_INT);
            $this->Db->execute($req);
        }
        return count($localFiles);
    }

    public function fixBodies(): void
    {
        $this->fixBody('experiments');
        $this->fixBody('items');
    }

    private function fixBody(string $table): bool
    {
        $sql = sprintf('UPDATE %s SET body = REPLACE(body, "storage=%d", "storage=%d")', $table, Storage::LOCAL->value, Storage::S3->value);
        $req = $this->Db->prepare($sql);
        return $this->Db->execute($req);
    }

    /**
     * Get an array of uploads stored locally
     */
    private function findLocal(): array
    {
        $sql = 'SELECT long_name, id FROM uploads WHERE storage = :storage';
        $req = $this->Db->prepare($sql);
        $req->bindValue(':storage', Storage::LOCAL->value);
        $this->Db->execute($req);

        return $req->fetchAll();
    }
}
