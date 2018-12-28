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

/**
 * For things related to file storage
 *
 */
trait UploadTrait
{
    /**
     * Generate a long and unique string
     *
     * @return string a random sha512 hash
     */
    protected function getUniqueString(): string
    {
        return \hash("sha512", \bin2hex(\random_bytes(16)));
    }

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
}
