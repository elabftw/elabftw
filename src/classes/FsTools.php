<?php declare(strict_types=1);
/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2022 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

namespace Elabftw\Elabftw;

use function bin2hex;
use Elabftw\Storage\ParentCache;

use function hash;
use League\Flysystem\Filesystem;
use League\Flysystem\FilesystemOperator;
use League\Flysystem\Local\LocalFilesystemAdapter;
use League\Flysystem\Visibility;
use function random_bytes;

/**
 * For filesystem related helpers
 */
class FsTools
{
    /**
     * Create a directory in the cache folder and return the full path
     */
    public static function getCacheFolder(string $folder): string
    {
        $storage = new ParentCache();
        $fs = $storage->getFs();
        $fs->createDirectory($folder);
        $fs->setVisibility($folder, Visibility::PRIVATE);
        return $storage->getPath($folder);
    }

    /**
     * Return a path to a file with a random name in the elab's cache folder
     */
    public static function getCacheFile(): string
    {
        return self::getCacheFolder('elab') . '/' . self::getUniqueString();
    }

    public static function getUniqueString(): string
    {
        return hash('sha512', bin2hex(random_bytes(16)));
    }

    public static function getFs(string $path): FilesystemOperator
    {
        return new Filesystem(new LocalFilesystemAdapter($path));
    }
}
