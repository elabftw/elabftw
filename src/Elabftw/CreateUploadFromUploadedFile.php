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
use Elabftw\Hash\FileHash;
use Elabftw\Interfaces\HashInterface;
use Elabftw\Storage\Tmp;
use League\Flysystem\Filesystem;
use Override;
use Symfony\Component\HttpFoundation\File\UploadedFile;

final class CreateUploadFromUploadedFile extends CreateUpload
{
    public function __construct(
        private readonly UploadedFile $uploadedFile,
        private readonly ?string $comment = null,
        private readonly int $immutable = 0,
        private readonly State $state = State::Normal,
    ) {
        parent::__construct($this->getFilename(), $this->getFilePath(), $this->getHasher(), $this->comment, $this->immutable, $this->state);
    }

    #[Override]
    public function getFilename(): string
    {
        return $this->uploadedFile->getClientOriginalName();
    }

    #[Override]
    public function getFilePath(): string
    {
        return $this->uploadedFile->getPathname();
    }

    #[Override]
    public function getTmpFilePath(): string
    {
        return basename($this->uploadedFile->getPathname());
    }

    #[Override]
    public function getSourceFs(): Filesystem
    {
        return new Tmp()->getFs();
    }

    #[Override]
    public function getHasher(): HashInterface
    {
        return new FileHash($this->getSourceFs(), $this->getTmpFilePath());
    }
}
