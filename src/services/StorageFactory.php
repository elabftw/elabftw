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
use Elabftw\Interfaces\StorageInterface;
use Elabftw\Models\Config;

/**
 * Manage storage stuff
 */
class StorageFactory
{
    public const STORAGE_LOCAL = 1;

    public const STORAGE_S3 = 2;

    public const STORAGE_MEMORY = 3;

    public const STORAGE_CACHE = 4;

    public function __construct(private int $storage)
    {
    }

    public function getStorage(): StorageInterface
    {
        switch ($this->storage) {
            case self::STORAGE_LOCAL:
                return (new LocalStorage());
            case self::STORAGE_S3:
                return (new S3Storage(Config::getConfig(), new Credentials(ELAB_AWS_ACCESS_KEY, ELAB_AWS_SECRET_KEY)));
            case self::STORAGE_MEMORY:
                return new MemoryStorage();
            case self::STORAGE_CACHE:
                return new CacheStorage();
            default:
                return new MemoryStorage();
        }
    }
}
