<?php

/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2024 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

declare(strict_types=1);

namespace Elabftw\Models;

use Override;

final class Compounds2ItemsTypesLinks extends AbstractCompoundsLinks
{
    #[Override]
    protected function getTable(): string
    {
        return 'compounds2items_types';
    }
}
