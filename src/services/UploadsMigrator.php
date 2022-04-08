<?php declare(strict_types=1);
/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2022 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

namespace Elabftw\Services;

use function count;
use Elabftw\Elabftw\Db;
use Elabftw\Models\Uploads;
use League\Flysystem\FilesystemOperator;
use PDO;

/**
 * Migrate uploads to S3
 */
class UploadsMigrator
{
    private Db $Db;

    public function __construct(private FilesystemOperator $sourceFs, private FilesystemOperator $targetFs)
    {
        $this->Db = Db::getConnection();
    }

    public function migrate(): int
    {
        $localFiles = $this->findLocal();
        foreach ($localFiles as $upload) {
            $this->targetFs->writeStream($upload['long_name'], $this->sourceFs->readStream($upload['long_name']));
            // also upload the thumbnail if it exists
            if ($this->sourceFs->fileExists($upload['long_name'] . '_th.jpg')) {
                $this->targetFs->writeStream($upload['long_name'] . '_th.jpg', $this->sourceFs->readStream($upload['long_name'] . '_th.jpg'));
            }
            $sql = 'UPDATE uploads SET storage = :storage WHERE id = :id';
            $req = $this->Db->prepare($sql);
            $req->bindValue(':storage', StorageFactory::S3);
            $req->bindParam(':id', $upload['id'], PDO::PARAM_INT);
            $this->Db->execute($req);
        }
        return count($localFiles);
    }

    /**
     * Get an array of uploads stored locally
     */
    private function findLocal(): array
    {
        $sql = 'SELECT * FROM uploads WHERE storage = :storage';
        $req = $this->Db->prepare($sql);
        $req->bindValue(':storage', StorageFactory::LOCAL);
        $this->Db->execute($req);

        return $req->fetchAll();
    }
}
