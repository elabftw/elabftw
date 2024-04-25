<?php

/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

declare(strict_types=1);

namespace Elabftw\Traits;

use Elabftw\Elabftw\FsTools;
use Elabftw\Enums\Storage;
use League\Flysystem\Visibility;

/**
 * For things related to file storage
 */
trait UploadTrait
{
    /**
     * Create a unique long filename with a folder
     * Create the folder if it doesn't exist
     */
    protected function getLongName(): string
    {
        $hash = FsTools::getUniqueString();
        $folder = substr($hash, 0, 2);
        // create a subfolder if it doesn't exist
        $storageFs = Storage::LOCAL->getStorage()->getFs();
        $storageFs->createDirectory($folder);
        $storageFs->setVisibility($folder, Visibility::PRIVATE);
        return $folder . '/' . $hash;
    }
}
