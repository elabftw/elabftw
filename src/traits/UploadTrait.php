<?php
/**
 * @author Nicolas CARPi <nicolas.carpi@curie.fr>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */
declare(strict_types=1);

namespace Elabftw\Traits;

use Elabftw\Exceptions\FilesystemErrorException;
use PDO;

/**
 * For things related to file storage
 */
trait UploadTrait
{
    /**
     * Get the uploads folder absolute path
     *
     * @return string absolute path
     */
    public function getUploadsPath(): string
    {
        return \dirname(__DIR__, 2) . '/uploads/';
    }

    /**
     * Generate a long and unique string
     *
     * @return string a random sha512 hash
     */
    protected function getUniqueString(): string
    {
        return \hash('sha512', \bin2hex(\random_bytes(16)));
    }

    /**
     * Create a unique long filename with a folder
     *
     * @return string the path for storing the file
     */
    protected function getLongName(): string
    {
        $hash = $this->getUniqueString();
        $folder = substr($hash, 0, 2);
        // create a subfolder if it doesn't exist
        $folderPath = $this->getUploadsPath() . $folder;
        if (!\is_dir($folderPath) && !\mkdir($folderPath, 0700, true) && !\is_dir($folderPath)) {
            throw new FilesystemErrorException('Cannot create folder! Check permissions of uploads folder.');
        }
        return $folder . '/' . $hash;
    }

    /**
     * Get the total size on disk of uploaded files for a user
     *
     * @param int $userid
     * @return int
     */
    protected function getDiskUsage(int $userid): int
    {
        $sql = 'SELECT userid, long_name FROM uploads WHERE userid = :userid ORDER BY userid';
        $req = $this->Db->prepare($sql);
        $req->bindParam(':userid', $userid, PDO::PARAM_INT);
        $this->Db->execute($req);

        $uploads = $req->fetchAll();
        if ($uploads === false) {
            return 0;
        }
        $diskUsage = 0;
        foreach ($uploads as $upload) {
            $diskUsage += \filesize($this->getUploadsPath() . $upload['long_name']);
        }
        return $diskUsage;
    }
}
