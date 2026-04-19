<?php

/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2024 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

declare(strict_types=1);

namespace Elabftw\Elabftw;

use Elabftw\Enums\State;
use Elabftw\Interfaces\HashInterface;
use League\Flysystem\FilesystemOperator;
use Override;

final class CreateUploadFromFs extends CreateUpload
{
    public function __construct(
        protected FilesystemOperator $fs,
        string $realName,
        string $filePath,
        HashInterface $hasher,
        ?string $comment = null,
        int $immutable = 0,
        State $state = State::Normal,
    ) {
        parent::__construct(
            $realName,
            $filePath,
            $hasher,
            $comment,
            $immutable,
            $state,
        );
    }

    #[Override]
    public function getSourceFs(): FilesystemOperator
    {
        return $this->fs;
    }

    #[Override]
    public function getTmpFilePath(): string
    {
        return $this->filePath;
    }
}
