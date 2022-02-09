<?php declare(strict_types=1);
/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2012, 2022 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

namespace Elabftw\Elabftw;

use Elabftw\Interfaces\CreateUploadParamsInterface;
use Elabftw\Services\Filter;
use League\Flysystem\Filesystem;
use League\Flysystem\Local\LocalFilesystemAdapter;

class CreateUpload implements CreateUploadParamsInterface
{
    public function __construct(private string $realName, private string $filePath, private ?string $comment = null)
    {
    }

    public function getFilename(): string
    {
        return Filter::forFilesystem($this->realName);
    }

    public function getFilePath(): string
    {
        return $this->filePath;
    }

    public function getComment(): ?string
    {
        if ($this->comment !== null) {
            return nl2br(Filter::sanitize($this->comment));
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
}
