<?php

/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2025 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

declare(strict_types=1);

namespace Elabftw\Hash;

use League\Flysystem\Filesystem;
use League\Flysystem\Local\LocalFilesystemAdapter;

/**
 * To hash a file stored on local filesystem
 */
final class LocalFileHash extends FileHash
{
    public function __construct(string $filepath)
    {
        $this->filename = basename($filepath);
        $this->filesystem = new Filesystem(new LocalFilesystemAdapter(dirname($filepath)));
    }
}
