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

class NullHash extends Hash
{
    public function __construct() {}

    #[Override]
    public function getHash(): ?string
    {
        return null;
    }
}
