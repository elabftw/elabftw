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

/**
 * All about Experiments Links in Items
 */
final class Items2ExperimentsLinks extends AbstractExperimentsLinks
{
    #[Override]
    protected function getTable(): string
    {
        return 'items2experiments';
    }

    #[Override]
    protected function getOtherImportTypeTable(): string
    {
        return 'items2items';
    }
}
