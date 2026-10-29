<?php

/**
 * @package   Elabftw\Elabftw
 * @author    Nicolas CARPi / Deltablot
 * @copyright 2026 Nicolas CARPi
 * @license   https://www.gnu.org/licenses/agpl-3.0.html AGPL-3.0
 * @see       https://www.elabftw.net Official website
 */

declare(strict_types=1);

namespace Elabftw\Interfaces;

use Elabftw\Elabftw\Compound;

interface CompoundInterface
{
    public function createFromCompound(Compound $compound): int;

    public function setId(?int $id): void;

    public function readOne(): array;
}
