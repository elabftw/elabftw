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

use Elabftw\Enums\Storage;
use League\Flysystem\Filesystem;
use Override;

class CreateUploadFromS3 extends CreateUpload
{
    #[Override]
    public function getSourceFs(): Filesystem
    {
        return Storage::from(Storage::S3->value)->getStorage()->getFs();
    }

    #[Override]
    public function getSourcePath(): string
    {
        return $this->filePath;
    }

    #[Override]
    public function getTmpFilePath(): string
    {
        return $this->filePath;
    }
}
