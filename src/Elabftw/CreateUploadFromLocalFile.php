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
use Elabftw\Hash\LocalFileHash;
use Elabftw\Interfaces\HashInterface;
use League\Flysystem\Filesystem;
use League\Flysystem\Local\LocalFilesystemAdapter;
use Override;

final class CreateUploadFromLocalFile extends CreateUpload
{
    public function __construct(
        string $realName,
        string $filePath,
        private readonly ?string $comment = null,
        private readonly int $immutable = 0,
        private readonly State $state = State::Normal,
    ) {
        $this->filePath = $filePath;
        parent::__construct($realName, $filePath, $this->getHasher(), $this->comment, $this->immutable, $this->state);
    }

    #[Override]
    public function getSourceFs(): Filesystem
    {
        return new Filesystem(new LocalFilesystemAdapter(dirname($this->filePath)));
    }

    #[Override]
    public function getHasher(): HashInterface
    {
        return new LocalFileHash($this->filePath);
    }
}
