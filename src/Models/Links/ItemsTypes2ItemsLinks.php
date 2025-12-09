<?php

/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2024 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

declare(strict_types=1);

namespace Elabftw\Models\Links;

use Override;

/**
 * All about Items Links in Items Types
 */
final class ItemsTypes2ItemsLinks extends AbstractItemsLinks
{
    #[Override]
    protected function getTable(): string
    {
        return 'items_types2items';
    }

    #[Override]
    protected function getOtherImportTypeTable(): string
    {
        return 'items_types2experiments';
    }

    // ensure importing links uses the correct table. See #5875
    #[Override]
    protected function getImportTargetTable(): string
    {
        return 'items_types2items';
    }

    // ensure related links use the correct table
    #[Override]
    protected function getRelatedTable(): string
    {
        return 'items_types2items';
    }
}
