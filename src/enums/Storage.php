<?php declare(strict_types=1);
/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2023 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

namespace Elabftw\Enums;

use Aws\Credentials\Credentials;
use Elabftw\Interfaces\StorageInterface;
use Elabftw\Models\Config;
use Elabftw\Services\CacheStorage;
use Elabftw\Services\FixturesStorage;
use Elabftw\Services\LocalStorage;
use Elabftw\Services\MemoryStorage;
use Elabftw\Services\S3Storage;

/**
 * This enum is responsible for providing a storage provider
 */
enum Storage: int
{
    case LOCAL = 1;
    case S3 = 2;
    case MEMORY = 3;
    case CACHE = 4;
    case FIXTURES = 5;

    public function getStorage(): StorageInterface
    {
        return match ($this) {
            $this::LOCAL => new LocalStorage(),
            $this::S3 => new S3Storage(Config::getConfig(), new Credentials(Config::fromEnv('ELAB_AWS_ACCESS_KEY'), Config::fromEnv('ELAB_AWS_SECRET_KEY'))),
            $this::MEMORY => new MemoryStorage(),
            $this::CACHE => new CacheStorage(),
            $this::FIXTURES => new FixturesStorage(),
        };
    }
}
