<?php declare(strict_types=1);
/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2012, 2022 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

namespace Elabftw\Elabftw;

use Aws\Credentials\Credentials;
use Elabftw\Interfaces\CreateUploadParamsInterface;
use Elabftw\Models\Config;
use Elabftw\Services\Filter;
use Elabftw\Services\LocalAdapter;
use Elabftw\Services\S3Adapter;
use League\Flysystem\Filesystem;
use League\Flysystem\FilesystemAdapter;
use League\Flysystem\Local\LocalFilesystemAdapter;

class CreateUpload implements CreateUploadParamsInterface
{
    private const STORAGE_LOCAL = 1;

    private const STORAGE_S3 = 2;

    private Filesystem $storageFs;

    public function __construct(private string $realName, private string $filePath, private int $storage, private ?string $comment = null)
    {
        $this->storageFs = new Filesystem($this->getAdapter());
    }

    public function getStorage(): int
    {
        return $this->storage;
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

    public function getStorageFs(): Filesystem
    {
        return $this->storageFs;
    }

    public function getSourceFs(): Filesystem
    {
        return new Filesystem(new LocalFilesystemAdapter($this->getSourcePath()));
    }

    public function getSourcePath(): string
    {
        return dirname($this->filePath) . '/';
    }

    private function getAdapter(): FilesystemAdapter
    {
        switch ($this->storage) {
            case self::STORAGE_S3:
                $adapter = new S3Adapter(Config::getConfig(), new Credentials(ELAB_AWS_ACCESS_KEY, ELAB_AWS_SECRET_KEY));
                break;
            case self::STORAGE_LOCAL:
                $adapter = new LocalAdapter();
                break;
            default:
                $adapter = new LocalAdapter();
        }
        return $adapter->getAdapter();
    }
}
