<?php

/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2022 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

declare(strict_types=1);

namespace Elabftw\Storage;

use Elabftw\Interfaces\StorageInterface;
use League\Flysystem\Filesystem;
use League\Flysystem\FilesystemAdapter;
use League\Flysystem\Local\LocalFilesystemAdapter;
use Override;

/**
 * Storage providers extend this class
 */
abstract class AbstractStorage implements StorageInterface
{
    protected const string FOLDER = '';

    #[Override]
    public function getFs(): Filesystem
    {
        return new Filesystem($this->getAdapter());
    }

    public static function getFolder(): string
    {
        return static::FOLDER;
    }

    #[Override]
    public function getAbsoluteUri(string $path): string
    {
        return $this->getPath($path);
    }

    /**
     * Get the absolute path of a resource
     * @param string $relativePath A relative path or filename. e.g. folder/file.txt or file.txt
     * @return string The absolute path of a resource
     */
    #[Override]
    public function getPath(string $relativePath = ''): string
    {
        return static::FOLDER . ($relativePath !== '' ? '/' . $relativePath : '');
    }

    protected function getAdapter(): FilesystemAdapter
    {
        return new LocalFilesystemAdapter(static::FOLDER);
    }
}
