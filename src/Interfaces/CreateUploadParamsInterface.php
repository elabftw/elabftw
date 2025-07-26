<?php

/**
 * @package   Elabftw\Elabftw
 * @author    Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2012, 2022 Nicolas CARPi
 * @license   https://www.gnu.org/licenses/agpl-3.0.html AGPL-3.0
 * @see       https://www.elabftw.net Official website
 */

declare(strict_types=1);

namespace Elabftw\Interfaces;

use Elabftw\Enums\State;
use League\Flysystem\Filesystem;

/**
 * Interface for creating an upload
 */
interface CreateUploadParamsInterface
{
    public function getFilename(): string;

    public function getComment(): ?string;

    public function getFilePath(): string;

    public function getTmpFilePath(): string;

    public function getSourceFs(): Filesystem;

    public function getImmutable(): int;

    public function getState(): State;

    public function getHasher(): HashInterface;
}
