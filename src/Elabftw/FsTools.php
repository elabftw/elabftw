<?php

/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2022 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

declare(strict_types=1);

namespace Elabftw\Elabftw;

use Elabftw\Storage\ParentCache;
use League\Flysystem\Filesystem;
use League\Flysystem\FilesystemOperator;
use League\Flysystem\Local\LocalFilesystemAdapter;
use League\Flysystem\Visibility;

/**
 * For filesystem related helpers
 */
final class FsTools
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
        return self::getCacheFolder('elab') . '/' . Tools::getUuidv4();
    }

    public static function getFs(string $path): FilesystemOperator
    {
        return new Filesystem(new LocalFilesystemAdapter($path));
    }
}
