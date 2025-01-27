<?php

/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2023 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

declare(strict_types=1);

namespace Elabftw\Enums;

use Aws\Credentials\Credentials;
use Elabftw\Interfaces\StorageInterface;
use Elabftw\Models\Config;
use Elabftw\Storage\Cache;
use Elabftw\Storage\Exports;
use Elabftw\Storage\Fixtures;
use Elabftw\Storage\Local;
use Elabftw\Storage\Memory;
use Elabftw\Storage\S3;

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
    case EXPORTS = 6;

    public function getStorage(): StorageInterface
    {
        return match ($this) {
            $this::LOCAL => new Local(),
            $this::S3 => new S3(Config::getConfig(), new Credentials(Config::fromEnv('ELAB_AWS_ACCESS_KEY'), Config::fromEnv('ELAB_AWS_SECRET_KEY'))),
            $this::MEMORY => new Memory(),
            $this::CACHE => new Cache(),
            $this::FIXTURES => new Fixtures(),
            $this::EXPORTS => new Exports(),
        };
    }
}
