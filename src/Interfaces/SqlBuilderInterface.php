<?php

/**
 * @package   Elabftw\Elabftw
 * @author    Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2025 Nicolas CARPi
 * @license   https://www.gnu.org/licenses/agpl-3.0.html AGPL-3.0
 * @see       https://www.elabftw.net Official website
 */

declare(strict_types=1);

namespace Elabftw\Interfaces;

use Elabftw\Enums\EntityType;

interface SqlBuilderInterface
{
    public function getReadSqlBeforeWhere(
        bool $getTags = true,
        bool $fullSelect = false,
        ?EntityType $relatedOrigin = null,
    ): string;

    public function getCanFilter(string $can): string;
}
