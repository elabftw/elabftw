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

use League\Flysystem\FilesystemOperator;
use Override;

/**
 * To hash a file
 */
class FileHash extends StringHash
{
    protected const string HASH_ALGORITHM = 'sha256';

    // size of a file in bytes above which we don't process it (100 Mb)
    protected const int THRESHOLD = 100000000;

    public function __construct(
        protected FilesystemOperator $filesystem,
        protected string $filename,
    ) {}

    #[Override]
    protected function getContent(): string
    {
        return $this->filesystem->read($this->filename);
    }

    #[Override]
    protected function canCompute(): bool
    {
        $filesize = $this->filesystem->fileSize($this->filename);
        return $filesize < self::THRESHOLD;
    }
}
