<?php declare(strict_types=1);
/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2022 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

namespace Elabftw\Services;

use League\Flysystem\FilesystemAdapter;
use League\Flysystem\InMemory\InMemoryFilesystemAdapter;

/**
 * For in memory filesystem operations
 */
class MemoryStorage extends AbstractStorage
{
    protected function getAdapter(): FilesystemAdapter
    {
        return new InMemoryFilesystemAdapter();
    }
}
