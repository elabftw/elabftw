<?php

/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2022 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

declare(strict_types=1);

namespace Elabftw\Storage;

use League\Flysystem\FilesystemAdapter;
use League\Flysystem\InMemory\InMemoryFilesystemAdapter;
use Override;

/**
 * For in memory filesystem operations
 */
final class Memory extends AbstractStorage
{
    #[Override]
    public function getPath(string $relativePath = ''): string
    {
        // $path is not actually used here because php://memory does not provide a full file system
        // compare to https://github.com/thephpleague/flysystem/issues/471#issuecomment-106231642
        return 'php://memory';
    }

    #[Override]
    protected function getAdapter(): FilesystemAdapter
    {
        return new InMemoryFilesystemAdapter();
    }
}
