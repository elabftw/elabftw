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
use Elabftw\Models\Users;
use Elabftw\Models\Experiments;
use Elabftw\Models\Database;
use Elabftw\Models\Uploads;

/**
 * This is used to find out if there are untracked files that should have been deleted
 * but were not deleted because of a bug fixed in 2.0.7
 */
class Cleaner
{
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
                $isTracked = $this->lookupInDb($file->getPathName());
                if ($isTracked === false) {
                    $orphans[] = $file->getPathName();
                }
            }
        }
        return $orphans;
    }

    private function lookupInDb($filePath): bool
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

    public function cleanup(): void
    {
        $orphans = $this->findOrphans();
        foreach ($orphans as $orphan) {
            if (\unlink($orphan) === false) {
                throw new FilesystemErrorException("Could not remove file: $orphan");
            }
        }
        printf("Deleted %d files\n", \count($orphans));
    }
}

require_once \dirname(__DIR__, 2) . '/vendor/autoload.php';
require_once \dirname(__DIR__, 2) . '/config.php';
$Cleaner = new Cleaner();
$Cleaner->cleanup();
