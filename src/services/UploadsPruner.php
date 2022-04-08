<?php declare(strict_types=1);
/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2022 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

namespace Elabftw\Services;

use Elabftw\Elabftw\Db;
use Elabftw\Interfaces\CleanerInterface;
use Elabftw\Models\Uploads;
use PDO;

/**
 * Remove deleted uploads
 */
class UploadsPruner implements CleanerInterface
{
    private Db $Db;

    public function __construct()
    {
        $this->Db = Db::getConnection();
    }

    /**
     * Remove uploads with deleted state from database and filesystem
     * This is a global function and should only be called by uploads:prune command
     */
    public function cleanup(): int
    {
        $sql = 'SELECT id, long_name, storage FROM uploads WHERE state = :state';
        $req = $this->Db->prepare($sql);
        $req->bindValue(':state', Uploads::STATE_DELETED, PDO::PARAM_INT);
        $this->Db->execute($req);
        foreach ($req->fetchAll() as $upload) {
            $storageFs = (new StorageFactory((int) $upload['storage']))->getStorage()->getFs();
            $storageFs->delete($upload['long_name']);
            // also delete an hypothetical thumbnail
            // this won't throw any error if the file doesn't exist
            $storageFs->delete($upload['long_name'] . '_th.jpg');
        }
        $this->deleteFromDb();

        return $req->rowCount();
    }

    private function deleteFromDb(): bool
    {
        $sql = 'DELETE FROM uploads WHERE state = :state';
        $req = $this->Db->prepare($sql);
        $req->bindValue(':state', Uploads::STATE_DELETED, PDO::PARAM_INT);
        return $this->Db->execute($req);
    }
}
