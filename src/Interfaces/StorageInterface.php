<?php

/**
 * @package   Elabftw\Elabftw
 * @author    Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2022 Nicolas CARPi
 * @license   https://www.gnu.org/licenses/agpl-3.0.html AGPL-3.0
 * @see       https://www.elabftw.net Official website
 */

declare(strict_types=1);

namespace Elabftw\Interfaces;

use League\Flysystem\Filesystem;

/**
 * Interface for storage providers
 */
interface StorageInterface
{
    public function getFs(): Filesystem;

    public function getPath(string $relativePath = ''): string;

    public function getAbsoluteUri(string $path): string;
}
