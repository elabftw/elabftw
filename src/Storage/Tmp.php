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

use League\Flysystem\FilesystemAdapter;
use League\Flysystem\Local\LocalFilesystemAdapter;
use Override;

/**
 * /tmp/
 */
final class Tmp extends Local
{
    protected const string FOLDER = '/tmp';

    #[Override]
    protected function getAdapter(): FilesystemAdapter
    {
        return new LocalFilesystemAdapter(static::FOLDER);
    }
}
