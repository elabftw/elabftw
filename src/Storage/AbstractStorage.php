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

    /**
     * Get the absolute path of a resource
     * @param string $relativePath A relative path or filename. e.g. folder/file.txt or file.txt
     * @return string The absolute path of a resource
     */
    #[Override]
    public function getPath(string $relativePath = ''): string
    {
        return '/elabftw/' . static::FOLDER . ($relativePath !== '' ? '/' . $relativePath : '');
    }

    abstract protected function getAdapter(): FilesystemAdapter;
}
