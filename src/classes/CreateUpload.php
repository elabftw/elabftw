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
use Elabftw\Services\Filter;
use League\Flysystem\Filesystem;
use League\Flysystem\Local\LocalFilesystemAdapter;

class CreateUpload implements CreateUploadParamsInterface
{
    protected int $immutable = 0;

    protected State $state = State::Normal;

    public function __construct(private string $realName, protected string $filePath, private ?string $comment = null) {}

    public function getFilename(): string
    {
        return Filter::forFilesystem($this->realName);
    }

    public function getFilePath(): string
    {
        return $this->filePath;
    }

    public function getTmpFilePath(): string
    {
        return basename($this->filePath);
    }

    public function getComment(): ?string
    {
        if ($this->comment !== null && $this->comment !== '') {
            return $this->comment;
        }
        return null;
    }

    public function getSourceFs(): Filesystem
    {
        return new Filesystem(new LocalFilesystemAdapter($this->getSourcePath()));
    }

    public function getSourcePath(): string
    {
        return dirname($this->filePath) . '/';
    }

    public function getImmutable(): int
    {
        return $this->immutable;
    }

    public function getState(): State
    {
        return $this->state;
    }
}
