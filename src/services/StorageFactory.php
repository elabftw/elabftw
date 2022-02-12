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
 * This factory is responsible for providing a storage provider
 */
class StorageFactory
{
    public const LOCAL = 1;

    public const S3 = 2;

    public const MEMORY = 3;

    public const CACHE = 4;

    public const FIXTURES = 5;

    public function __construct(private int $storage)
    {
    }

    public function getStorage(): StorageInterface
    {
        switch ($this->storage) {
            case self::LOCAL:
                return (new LocalStorage());
            case self::S3:
                return (new S3Storage(Config::getConfig(), new Credentials(ELAB_AWS_ACCESS_KEY, ELAB_AWS_SECRET_KEY)));
            case self::MEMORY:
                return new MemoryStorage();
            case self::CACHE:
                return new CacheStorage();
            case self::FIXTURES:
                return new FixturesStorage();
            default:
                return new MemoryStorage();
        }
    }
}
