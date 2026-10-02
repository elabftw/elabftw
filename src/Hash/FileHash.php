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

use function fclose;
use function hash_final;
use function hash_init;
use function hash_update_stream;

/**
 * To hash a file
 */
class FileHash extends AbstractHash
{
    public function __construct(
        protected FilesystemOperator $filesystem,
        protected string $filename,
    ) {}

    /**
     * @return resource
     */
    protected function getContent()
    {
        return $this->filesystem->readStream($this->filename);
    }

    #[Override]
    protected function compute(): ?string
    {
        $stream = $this->getContent();
        $context = hash_init($this->getAlgo());
        try {
            hash_update_stream($context, $stream);
            return hash_final($context);
        } finally {
            fclose($stream);
        }
    }
}
