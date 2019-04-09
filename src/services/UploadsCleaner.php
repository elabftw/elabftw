<?php
/**
 * @author Nicolas CARPi <nicolas.carpi@curie.fr>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */
namespace Elabftw\Services;

use Elabftw\Elabftw\Db;
use Elabftw\Exceptions\DatabaseErrorException;
use Elabftw\Exceptions\FilesystemErrorException;
use Elabftw\Interfaces\CleanerInterface;

/**
 * This is used to find out if there are untracked files that should have been deleted
 * but were not deleted because of a bug fixed in 2.0.7
 */
class UploadsCleaner implements CleanerInterface
{
    /**
     * Loop of uploaded file and check if it is referenced in the uploads table
     *
     * @return array the orphaned files
     */
    private function findOrphans(): array
    {
        $orphans = array();
        $dir = \dirname(__DIR__, 2) . '/uploads';
        if (!is_dir($dir)) {
            return $orphans;
        }
        $di = new \RecursiveDirectoryIterator($dir, \FilesystemIterator::SKIP_DOTS);
        $ri = new \RecursiveIteratorIterator($di, \RecursiveIteratorIterator::CHILD_FIRST);
        foreach ($ri as $file) {
            if ($file->isDir() === false) {
                $isTracked = $this->isInDb($file->getPathName());
                if ($isTracked === false) {
                    $orphans[] = $file->getPathName();
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
        $longName = \basename($filePath);
        $folder = substr($longName, 0, 2);
        $longNameWithFolder = $folder . '/' . $longName;
        $Db = Db::getConnection();
        $sql = "SELECT long_name FROM uploads WHERE long_name = :long_name OR long_name = :long_name_with_folder";
        $req = $Db->prepare($sql);
        $req->bindParam(':long_name', $longName);
        $req->bindParam(':long_name_with_folder', $longNameWithFolder);
        if ($req->execute() !== true) {
            throw new DatabaseErrorException('Error while executing SQL query.');
        }
        return (bool) $req->fetch();
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
            if (\unlink($orphan) === false) {
                throw new FilesystemErrorException("Could not remove file: $orphan");
            }
        }
        return \count($orphans);
    }
}
