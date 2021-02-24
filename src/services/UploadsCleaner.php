<?php
/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */
declare(strict_types=1);

namespace Elabftw\Services;

use function basename;
use function count;
use Elabftw\Elabftw\Db;
use Elabftw\Interfaces\CleanerInterface;
use League\Flysystem\FilesystemInterface;
use function substr;

/**
 * This is used to find out if there are untracked files that should have been deleted
 * but were not deleted because of a bug fixed in 2.0.7
 */
class UploadsCleaner implements CleanerInterface
{
    /** @var FilesystemInterface $filesystem */
    private $filesystem;

    public function __construct(FilesystemInterface $filesystem)
    {
        $this->filesystem = $filesystem;
    }

    /**
     * Remove orphan files from filesystem
     *
     * @return int number of orphan files
     */
    public function cleanup(): int
    {
        $orphans = $this->findOrphans();
        foreach ($orphans as $orphan) {
            $this->filesystem->delete($orphan['path']);
        }
        return count($orphans);
    }

    /**
     * Loop of uploaded file and check if it is referenced in the uploads table
     *
     * @return array the orphaned files
     */
    private function findOrphans(): array
    {
        $orphans = array();
        $contents = $this->filesystem->listContents('', true);
        foreach ($contents as $file) {
            if ($file['type'] === 'file') {
                $isTracked = $this->isInDb($file['path']);
                if ($isTracked === false) {
                    $orphans[] = $file;
                }
            }
        }
        return $orphans;
    }

    /**
     * Given a file path, look in the Db to see if the file is referenced
     *
     * @param string $filePath full path to file
     * @return bool
     */
    private function isInDb(string $filePath): bool
    {
        // don't delete the thumbnails! They are not in the database but still useful!
        if (substr($filePath, -7) === '_th.jpg') {
            return true;
        }
        $longName = basename($filePath);
        $folder = substr($longName, 0, 2);
        $longNameWithFolder = $folder . '/' . $longName;
        $Db = Db::getConnection();
        $sql = 'SELECT long_name FROM uploads WHERE long_name = :long_name OR long_name = :long_name_with_folder';
        $req = $Db->prepare($sql);
        $req->bindParam(':long_name', $longName);
        $req->bindParam(':long_name_with_folder', $longNameWithFolder);
        $Db->execute($req);
        return (bool) $req->fetch();
    }
}
