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
 * All about Items Links in Experiments Templates
 */
final class ExperimentsTemplates2ItemsLinks extends AbstractItemsLinks
{
    #[Override]
    protected function getTable(): string
    {
        return 'experiments_templates2items';
    }

    #[Override]
    protected function getOtherImportTypeTable(): string
    {
        return 'experiments_templates2experiments';
    }
}
