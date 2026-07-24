<?php

/**
 * @author Nicolas CARPi <Deltablot>
 * @copyright 2026 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

declare(strict_types=1);

namespace Elabftw\Storage\Cache;

use Elabftw\Exceptions\ImproperActionException;
use Elabftw\Storage\AbstractStorage;
use Elabftw\Interfaces\CacheStorageInterface;
use Override;

/**
 * Cache Storage providers extend this class
 */
abstract class AbstractCache extends AbstractStorage implements CacheStorageInterface
{
    #[Override]
    public function clear(): bool
    {
        $fs = $this->getFs();
        foreach ($fs->listContents('', false) as $entry) {
            if ($entry->isFile()) {
                $fs->delete($entry->path());
                continue;
            }
            $fs->deleteDirectory($entry->path());
        }
        return true;
    }

    #[Override]
    public function warm(): bool
    {
        throw new ImproperActionException('No warm action for this folder');
    }
}
