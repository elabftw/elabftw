<?php declare(strict_types=1);
/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2022 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

namespace Elabftw\Services;

use Elabftw\Interfaces\StorageInterface;
use League\Flysystem\Filesystem;
use League\Flysystem\FilesystemAdapter;

/**
 * Storage providers extend this class
 */
abstract class AbstractStorage implements StorageInterface
{
    public function getFs(): Filesystem
    {
        return new Filesystem($this->getAdapter());
    }

    abstract protected function getAdapter(): FilesystemAdapter;
}
