<?php

/**
 * @author Nicolas CARPi / Deltablot
 * @copyright 2026 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

declare(strict_types=1);

namespace Elabftw\Hash;

use Override;

use function password_hash;

class LocalPasswordHash extends StringHash
{
    #[Override]
    protected function compute(): ?string
    {
        if ($this->canCompute()) {
            return password_hash($this->getContent(), PASSWORD_DEFAULT);
        }
        return null;
    }
}
