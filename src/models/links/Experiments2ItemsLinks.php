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
 * All about Items Links in Experiments
 */
final class Experiments2ItemsLinks extends AbstractItemsLinks
{
    #[Override]
    protected function getTable(): string
    {
        return 'experiments2items';
    }

    #[Override]
    protected function getOtherImportTypeTable(): string
    {
        return 'experiments2experiments';
    }
}
