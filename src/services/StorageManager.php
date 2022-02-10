<?php declare(strict_types=1);
/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2022 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

namespace Elabftw\Services;

use Aws\Credentials\Credentials;
use const ELAB_AWS_ACCESS_KEY;
use const ELAB_AWS_SECRET_KEY;
use Elabftw\Models\Config;
use League\Flysystem\Filesystem;
use League\Flysystem\FilesystemAdapter;
use League\Flysystem\InMemory\InMemoryFilesystemAdapter;

/**
 * Manage storage stuff
 */
class StorageManager
{
    public const STORAGE_LOCAL = 1;

    public const STORAGE_S3 = 2;

    public const STORAGE_MEMORY = 3;

    public function __construct(private int $storage)
    {
    }

    public function getStorageFs(): Filesystem
    {
        return new Filesystem($this->getAdapter());
    }

    private function getAdapter(): FilesystemAdapter
    {
        switch ($this->storage) {
            case self::STORAGE_S3:
                return (new S3Adapter(Config::getConfig(), new Credentials(ELAB_AWS_ACCESS_KEY, ELAB_AWS_SECRET_KEY)))->getAdapter();
            case self::STORAGE_LOCAL:
                return (new LocalAdapter())->getAdapter();
            case self::STORAGE_MEMORY:
                return new InMemoryFilesystemAdapter();
            default:
                return new InMemoryFilesystemAdapter();
        }
    }
}
