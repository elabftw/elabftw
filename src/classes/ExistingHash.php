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

class ExistingHash extends Hash
{
    public function __construct(protected ?string $hash = null) {}

    #[Override]
    protected function compute(): ?string
    {
        return $this->hash;
    }
}
