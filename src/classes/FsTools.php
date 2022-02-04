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
use function dirname;
use function hash;
use League\Flysystem\Filesystem;
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
        $tmpPath = dirname(__DIR__, 2) . '/cache/';
        $fs = new Filesystem(new LocalFilesystemAdapter($tmpPath));
        $fs->createDirectory($folder);
        $fs->setVisibility($folder, Visibility::PRIVATE);
        return $tmpPath . $folder;
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
}
