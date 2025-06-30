<?php

/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2012, 2022 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

declare(strict_types=1);

namespace Elabftw\Elabftw;

use Elabftw\Enums\State;
use Elabftw\Interfaces\CreateUploadParamsInterface;
use Elabftw\Interfaces\HashInterface;
use Elabftw\Services\Filter;
use League\Flysystem\Filesystem;
use League\Flysystem\Local\LocalFilesystemAdapter;
use Override;

class CreateUpload implements CreateUploadParamsInterface
{
    public function __construct(
        private readonly string $realName,
        protected string $filePath,
        public readonly HashInterface $hasher,
        private readonly ?string $comment = null,
        private readonly int $immutable = 0,
        private readonly State $state = State::Normal,
    ) {}

    #[Override]
    public function getFilename(): string
    {
        return Filter::forFilesystem($this->realName);
    }

    #[Override]
    public function getFilePath(): string
    {
        return $this->filePath;
    }

    #[Override]
    public function getTmpFilePath(): string
    {
        return basename($this->filePath);
    }

    #[Override]
    public function getComment(): ?string
    {
        if ($this->comment !== null && $this->comment !== '') {
            return $this->comment;
        }
        return null;
    }

    #[Override]
    public function getSourceFs(): Filesystem
    {
        return new Filesystem(new LocalFilesystemAdapter(dirname($this->filePath)));
    }

    #[Override]
    public function getImmutable(): int
    {
        return $this->immutable;
    }

    #[Override]
    public function getState(): State
    {
        return $this->state;
    }

    #[Override]
    public function getHasher(): HashInterface
    {
        return $this->hasher;
    }
}
