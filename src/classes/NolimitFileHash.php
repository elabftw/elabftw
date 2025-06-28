<?php

/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2025 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

declare(strict_types=1);

namespace Elabftw\Elabftw;

use Override;

/**
 * Always compute hash, regardless of filesize
 */
class NolimitFileHash extends FileHash
{
    #[Override]
    protected function canCompute(): bool
    {
        return true;
    }
}
