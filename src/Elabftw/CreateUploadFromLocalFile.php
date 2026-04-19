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
use League\Flysystem\FilesystemOperator;
use League\Flysystem\Local\LocalFilesystemAdapter;
use Override;

use function dirname;

final class CreateUploadFromLocalFile extends CreateUpload
{
    public function __construct(
        string $realName,
        string $filePath,
        ?string $comment = null,
        int $immutable = 0,
        State $state = State::Normal,
    ) {
        $this->filePath = $filePath;
        parent::__construct($realName, $filePath, $this->getHasher(), $comment, $immutable, $state);
    }

    #[Override]
    public function getSourceFs(): FilesystemOperator
    {
        return new Filesystem(new LocalFilesystemAdapter(dirname($this->filePath)));
    }

    #[Override]
    public function getHasher(): HashInterface
    {
        return new LocalFileHash($this->filePath);
    }
}
