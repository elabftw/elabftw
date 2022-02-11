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
use Elabftw\Services\StorageFactory;
use League\Flysystem\Visibility;

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
        $storageFs = (new StorageFactory(StorageFactory::STORAGE_LOCAL))->getStorage()->getFs();
        $storageFs->createDirectory($folder);
        $storageFs->setVisibility($folder, Visibility::PRIVATE);
        return $folder . '/' . $hash;
    }
}
