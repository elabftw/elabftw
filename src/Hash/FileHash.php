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

use function stream_get_contents;

/**
 * To hash a file
 */
class FileHash extends AbstractHash
{
    public function __construct(
        protected FilesystemOperator $filesystem,
        protected string $filename,
    ) {}

    #[Override]
    /**
     * @return resource
     */
    protected function getContent()
    {
        return $this->filesystem->readStream($this->filename);
    }

    #[Override]
    protected function getStringContent(): string
    {
        $inputStream = $this->getContent();
        return stream_get_contents($inputStream, 64 * 1024);
    }
}
