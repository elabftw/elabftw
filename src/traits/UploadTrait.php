<?php declare(strict_types=1);
/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

namespace Elabftw\Traits;

use function dirname;
use Elabftw\Elabftw\Db;
use Elabftw\Elabftw\FsTools;
use function filesize;
use League\Flysystem\Filesystem;
use League\Flysystem\Local\LocalFilesystemAdapter;
use League\Flysystem\Visibility;
use PDO;

/**
 * For things related to file storage
 */
trait UploadTrait
{
    protected Db $Db;

    /**
     * Get the uploads folder absolute path
     */
    public function getUploadsPath(): string
    {
        return dirname(__DIR__, 2) . '/uploads/';
    }

    /**
     * Create a unique long filename with a folder
     * Create the folder if it doesn't exist
     */
    protected function getLongName(): string
    {
        $hash = FsTools::getUniqueString();
        $folder = substr($hash, 0, 2);
        // create a subfolder if it doesn't exist
        $folderPath = $this->getUploadsPath() . $folder;
        $fs = new Filesystem(new LocalFilesystemAdapter($this->getUploadsPath()));
        $fs->createDirectory($folder);
        $fs->setVisibility($folder, Visibility::PRIVATE);
        return $folder . '/' . $hash;
    }

    /**
     * Get the total size on disk of uploaded files for a user
     */
    protected function getDiskUsage(int $userid): int
    {
        $sql = 'SELECT userid, long_name FROM uploads WHERE userid = :userid ORDER BY userid';
        $req = $this->Db->prepare($sql);
        $req->bindParam(':userid', $userid, PDO::PARAM_INT);
        $this->Db->execute($req);

        $uploads = $this->Db->fetchAll($req);
        $diskUsage = 0;
        foreach ($uploads as $upload) {
            $diskUsage += filesize($this->getUploadsPath() . $upload['long_name']);
        }
        return $diskUsage;
    }
}
