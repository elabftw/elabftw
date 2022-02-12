<?php declare(strict_types=1);
/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2022 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

namespace Elabftw\Services;

use League\Flysystem\FilesystemAdapter;
use League\Flysystem\Local\LocalFilesystemAdapter;

/**
 * For locally stored uploads
 */
class LocalStorage extends AbstractStorage
{
    protected const FOLDER = 'uploads';

    protected function getAdapter(): FilesystemAdapter
    {
        return new LocalFilesystemAdapter(dirname(__DIR__, 2) . '/' . static::FOLDER);
    }
}
